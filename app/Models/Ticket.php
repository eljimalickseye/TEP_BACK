<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = ['trip_id', 'user_id', 'seat_number', 'price', 'ticket_code', 'status', 'external_transaction_id', 'payment_method', 'payment_status'];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
