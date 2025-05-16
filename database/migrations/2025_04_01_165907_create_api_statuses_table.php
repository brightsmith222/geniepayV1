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
        Schema::create('api_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

  

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('api_statuses');
    }
};
