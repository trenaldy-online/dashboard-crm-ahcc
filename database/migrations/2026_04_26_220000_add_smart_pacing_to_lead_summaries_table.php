<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('lead_summaries', function (Blueprint $table) {
        $table->integer('follow_up_count')->default(0)->after('topik_follow_up');
        $table->date('tunda_sampai_tanggal')->nullable()->after('follow_up_count');
    });
}

public function down(): void
{
    Schema::table('lead_summaries', function (Blueprint $table) {
        $table->dropColumn(['follow_up_count', 'tunda_sampai_tanggal']);
    });
}
};
