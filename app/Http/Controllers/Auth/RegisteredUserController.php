<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SpecialistProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in(['patient', 'specialist'])],
            'specialty' => ['required_if:role,specialist', 'nullable', 'string', 'max:100'],
            'consultation_fee_euros' => ['required_if:role,specialist', 'nullable', 'integer', 'min:10', 'max:500'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            if ($validated['role'] === 'specialist') {
                SpecialistProfile::create([
                    'user_id' => $user->id,
                    'specialty' => $validated['specialty'],
                    'consultation_fee_cents' => $validated['consultation_fee_euros'] * 100,
                    'slot_duration_minutes' => 30,
                ]);
            }

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect($user->isSpecialist() ? route('specialist.dashboard') : route('patient.dashboard'));
    }
}
