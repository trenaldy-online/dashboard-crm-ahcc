<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lead_summaries', function (Blueprint $table) {
            $table->integer('patient_message_count')->default(0);
            $table->string('conversation_outcome')->default('pending');
            $table->boolean('is_eligible_for_hana')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_summaries', function (Blueprint $table) {
            //
        });
    }
};
