<?php

use App\Jobs\SummarizePatientIntakeJob;
use App\Models\Appointment;
use App\Models\SpecialistProfile;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function makeAppointmentWithIntake(?string $intake): Appointment
{
    $patient = User::factory()->create(['role' => 'patient']);
    $specialist = User::factory()->create(['role' => 'specialist']);
    SpecialistProfile::create([
        'user_id' => $specialist->id,
        'specialty' => 'Huisarts',
        'consultation_fee_cents' => 2500,
        'slot_duration_minutes' => 30,
    ]);

    return Appointment::create([
        'patient_user_id' => $patient->id,
        'specialist_user_id' => $specialist->id,
        'starts_at' => now()->addDays(2)->setTime(10, 0),
        'ends_at' => now()->addDays(2)->setTime(10, 30),
        'status' => Appointment::STATUS_CONFIRMED,
        'intake_notes' => $intake,
    ]);
}

test('summary job stores Claude response on appointment', function () {
    config(['services.anthropic.api_key' => 'sk-ant-test']);

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'text', 'text' => "- Hoofdpijn al week\n- Vooral 's middags\n- Verwijzing naar neuroloog overwegen"],
            ],
        ], 200),
    ]);

    $appointment = makeAppointmentWithIntake('Al een week hoofdpijn, vooral s middags');

    SummarizePatientIntakeJob::dispatchSync($appointment->id);

    expect($appointment->fresh()->intake_summary)
        ->toContain('Hoofdpijn')
        ->toContain('neuroloog');
});

test('job no-ops when appointment has no intake_notes', function () {
    config(['services.anthropic.api_key' => 'sk-ant-test']);
    Http::fake();

    $appointment = makeAppointmentWithIntake(null);
    SummarizePatientIntakeJob::dispatchSync($appointment->id);

    expect($appointment->fresh()->intake_summary)->toBeNull();
    Http::assertNothingSent();
});

test('job no-ops when ANTHROPIC_API_KEY is not configured', function () {
    config(['services.anthropic.api_key' => null]);
    Http::fake();

    $appointment = makeAppointmentWithIntake('Some complaint');
    SummarizePatientIntakeJob::dispatchSync($appointment->id);

    expect($appointment->fresh()->intake_summary)->toBeNull();
    Http::assertNothingSent();
});

test('job is idempotent when summary already exists', function () {
    config(['services.anthropic.api_key' => 'sk-ant-test']);
    Http::fake();

    $appointment = makeAppointmentWithIntake('Some complaint');
    $appointment->update(['intake_summary' => 'already summarised']);

    SummarizePatientIntakeJob::dispatchSync($appointment->id);

    expect($appointment->fresh()->intake_summary)->toBe('already summarised');
    Http::assertNothingSent();
});
