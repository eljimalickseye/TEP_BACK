<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehiclePosition;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function updatePosition(Request $request)
    {
        if ($request->user()->role !== 'driver') {
            return response(['message' => 'Non autorisé (Rôle chauffeur requis)'], 403);
        }

        $fields = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'speed' => 'numeric|nullable',
            'heading' => 'numeric|nullable',
        ]);

        $vehicle = Vehicle::where('driver_id', $request->user()->id)->first();

        if (!$vehicle) {
            return response(['message' => 'Aucun véhicule assigné à ce chauffeur'], 404);
        }

        $position = VehiclePosition::updateOrCreate(
            ['vehicle_id' => $vehicle->id],
            [
                'latitude' => $fields['latitude'],
                'longitude' => $fields['longitude'],
                'speed' => $fields['speed'] ?? null,
                'heading' => $fields['heading'] ?? null,
            ]
        );

        return response($position, 200);
    }

    public function getPositions(Request $request)
    {
        $positions = VehiclePosition::with(['vehicle.driver', 'vehicle.trips' => function($query) {
            $query->whereIn('status', ['in_progress', 'scheduled'])->with('line.stops');
        }])->get();

        return response($positions, 200);
    }
}
