<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Line;
use App\Models\Stop;
use App\Models\Trip;
use App\Models\Gie;
use App\Models\VehiclePosition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create GIEs
        $gieAftu = Gie::create([
            'name' => 'GIE AFTU Sénégal',
            'code' => 'AFTU',
        ]);

        $gieDdd = Gie::create([
            'name' => 'Dakar Dem Dikk (DDD)',
            'code' => 'DDD',
        ]);

        // 2. Create Users associated with GIEs
        $admin = User::create([
            'name' => 'Admin TepTep (AFTU)',
            'email' => 'admin@teptep.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'gie_id' => $gieAftu->id,
        ]);

        $driver1 = User::create([
            'name' => 'Abdoulaye Diouf (Chauffeur AFTU)',
            'email' => 'driver1@teptep.com',
            'password' => Hash::make('password'),
            'role' => 'driver',
            'gie_id' => $gieAftu->id,
        ]);

        $driver2 = User::create([
            'name' => 'Moussa Ndiaye (Chauffeur DDD)',
            'email' => 'driver2@teptep.com',
            'password' => Hash::make('password'),
            'role' => 'driver',
            'gie_id' => $gieDdd->id,
        ]);

        $client = User::create([
            'name' => 'Fatou Fall (Client)',
            'email' => 'client@teptep.com',
            'password' => Hash::make('password'),
            'role' => 'client',
        ]);

        // 3. Create Vehicles associated with GIEs
        $vehicle1 = Vehicle::create([
            'name' => "Mini-car Chang'An AFTU 36",
            'license_plate' => 'DK-1234-A',
            'driver_id' => $driver1->id,
            'capacity' => 14,
            'status' => 'active',
            'gie_id' => $gieAftu->id,
        ]);

        $vehicle2 = Vehicle::create([
            'name' => "Mini-car Chang'An DDD Express",
            'license_plate' => 'TH-5678-B',
            'driver_id' => $driver2->id,
            'capacity' => 45,
            'status' => 'active',
            'gie_id' => $gieDdd->id,
        ]);

        // 4. Create Transport Lines & Stops associated with GIEs
        // Line 1 belongs to Dakar Dem Dikk
        $line1 = Line::create([
            'name' => 'Dakar - Saint-Louis',
            'start_point' => 'Gare de Dakar',
            'end_point' => 'Gare de Saint-Louis',
            'distance' => 264.0,
            'base_price' => 5000.0,
            'gie_id' => $gieDdd->id,
        ]);

        $stopsLine1 = [
            ['name' => 'Gare de Dakar (Départ)', 'latitude' => 14.6937, 'longitude' => -17.4300, 'sequence' => 1],
            ['name' => 'Thiès (Escale)', 'latitude' => 14.7910, 'longitude' => -16.9298, 'sequence' => 2],
            ['name' => 'Tivaouane', 'latitude' => 14.9542, 'longitude' => -16.8122, 'sequence' => 3],
            ['name' => 'Kébémer', 'latitude' => 15.3708, 'longitude' => -16.4461, 'sequence' => 4],
            ['name' => 'Louga (Escale)', 'latitude' => 15.6186, 'longitude' => -16.2243, 'sequence' => 5],
            ['name' => 'Saint-Louis (Terminus)', 'latitude' => 16.0244, 'longitude' => -16.5019, 'sequence' => 6],
        ];

        foreach ($stopsLine1 as $stop) {
            $line1->stops()->create($stop);
        }

        // Line 2 belongs to AFTU
        $line2 = Line::create([
            'name' => 'Dakar - Mbour',
            'start_point' => 'Beaux Maraichers',
            'end_point' => 'Gare de Mbour',
            'distance' => 83.0,
            'base_price' => 2500.0,
            'gie_id' => $gieAftu->id,
        ]);

        $stopsLine2 = [
            ['name' => 'Beaux Maraichers (Départ)', 'latitude' => 14.7478, 'longitude' => -17.3917, 'sequence' => 1],
            ['name' => 'Rufisque', 'latitude' => 14.7153, 'longitude' => -17.2691, 'sequence' => 2],
            ['name' => 'Diamniadio', 'latitude' => 14.7078, 'longitude' => -17.2023, 'sequence' => 3],
            ['name' => 'Mbour (Terminus)', 'latitude' => 14.4220, 'longitude' => -16.9634, 'sequence' => 4],
        ];

        foreach ($stopsLine2 as $stop) {
            $line2->stops()->create($stop);
        }

        // 5. Create Trips (Schedules)
        // Trip 1 on Line 1 (Dakar - Saint-Louis) - Scheduled for today
        $trip1 = Trip::create([
            'line_id' => $line1->id,
            'vehicle_id' => $vehicle2->id, // Dakar Dem Dikk
            'departure_time' => Carbon::now()->addHours(2),
            'status' => 'scheduled',
        ]);

        // Trip 2 on Line 2 (Dakar - Mbour) - Running now
        $trip2 = Trip::create([
            'line_id' => $line2->id,
            'vehicle_id' => $vehicle1->id, // AFTU
            'departure_time' => Carbon::now()->subMinutes(30),
            'status' => 'in_progress',
        ]);

        // 6. Create initial positions for live tracking
        // Vehicle 1 is near Dakar Gare
        VehiclePosition::create([
            'vehicle_id' => $vehicle1->id,
            'latitude' => 14.7400,
            'longitude' => -17.3900,
            'speed' => 0.0,
            'heading' => 90.0,
        ]);

        // Vehicle 2 is en route to Mbour, near Rufisque
        VehiclePosition::create([
            'vehicle_id' => $vehicle2->id,
            'latitude' => 14.7160,
            'longitude' => -17.2500,
            'speed' => 65.0,
            'heading' => 120.0,
        ]);
    }
}
