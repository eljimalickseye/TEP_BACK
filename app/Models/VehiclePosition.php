<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiclePosition extends Model
{
    protected $fillable = ['vehicle_id', 'latitude', 'longitude', 'speed', 'heading'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
