<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SpecialistProfile {
    specialty: string;
    bio: string | null;
    consultation_fee_cents: number;
    slot_duration_minutes: number;
}

const props = defineProps<{
    profile: SpecialistProfile | null;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const feeEuros = computed(() =>
    props.profile ? (props.profile.consultation_fee_cents / 100).toFixed(2) : '0.00'
);
</script>

<template>
    <Head title="Specialist Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Specialist Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-4 inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800">
                            Rol: Specialist · {{ profile?.specialty ?? 'Onbekend' }}
                        </div>

                        <h3 class="text-2xl font-bold">Welkom, {{ user.name }}</h3>
                        <p class="mt-2 text-gray-600">
                            Consult-tarief: <span class="font-semibold">€ {{ feeEuros }}</span>
                            · Slot-duur: {{ profile?.slot_duration_minutes ?? 30 }} min
                        </p>

                        <div class="mt-6 rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-400">
                            Boekingen + omzet-widget volgen in PF-6
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
