<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = ['line_id', 'vehicle_id', 'departure_time', 'status'];

    public function line()
    {
        return $this->belongsTo(Line::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
