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
    Schema::table('transaction_reports', function (Blueprint $table) {
        $table->string('updated_by')->nullable()->after('balance_after');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_reports', function (Blueprint $table) {
            //
        });
    }
};
