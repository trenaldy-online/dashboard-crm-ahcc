<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_classification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('field_name')->index();
            $table->text('match_keywords')->nullable();
            $table->text('target_value')->nullable();
            $table->text('reasoning')->nullable();
            $table->unsignedBigInteger('example_summary_id')->nullable()->index();
            $table->string('example_client_number')->nullable()->index();
            $table->string('source')->default('human_correction');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            $table->index(['field_name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_classification_rules');
    }
};
