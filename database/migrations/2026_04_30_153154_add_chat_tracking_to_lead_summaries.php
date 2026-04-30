<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('lead_summaries', function (Blueprint $table) {
        $table->timestamp('last_patient_reply_at')->nullable()->after('pipeline_status');
        $table->timestamp('last_cs_reply_at')->nullable()->after('last_patient_reply_at');
        $table->timestamp('last_follow_up_at')->nullable()->after('last_cs_reply_at');
        $table->tinyInteger('follow_up_step')->default(0)->after('last_follow_up_at');
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
