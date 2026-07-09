<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function book(Request $request)
    {
        $fields = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'seat_number' => 'integer|min:1',
        ]);

        $trip = Trip::with(['vehicle', 'line'])->find($fields['trip_id']);
        
        $bookedSeatsCount = Ticket::where('trip_id', $trip->id)
            ->where('status', 'booked')
            ->count();

        if ($bookedSeatsCount >= $trip->vehicle->capacity) {
            return response(['message' => 'Ce voyage est complet'], 400);
        }

        if (isset($fields['seat_number'])) {
            $seatNumber = $fields['seat_number'];
            $exists = Ticket::where('trip_id', $trip->id)
                ->where('seat_number', $seatNumber)
                ->where('status', 'booked')
                ->exists();
            if ($exists) {
                return response(['message' => 'Ce siège est déjà réservé'], 400);
            }
        } else {
            $seatNumber = $bookedSeatsCount + 1;
        }

        $ticket = Ticket::create([
            'trip_id' => $trip->id,
            'user_id' => $request->user()->id,
            'seat_number' => $seatNumber,
            'price' => $trip->line->base_price,
            'ticket_code' => 'TEP-' . strtoupper(Str::random(8)),
            'status' => 'booked'
        ]);

        return response($ticket->load('trip.line'), 201);
    }

    public function myTickets(Request $request)
    {
        $tickets = Ticket::with(['trip.line', 'trip.vehicle.driver'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response($tickets, 200);
    }

    public function scan(Request $request)
    {
        if (!in_array($request->user()->role, ['admin', 'driver'])) {
            return response(['message' => 'Non autorisé'], 403);
        }

        $fields = $request->validate([
            'ticket_code' => 'required|string',
        ]);

        $ticket = Ticket::with(['trip.line', 'user'])->where('ticket_code', $fields['ticket_code'])->first();

        if (!$ticket) {
            return response(['message' => 'Ticket invalide ou introuvable'], 404);
        }

        if ($ticket->status === 'used') {
            return response([
                'message' => 'Ce ticket a déjà été validé',
                'ticket' => $ticket
            ], 400);
        }

        if ($ticket->status === 'cancelled') {
            return response([
                'message' => 'Ce ticket a été annulé',
                'ticket' => $ticket
            ], 400);
        }

        $ticket->status = 'used';
        $ticket->save();

        return response([
            'message' => 'Ticket validé avec succès !',
            'ticket' => $ticket
        ], 200);
    }
}
