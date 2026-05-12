<?php

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\SpecialistProfile;
use App\Models\User;

function signStripePayload(string $payload, string $secret): array
{
    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return [
        'Stripe-Signature' => "t={$timestamp},v1={$signature}",
    ];
}

function makeAppointmentWithPayment(string $intentId): Payment
{
    $patient = User::factory()->create(['role' => 'patient']);
    $specialist = User::factory()->create(['role' => 'specialist']);
    SpecialistProfile::create([
        'user_id' => $specialist->id,
        'specialty' => 'Huisarts',
        'consultation_fee_cents' => 2500,
        'slot_duration_minutes' => 30,
    ]);

    $appointment = Appointment::create([
        'patient_user_id' => $patient->id,
        'specialist_user_id' => $specialist->id,
        'starts_at' => now()->addDays(2)->setTime(10, 0),
        'ends_at' => now()->addDays(2)->setTime(10, 30),
        'status' => Appointment::STATUS_PENDING_PAYMENT,
    ]);

    return Payment::create([
        'appointment_id' => $appointment->id,
        'stripe_payment_intent_id' => $intentId,
        'amount_cents' => 2500,
        'currency' => 'eur',
        'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
    ]);
}

beforeEach(function () {
    $this->secret = 'whsec_test_secret_123';
    config(['services.stripe.webhook_secret' => $this->secret]);
});

test('payment_intent.succeeded confirms appointment and marks payment succeeded', function () {
    $payment = makeAppointmentWithPayment('pi_test_success');

    $payload = json_encode([
        'id' => 'evt_test_1',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_test_success']],
    ]);

    $response = $this->call(
        'POST',
        '/webhooks/stripe',
        [], [], [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => signStripePayload($payload, $this->secret)['Stripe-Signature']],
        $payload
    );

    $response->assertStatus(200);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCEEDED);
    expect($payment->fresh()->appointment->status)->toBe(Appointment::STATUS_CONFIRMED);
});

test('payment_intent.payment_failed marks payment failed but keeps appointment pending', function () {
    $payment = makeAppointmentWithPayment('pi_test_fail');

    $payload = json_encode([
        'id' => 'evt_test_2',
        'object' => 'event',
        'type' => 'payment_intent.payment_failed',
        'data' => ['object' => ['id' => 'pi_test_fail']],
    ]);

    $response = $this->call(
        'POST',
        '/webhooks/stripe',
        [], [], [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => signStripePayload($payload, $this->secret)['Stripe-Signature']],
        $payload
    );

    $response->assertStatus(200);
    expect($payment->fresh()->status)->toBe(Payment::STATUS_FAILED);
    expect($payment->fresh()->appointment->status)->toBe(Appointment::STATUS_PENDING_PAYMENT);
});

test('webhook with invalid signature returns 400', function () {
    $payload = json_encode([
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_test_x']],
    ]);

    $response = $this->call(
        'POST',
        '/webhooks/stripe',
        [], [], [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 't=1,v1=clearlywrong'],
        $payload
    );

    $response->assertStatus(400);
});

test('duplicate succeeded webhook is idempotent', function () {
    $payment = makeAppointmentWithPayment('pi_test_dup');

    $payload = json_encode([
        'id' => 'evt_test_dup',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_test_dup']],
    ]);

    $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => signStripePayload($payload, $this->secret)['Stripe-Signature']];

    $this->call('POST', '/webhooks/stripe', [], [], [], $headers, $payload)->assertStatus(200);

    // Second call with fresh signature - should not error or double-update
    $headers2 = ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => signStripePayload($payload, $this->secret)['Stripe-Signature']];
    $this->call('POST', '/webhooks/stripe', [], [], [], $headers2, $payload)->assertStatus(200);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCEEDED);
});
