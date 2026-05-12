<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $user = Auth::user();

    return $user->isSpecialist()
        ? redirect()->route('specialist.dashboard')
        : redirect()->route('patient.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'role:patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('/dashboard', function () {
        $appointments = \App\Models\Appointment::query()
            ->where('patient_user_id', Auth::id())
            ->with(['specialist.specialistProfile', 'payment'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'starts_at_label' => $a->starts_at->isoFormat('dddd D MMM · HH:mm'),
                'specialist_name' => $a->specialist->name,
                'specialty' => $a->specialist->specialistProfile?->specialty,
                'status' => $a->status,
                'payment_status' => $a->payment?->status,
            ]);

        return Inertia::render('Patient/Dashboard', [
            'appointments' => $appointments,
        ]);
    })->name('dashboard');

    Route::get('/book', [\App\Http\Controllers\Patient\BookingController::class, 'index'])->name('book.index');
    Route::get('/book/{specialist}', [\App\Http\Controllers\Patient\BookingController::class, 'show'])->name('book.show');
    Route::post('/appointments', [\App\Http\Controllers\Patient\AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}/payment', [\App\Http\Controllers\Patient\AppointmentController::class, 'paymentPage'])->name('appointments.payment');
});

Route::middleware(['auth', 'role:specialist'])->prefix('specialist')->name('specialist.')->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::user()->load('specialistProfile');

        return Inertia::render('Specialist/Dashboard', [
            'profile' => $user->specialistProfile,
        ]);
    })->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
