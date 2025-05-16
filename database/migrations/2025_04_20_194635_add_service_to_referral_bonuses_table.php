<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('referral_bonuses', function (Blueprint $table) {
            $table->string('service')->after('bonus_amount');
        });
    }
    
   

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('referral_bonuses', function (Blueprint $table) {
            $table->dropColumn('service');
        });
    }
};
