<?php

namespace App\Services;

use Google\Auth\ApplicationDefaultCredentials;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiClient
{
    public function generateJson(string $prompt, array $options = []): Response
    {
        $options['responseMimeType'] = $options['responseMimeType'] ?? 'application/json';

        return $this->generate($prompt, $options);
    }

    public function generateText(string $prompt, array $options = []): Response
    {
        $options['responseMimeType'] = $options['responseMimeType'] ?? null;

        return $this->generate($prompt, $options);
    }

    public function generate(string $prompt, array $options = []): Response
    {
        $provider = strtolower(trim((string) env('AI_PROVIDER', 'developer')));

        return match ($provider) {
            'vertex', 'vertex_ai', 'google_cloud' => $this->generateWithVertex($prompt, $options),
            default => $this->generateWithDeveloperApi($prompt, $options),
        };
    }

    public function createContextCache(string $displayName, string $contextText, int $ttlSeconds, array $options = []): Response
    {
        $projectId = $this->vertexProjectId();
        $location = $this->vertexLocation();
        $model = $options['model'] ?? env('VERTEX_AI_MODEL', 'gemini-2.5-flash');

        $host = $this->vertexHost($location);
        $endpoint = "https://{$host}/v1/projects/{$projectId}/locations/{$location}/cachedContents";

        $payload = [
            'model' => "projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}",
            'displayName' => $displayName,
            'ttl' => max($ttlSeconds, 60) . 's',
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $contextText],
                    ],
                ],
            ],
        ];

        return Http::timeout((int) ($options['timeout'] ?? 90))
            ->retry((int) ($options['retries'] ?? 1), 1000, throw: false)
            ->withToken($this->getVertexAccessToken())
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, $payload);
    }

    public function generateTextWithCache(string $cacheName, string $prompt, array $options = []): Response
    {
        $projectId = $this->vertexProjectId();
        $location = $this->vertexLocation();
        $model = $options['model'] ?? env('VERTEX_AI_MODEL', 'gemini-2.5-flash');

        $host = $this->vertexHost($location);
        $endpoint = "https://{$host}/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}:generateContent";

        $payload = $this->buildPayload($prompt, [
            'responseMimeType' => null,
            'maxOutputTokens' => $options['maxOutputTokens'] ?? 4096,
            'temperature' => $options['temperature'] ?? 0.2,
            'thinkingLevel' => $options['thinkingLevel'] ?? env('VERTEX_AI_THINKING_LEVEL'),
        ], true);

        $payload['cachedContent'] = $cacheName;

        return Http::timeout((int) ($options['timeout'] ?? 90))
            ->retry((int) ($options['retries'] ?? 1), 1000, throw: false)
            ->withToken($this->getVertexAccessToken())
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, $payload);
    }

    private function generateWithDeveloperApi(string $prompt, array $options = []): Response
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('GEMINI_API_KEY belum tersedia di .env.');
        }

        $model = $options['model']
            ?? env('GEMINI_MODEL')
            ?? env('GEMINI_DEVELOPER_MODEL')
            ?? 'gemini-3-flash-preview';

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        return Http::timeout((int) ($options['timeout'] ?? 60))
            ->retry((int) ($options['retries'] ?? 2), 1000, throw: false)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, $this->buildPayload($prompt, $options, false));
    }

    private function generateWithVertex(string $prompt, array $options = []): Response
    {
        $projectId = $this->vertexProjectId();
        $location = $this->vertexLocation();

        $model = $options['model']
            ?? env('VERTEX_AI_MODEL')
            ?? 'gemini-2.5-flash';

        $host = $this->vertexHost($location);
        $endpoint = "https://{$host}/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}:generateContent";

        return Http::timeout((int) ($options['timeout'] ?? 60))
            ->retry((int) ($options['retries'] ?? 2), 1000, throw: false)
            ->withToken($this->getVertexAccessToken())
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, $this->buildPayload($prompt, $options, true));
    }

    private function buildPayload(string $prompt, array $options = [], bool $allowThinking = true): array
    {
        $generationConfig = [];

        if (array_key_exists('temperature', $options)) {
            $generationConfig['temperature'] = $options['temperature'];
        }

        $responseMimeType = array_key_exists('responseMimeType', $options)
            ? $options['responseMimeType']
            : 'application/json';

        if ($responseMimeType !== null) {
            $generationConfig['responseMimeType'] = $responseMimeType;
        }

        $maxOutputTokens = $options['maxOutputTokens']
            ?? env('VERTEX_AI_MAX_OUTPUT_TOKENS')
            ?? env('GEMINI_MAX_OUTPUT_TOKENS');

        if ($maxOutputTokens) {
            $generationConfig['maxOutputTokens'] = (int) $maxOutputTokens;
        }

        if ($allowThinking) {
            $thinkingLevel = $options['thinkingLevel']
                ?? env('VERTEX_AI_THINKING_LEVEL');

            $thinkingBudget = $options['thinkingBudget']
                ?? env('VERTEX_AI_THINKING_BUDGET');

            if ($thinkingLevel) {
                $generationConfig['thinkingConfig'] = [
                    'thinkingLevel' => strtoupper((string) $thinkingLevel),
                ];
            } elseif ($thinkingBudget !== null && $thinkingBudget !== '') {
                $generationConfig['thinkingConfig'] = [
                    'thinkingBudget' => (int) $thinkingBudget,
                ];
            }
        }

        return [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => $generationConfig,
        ];
    }

    private function vertexProjectId(): string
    {
        $projectId = env('VERTEX_AI_PROJECT_ID') ?: env('GOOGLE_CLOUD_PROJECT');

        if (!$projectId) {
            throw new RuntimeException('VERTEX_AI_PROJECT_ID atau GOOGLE_CLOUD_PROJECT belum tersedia di .env.');
        }

        return $projectId;
    }

    private function vertexLocation(): string
    {
        return env('VERTEX_AI_LOCATION', 'us-central1');
    }

    private function vertexHost(string $location): string
    {
        return $location === 'global'
            ? 'aiplatform.googleapis.com'
            : "{$location}-aiplatform.googleapis.com";
    }

    private function getVertexAccessToken(): string
    {
        if (env('VERTEX_AI_ACCESS_TOKEN')) {
            return trim((string) env('VERTEX_AI_ACCESS_TOKEN'));
        }

        if (class_exists(ApplicationDefaultCredentials::class)) {
            $credentials = ApplicationDefaultCredentials::getCredentials([
                'https://www.googleapis.com/auth/cloud-platform',
            ]);

            $token = $credentials->fetchAuthToken();

            if (!empty($token['access_token'])) {
                return $token['access_token'];
            }
        }

        $tokenFromAdc = trim((string) shell_exec('gcloud auth application-default print-access-token 2>/dev/null'));

        if ($tokenFromAdc !== '') {
            return $tokenFromAdc;
        }

        $tokenFromGcloud = trim((string) shell_exec('gcloud auth print-access-token 2>/dev/null'));

        if ($tokenFromGcloud !== '') {
            return $tokenFromGcloud;
        }

        throw new RuntimeException('Gagal mendapatkan access token Vertex AI. Jalankan gcloud auth application-default login atau set GOOGLE_APPLICATION_CREDENTIALS.');
    }
}
