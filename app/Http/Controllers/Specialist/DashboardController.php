<?php

namespace App\Http\Controllers\Specialist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();
        $user = Auth::user()->load('specialistProfile');

        $appointments = Appointment::query()
            ->where('specialist_user_id', $userId)
            ->with(['patient', 'payment'])
            ->orderBy('starts_at')
            ->get();

        $upcoming = $appointments
            ->filter(fn ($a) => $a->starts_at->isFuture() && $a->status !== Appointment::STATUS_CANCELLED)
            ->values()
            ->map(fn ($a) => $this->formatAppointment($a));

        $past = $appointments
            ->filter(fn ($a) => ! $a->starts_at->isFuture() || $a->status === Appointment::STATUS_COMPLETED)
            ->sortByDesc('starts_at')
            ->values()
            ->map(fn ($a) => $this->formatAppointment($a));

        $monthRevenueCents = Payment::query()
            ->whereHas('appointment', fn ($q) => $q->where('specialist_user_id', $userId))
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount_cents');

        $monthAppointments = $appointments
            ->filter(fn ($a) => $a->status === Appointment::STATUS_CONFIRMED || $a->status === Appointment::STATUS_COMPLETED)
            ->filter(fn ($a) => $a->starts_at->isCurrentMonth())
            ->count();

        return Inertia::render('Specialist/Dashboard', [
            'profile' => $user->specialistProfile,
            'upcoming' => $upcoming,
            'past' => $past,
            'monthRevenueCents' => (int) $monthRevenueCents,
            'monthAppointments' => $monthAppointments,
        ]);
    }

    private function formatAppointment(Appointment $a): array
    {
        return [
            'id' => $a->id,
            'starts_at_label' => $a->starts_at->isoFormat('dddd D MMM · HH:mm'),
            'patient_name' => $a->patient->name,
            'status' => $a->status,
            'payment_status' => $a->payment?->status,
            'amount_cents' => $a->payment?->amount_cents,
            'intake_notes' => $a->intake_notes,
            'intake_summary' => $a->intake_summary,
        ];
    }
}
