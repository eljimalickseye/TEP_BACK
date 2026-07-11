<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

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
            ->whereIn('status', ['booked', 'used'])
            ->count();

        if ($bookedSeatsCount >= $trip->vehicle->capacity) {
            return response(['message' => 'Ce voyage est complet'], 400);
        }

        if (isset($fields['seat_number'])) {
            $seatNumber = $fields['seat_number'];
            $exists = Ticket::where('trip_id', $trip->id)
                ->where('seat_number', $seatNumber)
                ->whereIn('status', ['booked', 'used'])
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

    /**
     * Book a ticket and initiate an Intech Payment (Wave or Orange Money)
     */
    public function bookWithPayment(Request $request)
    {
        $fields = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'seat_number' => 'integer|min:1',
            'payment_method' => 'required|in:wave,orange',
            'phone' => 'required|string',
        ]);

        $trip = Trip::with(['vehicle', 'line'])->find($fields['trip_id']);
        
        $bookedSeatsCount = Ticket::where('trip_id', $trip->id)
            ->whereIn('status', ['booked', 'used'])
            ->count();

        if ($bookedSeatsCount >= $trip->vehicle->capacity) {
            return response(['message' => 'Ce voyage est complet'], 400);
        }

        if (isset($fields['seat_number'])) {
            $seatNumber = $fields['seat_number'];
            $exists = Ticket::where('trip_id', $trip->id)
                ->where('seat_number', $seatNumber)
                ->whereIn('status', ['booked', 'used'])
                ->exists();
            if ($exists) {
                return response(['message' => 'Ce siège est déjà réservé'], 400);
            }
        } else {
            $seatNumber = $bookedSeatsCount + 1;
        }

        $formattedPhone = $this->formatPhone($fields['phone']);
        
        // Generate a unique external transaction ID
        $userId = $request->user()->id;
        $timestamp = time();
        $random = rand(1000, 9999);
        $externalTransactionId = "TEP_{$userId}_{$trip->id}_{$timestamp}_{$random}";

        // Create the ticket in 'pending_payment' status
        $ticket = Ticket::create([
            'trip_id' => $trip->id,
            'user_id' => $userId,
            'seat_number' => $seatNumber,
            'price' => $trip->line->base_price,
            'ticket_code' => 'TEP-' . strtoupper(Str::random(8)),
            'status' => 'pending_payment',
            'external_transaction_id' => $externalTransactionId,
            'payment_method' => $fields['payment_method'],
            'payment_status' => 'pending',
        ]);

        // Call Intech API
        $codeService = $fields['payment_method'] === 'wave' 
            ? 'WAVE_SN_API_CASH_OUT'
            : 'ORANGE_SN_API_CASH_OUT';

        try {
            $response = Http::post('https://api.intech.sn/api-services/operation', [
                'phone' => $formattedPhone,
                'amount' => (int) $trip->line->base_price,
                'codeService' => $codeService,
                'externalTransactionId' => $externalTransactionId,
                'callbackUrl' => 'https://teptep-api.duckdns.org/teptep-api/api/payments/callback',
                'apiKey' => 'CE7ADB3E-57AC-4720-9A47-240DEE6F77DB',
                'sender' => 'TepTep',
                'successRedirectUrl' => 'https://teptep-d.web.app/tickets',
                'errorRedirectUrl' => 'https://teptep-d.web.app/tickets',
                'data' => [
                    'userId' => (string) $userId,
                    'tripId' => (string) $trip->id,
                    'ticketCode' => $ticket->ticket_code,
                    'type' => 'ticket_booking'
                ]
            ]);

            $result = $response->json();

            if ($response->failed() || (isset($result['error']) && $result['error'] === true)) {
                $ticket->status = 'cancelled';
                $ticket->payment_status = 'failed';
                $ticket->save();
                
                $errMsg = $result['msg'] ?? 'Erreur d\'initialisation du paiement';
                return response(['message' => 'Erreur Intech: ' . $errMsg], 400);
            }

            // Return deepLinkUrl or authLinkUrl along with the ticket
            return response([
                'ticket' => $ticket->load('trip.line'),
                'payment' => $result['data'] ?? null
            ], 201);

        } catch (\Exception $e) {
            $ticket->status = 'cancelled';
            $ticket->payment_status = 'failed';
            $ticket->save();
            return response(['message' => 'Impossible de contacter le service de paiement: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Intech Payment Callback handler
     */
    public function paymentsCallback(Request $request)
    {
        \Log::info('Intech Payment Callback Received', $request->all());

        $data = $request->input('data') ?? $request->all();
        
        $externalTransactionId = $data['externalTransactionId'] ?? null;
        $status = $data['status'] ?? null;

        if (!$externalTransactionId) {
            return response(['message' => 'Missing transaction ID'], 400);
        }

        $ticket = Ticket::where('external_transaction_id', $externalTransactionId)->first();

        if (!$ticket) {
            return response(['message' => 'Ticket not found'], 404);
        }

        if ($status === 'SUCCESS') {
            $ticket->payment_status = 'success';
            $ticket->status = 'booked'; // Mark valid/paid
            $ticket->save();
            return response(['message' => 'Payment processed successfully'], 200);
        } elseif ($status === 'FAILED' || $status === 'FAILLED' || $status === 'CANCELED') {
            $ticket->payment_status = 'failed';
            $ticket->status = 'cancelled';
            $ticket->save();
            return response(['message' => 'Payment failed'], 200);
        }

        return response(['message' => 'Status pending or unhandled'], 200);
    }

    /**
     * Check payment transaction status dynamically
     */
    public function checkPaymentStatus($externalTransactionId)
    {
        $ticket = Ticket::with('trip.line')->where('external_transaction_id', $externalTransactionId)->first();

        if (!$ticket) {
            return response(['message' => 'Ticket introuvable'], 404);
        }

        // If local status is already success, return immediately
        if ($ticket->payment_status === 'success') {
            return response([
                'status' => 'SUCCESS',
                'ticket' => $ticket
            ], 200);
        }

        if ($ticket->payment_status === 'failed') {
            return response([
                'status' => 'FAILED',
                'ticket' => $ticket
            ], 200);
        }

        // Call Intech status API
        try {
            $response = Http::post('https://api.intech.sn/api-services/get-transaction-status', [
                'externalTransactionId' => $externalTransactionId,
                'apiKey' => 'CE7ADB3E-57AC-4720-9A47-240DEE6F77DB'
            ]);

            $result = $response->json();
            
            if ($response->successful() && isset($result['data'])) {
                $status = $result['data']['status'] ?? 'PENDING';

                if ($status === 'SUCCESS') {
                    $ticket->payment_status = 'success';
                    $ticket->status = 'booked';
                    $ticket->save();
                } elseif ($status === 'FAILED' || $status === 'FAILLED' || $status === 'CANCELED') {
                    $ticket->payment_status = 'failed';
                    $ticket->status = 'cancelled';
                    $ticket->save();
                }

                return response([
                    'status' => $status === 'FAILLED' ? 'FAILED' : $status,
                    'ticket' => $ticket->fresh('trip.line')
                ], 200);
            }
        } catch (\Exception $e) {
            // Ignore API exceptions and return the current local status
        }

        return response([
            'status' => 'PENDING',
            'ticket' => $ticket
        ], 200);
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

    public function allTickets(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response(['message' => 'Non autorisé'], 403);
        }

        $tickets = Ticket::with(['trip.line', 'user'])->orderBy('created_at', 'desc')->get();
        return response($tickets, 200);
    }

    /**
     * Format Senegalese phone number to Intech standard
     */
    private function formatPhone($phone)
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (str_starts_with($clean, '221')) {
            $clean = substr($clean, 3);
        }
        if (str_starts_with($clean, '0')) {
            $clean = substr($clean, 1);
        }
        return substr($clean, -9);
    }
}
