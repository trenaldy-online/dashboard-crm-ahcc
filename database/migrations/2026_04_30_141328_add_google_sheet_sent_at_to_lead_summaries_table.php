<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('lead_summaries', function (Blueprint $table) {
        $table->timestamp('google_sheet_sent_at')->nullable()->after('gclid');
    });
}

public function down()
{
    Schema::table('lead_summaries', function (Blueprint $table) {
        $table->dropColumn('google_sheet_sent_at');
    });
}
};
