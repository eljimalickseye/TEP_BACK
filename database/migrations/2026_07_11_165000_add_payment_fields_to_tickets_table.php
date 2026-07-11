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
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('external_transaction_id')->nullable()->unique()->after('ticket_code');
            $table->string('payment_method')->nullable()->after('external_transaction_id');
            $table->string('payment_status')->default('pending')->after('payment_method'); // pending, success, failed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['external_transaction_id', 'payment_method', 'payment_status']);
        });
    }
};
