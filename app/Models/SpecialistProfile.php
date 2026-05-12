<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialistProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialty',
        'bio',
        'consultation_fee_cents',
        'slot_duration_minutes',
    ];

    protected $casts = [
        'consultation_fee_cents' => 'integer',
        'slot_duration_minutes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getConsultationFeeEurosAttribute(): float
    {
        return $this->consultation_fee_cents / 100;
    }
}
