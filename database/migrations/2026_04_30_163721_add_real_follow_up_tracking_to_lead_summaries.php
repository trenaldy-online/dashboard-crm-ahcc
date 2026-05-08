<?php

   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up()
       {
           Schema::table('lead_summaries', function (Blueprint $table) {
               $table->timestamp('last_follow_up_sent_at')->nullable()->after('last_cs_reply_at');
               $table->integer('follow_up_sent_count')->default(0)->after('last_follow_up_sent_at');
               
               // Menghapus kolom step lama jika ada
               if (Schema::hasColumn('lead_summaries', 'follow_up_step')) {
                   $table->dropColumn('follow_up_step');
               }
           });
       }

       public function down()
       {
           Schema::table('lead_summaries', function (Blueprint $table) {
               $table->dropColumn(['last_follow_up_sent_at', 'follow_up_sent_count']);
               $table->integer('follow_up_step')->default(0);
           });
       }
   };