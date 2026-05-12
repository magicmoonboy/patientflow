<?php

namespace Database\Seeders;

use App\Models\SpecialistProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $patient = User::create([
            'name' => 'Demo Patiënt',
            'email' => 'patient@patientflow.test',
            'password' => Hash::make('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        $specialists = [
            [
                'name' => 'Dr. Anna de Vries',
                'email' => 'anna@patientflow.test',
                'specialty' => 'Huisarts',
                'bio' => 'Huisarts met 12 jaar ervaring, gespecialiseerd in eerstelijnszorg en chronische aandoeningen.',
                'fee' => 2500,
            ],
            [
                'name' => 'Drs. Pieter Jansen',
                'email' => 'pieter@patientflow.test',
                'specialty' => 'Fysiotherapeut',
                'bio' => 'Fysiotherapeut gericht op sportblessures, herstel na operaties en rugklachten.',
                'fee' => 3500,
            ],
            [
                'name' => 'Dr. Sara Bakker',
                'email' => 'sara@patientflow.test',
                'specialty' => 'Psycholoog',
                'bio' => 'GZ-psycholoog met focus op cognitieve gedragstherapie en burnout-preventie.',
                'fee' => 7500,
            ],
        ];

        foreach ($specialists as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => 'specialist',
                'email_verified_at' => now(),
            ]);

            SpecialistProfile::create([
                'user_id' => $user->id,
                'specialty' => $data['specialty'],
                'bio' => $data['bio'],
                'consultation_fee_cents' => $data['fee'],
                'slot_duration_minutes' => 30,
            ]);
        }
    }
}
