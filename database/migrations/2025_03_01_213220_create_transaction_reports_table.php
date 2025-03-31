<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_reports', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('username');
            $table->string('status');
            $table->string('service_plan')->nullable();
            $table->string('amount');
            $table->string('transaction_id');
            $table->string('phone_number')->nullable();
            $table->string('smart_card_number')->nullable();
            $table->string('meter_number')->nullable();
            $table->string('quantity')->nullable();
            $table->string('electricity_token')->nullable();
            $table->string('balance_before')->nullable();
            $table->string('balance_after')->nullable();
            $table->string('updated_by');

            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_reports');
    }
};
