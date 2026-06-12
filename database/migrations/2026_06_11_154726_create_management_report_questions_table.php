<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_report_questions', function (Blueprint $table) {
            $table->id();

            $table->string('period_key', 30);
            $table->date('period_start');
            $table->date('period_end');

            $table->longText('question');
            $table->longText('answer')->nullable();

            $table->string('cache_name')->nullable();
            $table->string('model', 120)->nullable();
            $table->string('provider', 50)->nullable();

            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('cached_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();

            $table->longText('raw_response')->nullable();

            $table->timestamps();

            $table->index('period_key', 'mgmt_questions_period_idx');
            $table->index('created_at', 'mgmt_questions_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_report_questions');
    }
};
