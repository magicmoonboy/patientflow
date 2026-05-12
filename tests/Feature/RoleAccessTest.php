<?php

use App\Models\SpecialistProfile;
use App\Models\User;

function makePatient(): User
{
    return User::factory()->create(['role' => 'patient']);
}

function makeSpecialist(): User
{
    $user = User::factory()->create(['role' => 'specialist']);
    SpecialistProfile::create([
        'user_id' => $user->id,
        'specialty' => 'Fysiotherapeut',
        'consultation_fee_cents' => 3500,
        'slot_duration_minutes' => 30,
    ]);

    return $user;
}

test('patient is redirected from /dashboard to patient dashboard', function () {
    $patient = makePatient();
    $this->actingAs($patient)->get('/dashboard')->assertRedirect(route('patient.dashboard'));
});

test('specialist is redirected from /dashboard to specialist dashboard', function () {
    $specialist = makeSpecialist();
    $this->actingAs($specialist)->get('/dashboard')->assertRedirect(route('specialist.dashboard'));
});

test('patient cannot access specialist dashboard', function () {
    $patient = makePatient();
    $this->actingAs($patient)->get('/specialist/dashboard')->assertForbidden();
});

test('specialist cannot access patient dashboard', function () {
    $specialist = makeSpecialist();
    $this->actingAs($specialist)->get('/patient/dashboard')->assertForbidden();
});

test('guest is redirected to login from any role-gated route', function () {
    $this->get('/patient/dashboard')->assertRedirect('/login');
    $this->get('/specialist/dashboard')->assertRedirect('/login');
});
