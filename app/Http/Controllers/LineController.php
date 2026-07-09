<?php

namespace App\Http\Controllers;

use App\Models\Line;
use App\Models\Trip;
use Illuminate\Http\Request;

class LineController extends Controller
{
    public function index()
    {
        return response(Line::with(['stops', 'gie'])->get(), 200);
    }

    public function show($id)
    {
        $line = Line::with(['stops', 'gie', 'trips.vehicle.driver'])->find($id);
        if (!$line) {
            return response(['message' => 'Ligne introuvable'], 404);
        }
        return response($line, 200);
    }

    public function getTrips(Request $request)
    {
        $trips = Trip::with(['line.stops', 'line.gie', 'vehicle.driver', 'tickets'])
            ->withCount('tickets')
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('departure_time', 'asc')
            ->get();

        return response($trips, 200);
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response(['message' => 'Non autorisé'], 403);
        }

        $fields = $request->validate([
            'name' => 'required|string',
            'start_point' => 'required|string',
            'end_point' => 'required|string',
            'distance' => 'required|numeric',
            'base_price' => 'required|numeric',
            'stops' => 'array',
            'stops.*.name' => 'required|string',
            'stops.*.latitude' => 'required|numeric',
            'stops.*.longitude' => 'required|numeric',
            'stops.*.sequence' => 'required|integer',
        ]);

        $line = Line::create([
            'name' => $fields['name'],
            'start_point' => $fields['start_point'],
            'end_point' => $fields['end_point'],
            'distance' => $fields['distance'],
            'base_price' => $fields['base_price'],
            'gie_id' => $request->user()->gie_id, // Scoped to admin's GIE
        ]);

        if (isset($fields['stops'])) {
            foreach ($fields['stops'] as $stopData) {
                $line->stops()->create($stopData);
            }
        }

        return response($line->load(['stops', 'gie']), 201);
    }
}
