<?php

use App\Models\Appointment;
use App\Models\SpecialistProfile;
use App\Models\User;

beforeEach(function () {
    $this->patient = User::factory()->create(['role' => 'patient']);
    $this->specialist = User::factory()->create(['role' => 'specialist']);
    SpecialistProfile::create([
        'user_id' => $this->specialist->id,
        'specialty' => 'Huisarts',
        'consultation_fee_cents' => 2500,
        'slot_duration_minutes' => 30,
    ]);
});

test('patient can list specialists at /patient/book', function () {
    $this->actingAs($this->patient)
        ->get('/patient/book')
        ->assertStatus(200);
});

test('patient can view a specific specialist slot picker', function () {
    $this->actingAs($this->patient)
        ->get("/patient/book/{$this->specialist->id}")
        ->assertStatus(200);
});

test('patient can create an appointment for an available slot', function () {
    $startsAt = now()->addDays(2)->setTime(10, 0);

    $response = $this->actingAs($this->patient)->post('/patient/appointments', [
        'specialist_id' => $this->specialist->id,
        'starts_at' => $startsAt->toIso8601String(),
        'intake_notes' => 'Hoofdpijn al een week',
    ]);

    $appointment = Appointment::first();

    expect($appointment)->not->toBeNull();
    expect($appointment->patient_user_id)->toBe($this->patient->id);
    expect($appointment->specialist_user_id)->toBe($this->specialist->id);
    expect($appointment->status)->toBe(Appointment::STATUS_PENDING_PAYMENT);
    expect($appointment->intake_notes)->toBe('Hoofdpijn al een week');

    $response->assertRedirect(route('patient.appointments.payment', $appointment));
});

test('double booking on the same slot is rejected', function () {
    $startsAt = now()->addDays(2)->setTime(10, 0);

    $this->actingAs($this->patient)->post('/patient/appointments', [
        'specialist_id' => $this->specialist->id,
        'starts_at' => $startsAt->toIso8601String(),
    ]);

    $second = $this->actingAs($this->patient)->from('/patient/book')->post('/patient/appointments', [
        'specialist_id' => $this->specialist->id,
        'starts_at' => $startsAt->toIso8601String(),
    ]);

    $second->assertSessionHasErrors(['starts_at']);
    expect(Appointment::count())->toBe(1);
});

test('guest cannot create an appointment', function () {
    $this->post('/patient/appointments', [
        'specialist_id' => $this->specialist->id,
        'starts_at' => now()->addDays(2)->setTime(10, 0)->toIso8601String(),
    ])->assertRedirect('/login');
});
