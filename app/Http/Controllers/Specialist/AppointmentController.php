<?php

namespace App\Http\Controllers\Specialist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function complete(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->specialist_user_id === Auth::id(), 403);

        if (! in_array($appointment->status, [Appointment::STATUS_CONFIRMED, Appointment::STATUS_PENDING_PAYMENT], true)) {
            return back()->withErrors(['status' => 'Afspraak kan niet als voltooid worden gemarkeerd.']);
        }

        $appointment->update(['status' => Appointment::STATUS_COMPLETED]);

        return back();
    }
}
