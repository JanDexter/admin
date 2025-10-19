<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'public_reservations';

    protected $fillable = [
        'space_type_id',
        'payment_method', // gcash | maya | cash
        'hours',
        'pax',
        'status', // paid | hold
        'hold_until',
        'notes',
    ];

    protected $casts = [
        'hold_until' => 'datetime',
        'hours' => 'integer',
        'pax' => 'integer',
    ];

    public function spaceType()
    {
        return $this->belongsTo(SpaceType::class);
    }
}
