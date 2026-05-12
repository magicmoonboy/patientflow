<?php

use App\Models\SpecialistProfile;
use App\Models\User;

test('registration screen renders', function () {
    $this->get('/register')->assertStatus(200);
});

test('patient can register and lands on patient dashboard', function () {
    $response = $this->post('/register', [
        'name' => 'Test Patient',
        'email' => 'patient@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'patient',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('patient.dashboard'));

    $user = User::where('email', 'patient@example.test')->first();
    expect($user->role)->toBe('patient');
    expect($user->specialistProfile)->toBeNull();
});

test('specialist registration creates specialist_profile', function () {
    $response = $this->post('/register', [
        'name' => 'Dr. Spec',
        'email' => 'spec@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'specialist',
        'specialty' => 'Huisarts',
        'consultation_fee_euros' => 45,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('specialist.dashboard'));

    $user = User::where('email', 'spec@example.test')->first();
    expect($user->role)->toBe('specialist');
    expect($user->specialistProfile)->not->toBeNull();
    expect($user->specialistProfile->specialty)->toBe('Huisarts');
    expect($user->specialistProfile->consultation_fee_cents)->toBe(4500);
});

test('specialist registration without specialty fails validation', function () {
    $response = $this->post('/register', [
        'name' => 'Dr. NoSpec',
        'email' => 'nospec@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'specialist',
    ]);

    $response->assertSessionHasErrors(['specialty', 'consultation_fee_euros']);
    expect(User::where('email', 'nospec@example.test')->exists())->toBeFalse();
});
