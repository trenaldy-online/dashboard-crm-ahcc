<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('lead_summaries', function (Blueprint $table) {
        $table->boolean('perlu_follow_up')->default(false)->after('pipeline_status');
        $table->string('alasan_follow_up')->nullable()->after('perlu_follow_up');
        $table->text('topik_follow_up')->nullable()->after('alasan_follow_up');
    });
}

public function down(): void
{
    Schema::table('lead_summaries', function (Blueprint $table) {
        $table->dropColumn(['perlu_follow_up', 'alasan_follow_up', 'topik_follow_up']);
    });
}
};
