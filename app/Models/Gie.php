<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gie extends Model
{
    protected $fillable = ['name', 'code', 'status'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function lines()
    {
        return $this->hasMany(Line::class);
    }
}
