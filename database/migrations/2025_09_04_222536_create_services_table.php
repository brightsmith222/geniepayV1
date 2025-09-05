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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_type', 50); // 'airtime', 'data', 'voucher'
            $table->string('provider_name', 50); // Network name like 'MTN', 'Airtel', 'Esim', etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate entries
            $table->unique(['service_type', 'provider_name'], 'services_type_provider_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
