<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_report_caches', function (Blueprint $table) {
            $table->id();

            $table->string('period_key', 20);
            $table->date('period_start');
            $table->date('period_end');

            $table->string('model', 120)->nullable();
            $table->string('cache_name')->nullable();
            $table->unsignedInteger('ttl_seconds')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 30)->default('draft');

            $table->unsignedInteger('cached_token_count')->nullable();
            $table->string('source_payload_hash', 128)->nullable();

            $table->longText('source_payload')->nullable();
            $table->longText('last_error')->nullable();

            $table->timestamps();

            $table->index('period_key', 'mgmt_cache_period_idx');
            $table->index('status', 'mgmt_cache_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_report_caches');
    }
};
