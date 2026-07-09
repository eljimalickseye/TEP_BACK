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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('license_plate')->unique();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('capacity')->default(14);
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        Schema::create('lines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('start_point');
            $table->string('end_point');
            $table->double('distance')->comment('Distance in km');
            $table->double('base_price');
            $table->timestamps();
        });

        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained('lines')->onDelete('cascade');
            $table->string('name');
            $table->double('latitude');
            $table->double('longitude');
            $table->integer('sequence');
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained('lines')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->dateTime('departure_time');
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('seat_number');
            $table->double('price');
            $table->string('ticket_code')->unique();
            $table->string('status')->default('booked'); // booked, used, cancelled
            $table->timestamps();
        });

        Schema::create('vehicle_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->double('latitude');
            $table->double('longitude');
            $table->double('speed')->nullable();
            $table->double('heading')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_positions');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('stops');
        Schema::dropIfExists('lines');
        Schema::dropIfExists('vehicles');
    }
};
