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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('gie_id')->nullable()->constrained('gies')->nullOnDelete();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('gie_id')->nullable()->constrained('gies')->cascadeOnDelete();
        });

        Schema::table('lines', function (Blueprint $table) {
            $table->foreignId('gie_id')->nullable()->constrained('gies')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lines', function (Blueprint $table) {
            $table->dropForeign(['gie_id']);
            $table->dropColumn('gie_id');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['gie_id']);
            $table->dropColumn('gie_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['gie_id']);
            $table->dropColumn('gie_id');
        });
    }
};
