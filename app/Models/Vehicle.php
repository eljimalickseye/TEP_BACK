<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = ['name', 'license_plate', 'driver_id', 'capacity', 'status', 'gie_id'];

    public function gie()
    {
        return $this->belongsTo(Gie::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function latestPosition()
    {
        return $this->hasOne(VehiclePosition::class)->latestOfMany();
    }

    public function positions()
    {
        return $this->hasMany(VehiclePosition::class);
    }
}
