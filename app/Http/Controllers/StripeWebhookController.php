<?php

namespace App\Http\Controllers;

use App\Jobs\SummarizePatientIntakeJob;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature ?? '', $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature invalid', ['error' => $e->getMessage()]);

            return response('Invalid signature', 400);
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->handleSucceeded($event),
            'payment_intent.payment_failed' => $this->handleFailed($event),
            default => Log::info('Stripe webhook ignored event', ['type' => $event->type]),
        };

        return response('ok', 200);
    }

    private function handleSucceeded(Event $event): void
    {
        $intent = $event->data->object;
        $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();

        if (! $payment) {
            Log::warning('payment_intent.succeeded: no local Payment record', ['intent' => $intent->id]);

            return;
        }

        if ($payment->status === Payment::STATUS_SUCCEEDED) {
            return; // idempotent
        }

        $payment->update(['status' => Payment::STATUS_SUCCEEDED]);
        $payment->appointment()->update(['status' => Appointment::STATUS_CONFIRMED]);

        if ($payment->appointment->intake_notes && ! $payment->appointment->intake_summary) {
            SummarizePatientIntakeJob::dispatch($payment->appointment_id);
        }
    }

    private function handleFailed(Event $event): void
    {
        $intent = $event->data->object;
        $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();

        if (! $payment) {
            return;
        }

        $payment->update(['status' => Payment::STATUS_FAILED]);
        // Appointment stays in pending_payment so the patient can retry.
    }
}
