<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class PaymentController extends Controller
{
    public function createIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
        ]);

        $appointment = Appointment::with('specialist.specialistProfile', 'payment')
            ->findOrFail($validated['appointment_id']);

        abort_unless($appointment->patient_user_id === Auth::id(), 403);
        abort_if(
            $appointment->status !== Appointment::STATUS_PENDING_PAYMENT,
            422,
            'Deze afspraak is niet meer betaalbaar (status: '.$appointment->status.')'
        );

        $amountCents = (int) ($appointment->specialist->specialistProfile?->consultation_fee_cents ?? 0);
        abort_if($amountCents <= 0, 422, 'Geen geldig consult-tarief gevonden');

        $stripe = new StripeClient(config('services.stripe.secret'));

        // Reuse existing PaymentIntent if one was already created for this appointment.
        if ($appointment->payment && $appointment->payment->stripe_payment_intent_id) {
            $pi = $stripe->paymentIntents->retrieve($appointment->payment->stripe_payment_intent_id);
        } else {
            $pi = $stripe->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => 'eur',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'patient_user_id' => $appointment->patient_user_id,
                    'specialist_user_id' => $appointment->specialist_user_id,
                ],
            ]);

            Payment::updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'stripe_payment_intent_id' => $pi->id,
                    'amount_cents' => $amountCents,
                    'currency' => 'eur',
                    'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
                ]
            );
        }

        return response()->json([
            'client_secret' => $pi->client_secret,
            'amount_cents' => $amountCents,
            'publishable_key' => config('services.stripe.key'),
        ]);
    }
}
