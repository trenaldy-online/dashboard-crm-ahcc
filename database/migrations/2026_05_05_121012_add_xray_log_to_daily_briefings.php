<?php

   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up()
       {
           Schema::table('daily_briefings', function (Blueprint $table) {
               $table->longText('xray_log')->nullable()->after('data_mentah');
           });
       }

       public function down()
       {
           Schema::table('daily_briefings', function (Blueprint $table) {
               $table->dropColumn('xray_log');
           });
       }
   };