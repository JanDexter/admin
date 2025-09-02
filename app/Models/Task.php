<?php

namespace App\Models;

// Deprecated Task model (Task Tracker feature removed). Retained temporarily to avoid migration issues.
// Safe to remove after confirming no production data reliance.

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'customer_id',
        'user_id',
        'status',
        'priority',
        'due_date',
        'completed_at'
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
