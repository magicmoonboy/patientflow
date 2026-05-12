<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'patient' as 'patient' | 'specialist',
    specialty: '',
    consultation_fee_euros: '25',
});

const specialties = [
    'Huisarts',
    'Fysiotherapeut',
    'Psycholoog',
    'Diëtist',
    'Osteopaat',
    'Logopedist',
];

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registreer" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel value="Ik ben een..." />

                <div class="mt-2 grid grid-cols-2 gap-3">
                    <label
                        class="flex cursor-pointer items-center justify-center rounded-lg border px-4 py-3 text-sm font-medium transition"
                        :class="form.role === 'patient'
                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                            : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'"
                    >
                        <input v-model="form.role" type="radio" value="patient" class="sr-only" />
                        Patiënt
                    </label>
                    <label
                        class="flex cursor-pointer items-center justify-center rounded-lg border px-4 py-3 text-sm font-medium transition"
                        :class="form.role === 'specialist'
                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                            : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'"
                    >
                        <input v-model="form.role" type="radio" value="specialist" class="sr-only" />
                        Specialist
                    </label>
                </div>

                <InputError class="mt-2" :message="form.errors.role" />
            </div>

            <div class="mt-4">
                <InputLabel for="name" value="Naam" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="E-mail" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="form.role === 'specialist'" class="mt-4 space-y-4 rounded-lg border border-indigo-100 bg-indigo-50/50 p-4">
                <div>
                    <InputLabel for="specialty" value="Specialisme" />
                    <select
                        id="specialty"
                        v-model="form.specialty"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="" disabled>Kies een specialisme</option>
                        <option v-for="s in specialties" :key="s" :value="s">{{ s }}</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.specialty" />
                </div>

                <div>
                    <InputLabel for="fee" value="Consult-tarief (€)" />
                    <TextInput
                        id="fee"
                        type="number"
                        min="10"
                        max="500"
                        class="mt-1 block w-full"
                        v-model="form.consultation_fee_euros"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.consultation_fee_euros" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Wachtwoord" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Wachtwoord bevestigen" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-6 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900"
                >
                    Al een account?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Registreer
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
