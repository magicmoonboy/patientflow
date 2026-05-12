<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    public function isSpecialist(): bool
    {
        return $this->role === 'specialist';
    }

    public function specialistProfile(): HasOne
    {
        return $this->hasOne(SpecialistProfile::class);
    }

    public function patientAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_user_id');
    }

    public function specialistAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'specialist_user_id');
    }
}
