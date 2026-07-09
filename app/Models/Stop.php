<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stop extends Model
{
    protected $fillable = ['line_id', 'name', 'latitude', 'longitude', 'sequence'];

    public function line()
    {
        return $this->belongsTo(Line::class);
    }
}
