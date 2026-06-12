<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_summary_corrections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('management_lead_summary_id')->nullable()->index();
            $table->string('client_number')->nullable()->index();
            $table->string('period_key')->nullable()->index();
            $table->string('field_name')->index();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('correction_reason')->nullable();
            $table->text('learning_keywords')->nullable();
            $table->string('source')->default('human');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_summary_corrections');
    }
};
