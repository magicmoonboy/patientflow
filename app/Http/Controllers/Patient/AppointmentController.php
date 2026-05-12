<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'specialist_id' => ['required', 'integer', 'exists:users,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'intake_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $specialist = User::with('specialistProfile')->findOrFail($validated['specialist_id']);
        abort_unless($specialist->isSpecialist(), 422, 'Niet een specialist');

        $slotMinutes = $specialist->specialistProfile?->slot_duration_minutes ?? 30;
        $startsAt = CarbonImmutable::parse($validated['starts_at']);
        $endsAt = $startsAt->addMinutes($slotMinutes);

        $exists = Appointment::query()
            ->where('specialist_user_id', $specialist->id)
            ->where('starts_at', $startsAt)
            ->whereIn('status', [Appointment::STATUS_PENDING_PAYMENT, Appointment::STATUS_CONFIRMED])
            ->exists();

        if ($exists) {
            return back()->withErrors(['starts_at' => 'Dit tijdslot is al geboekt.']);
        }

        $appointment = Appointment::create([
            'patient_user_id' => Auth::id(),
            'specialist_user_id' => $specialist->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => Appointment::STATUS_PENDING_PAYMENT,
            'intake_notes' => $validated['intake_notes'] ?? null,
        ]);

        return redirect()->route('patient.appointments.payment', $appointment);
    }

    public function paymentPage(Appointment $appointment): Response
    {
        abort_unless($appointment->patient_user_id === Auth::id(), 403);
        $appointment->load(['specialist.specialistProfile', 'payment']);

        return Inertia::render('Patient/Appointment/Payment', [
            'appointment' => [
                'id' => $appointment->id,
                'starts_at' => $appointment->starts_at->toIso8601String(),
                'starts_at_label' => $appointment->starts_at->isoFormat('dddd D MMM YYYY · HH:mm'),
                'status' => $appointment->status,
                'intake_notes' => $appointment->intake_notes,
                'specialist' => [
                    'name' => $appointment->specialist->name,
                    'specialty' => $appointment->specialist->specialistProfile?->specialty,
                    'consultation_fee_cents' => $appointment->specialist->specialistProfile?->consultation_fee_cents,
                ],
                'payment' => $appointment->payment ? [
                    'status' => $appointment->payment->status,
                    'amount_cents' => $appointment->payment->amount_cents,
                ] : null,
            ],
        ]);
    }
}
