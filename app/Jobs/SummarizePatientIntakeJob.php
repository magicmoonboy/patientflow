<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SummarizePatientIntakeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(public int $appointmentId) {}

    public function handle(): void
    {
        $appointment = Appointment::find($this->appointmentId);

        if (! $appointment || ! $appointment->intake_notes) {
            return;
        }

        if ($appointment->intake_summary) {
            return; // already summarised, idempotent
        }

        $apiKey = config('services.anthropic.api_key');

        if (! $apiKey) {
            Log::info('SummarizePatientIntakeJob skipped: ANTHROPIC_API_KEY not set', ['appointment' => $appointment->id]);

            return;
        }

        $prompt = "Vat deze patient-intake samen in 3 korte bullets voor de behandelend specialist. Schrijf in het Nederlands, gebruik klinische taal, geen aannames over diagnose. Hou het onder de 60 woorden totaal.\n\nIntake:\n".$appointment->intake_notes;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(20)->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 200,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            Log::warning('Claude API call failed', [
                'appointment' => $appointment->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $response->throw(); // job retries up to $tries
        }

        $summary = $response->json('content.0.text');

        if (! $summary) {
            Log::warning('Claude returned empty content', ['appointment' => $appointment->id]);

            return;
        }

        $appointment->update(['intake_summary' => trim($summary)]);
    }
}
