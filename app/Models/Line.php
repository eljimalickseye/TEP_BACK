<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Line extends Model
{
    protected $fillable = ['name', 'start_point', 'end_point', 'distance', 'base_price', 'gie_id'];

    public function gie()
    {
        return $this->belongsTo(Gie::class);
    }

    public function stops()
    {
        return $this->hasMany(Stop::class)->orderBy('sequence');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
