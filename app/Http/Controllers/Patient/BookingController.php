<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(): Response
    {
        $specialists = User::query()
            ->where('role', 'specialist')
            ->with('specialistProfile')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'specialty' => $u->specialistProfile?->specialty,
                'bio' => $u->specialistProfile?->bio,
                'consultation_fee_cents' => $u->specialistProfile?->consultation_fee_cents,
                'slot_duration_minutes' => $u->specialistProfile?->slot_duration_minutes ?? 30,
            ])
            ->values();

        $specialties = $specialists->pluck('specialty')->filter()->unique()->values();

        return Inertia::render('Patient/Book/Index', [
            'specialists' => $specialists,
            'specialties' => $specialties,
        ]);
    }

    public function show(User $specialist): Response
    {
        abort_unless($specialist->isSpecialist(), 404);
        $specialist->load('specialistProfile');

        $slotMinutes = $specialist->specialistProfile?->slot_duration_minutes ?? 30;
        $start = CarbonImmutable::tomorrow()->setTime(9, 0);
        $days = 7;

        $bookedSlots = Appointment::query()
            ->where('specialist_user_id', $specialist->id)
            ->whereIn('status', [Appointment::STATUS_PENDING_PAYMENT, Appointment::STATUS_CONFIRMED])
            ->whereBetween('starts_at', [$start, $start->addDays($days)])
            ->pluck('starts_at')
            ->map(fn ($d) => $d->format('Y-m-d H:i'))
            ->toArray();

        $slotsByDay = [];
        for ($d = 0; $d < $days; $d++) {
            $dayStart = $start->addDays($d);
            $dayEnd = $dayStart->setTime(17, 0);
            $period = CarbonPeriod::create($dayStart, "$slotMinutes minutes", $dayEnd->subMinutes($slotMinutes));

            $slots = [];
            foreach ($period as $slot) {
                $key = $slot->format('Y-m-d H:i');
                $slots[] = [
                    'starts_at' => $slot->toIso8601String(),
                    'label' => $slot->format('H:i'),
                    'available' => ! in_array($key, $bookedSlots, true),
                ];
            }

            $slotsByDay[] = [
                'date' => $dayStart->toDateString(),
                'label' => $dayStart->isoFormat('dddd D MMM'),
                'slots' => $slots,
            ];
        }

        return Inertia::render('Patient/Book/Specialist', [
            'specialist' => [
                'id' => $specialist->id,
                'name' => $specialist->name,
                'specialty' => $specialist->specialistProfile?->specialty,
                'bio' => $specialist->specialistProfile?->bio,
                'consultation_fee_cents' => $specialist->specialistProfile?->consultation_fee_cents,
                'slot_duration_minutes' => $slotMinutes,
            ],
            'slotsByDay' => $slotsByDay,
        ]);
    }
}
