<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Appointment {
    id: number;
    starts_at: string;
    starts_at_label: string;
    status: string;
    intake_notes: string | null;
    specialist: {
        name: string;
        specialty: string | null;
        consultation_fee_cents: number | null;
    };
    payment: {
        status: string;
        amount_cents: number;
    } | null;
}

const props = defineProps<{
    appointment: Appointment;
}>();

const feeEuros = computed(() =>
    props.appointment.specialist.consultation_fee_cents
        ? (props.appointment.specialist.consultation_fee_cents / 100).toFixed(2)
        : '0.00'
);

const statusBadge = computed(() => {
    if (props.appointment.status === 'confirmed') {
        return { label: 'Bevestigd', class: 'bg-emerald-100 text-emerald-800' };
    }
    if (props.appointment.status === 'pending_payment') {
        return { label: 'Wacht op betaling', class: 'bg-amber-100 text-amber-800' };
    }
    return { label: props.appointment.status, class: 'bg-gray-100 text-gray-800' };
});
</script>

<template>
    <Head title="Betaling" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Bevestig je afspraak
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Samenvatting</h3>
                        <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusBadge.class">
                            {{ statusBadge.label }}
                        </span>
                    </div>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between border-b pb-2">
                            <dt class="text-gray-500">Specialist</dt>
                            <dd class="font-medium text-gray-900">{{ appointment.specialist.name }}</dd>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <dt class="text-gray-500">Specialisme</dt>
                            <dd class="font-medium text-gray-900">{{ appointment.specialist.specialty }}</dd>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <dt class="text-gray-500">Wanneer</dt>
                            <dd class="font-medium capitalize text-gray-900">{{ appointment.starts_at_label }}</dd>
                        </div>
                        <div v-if="appointment.intake_notes" class="border-b pb-2">
                            <dt class="text-gray-500">Jouw klacht</dt>
                            <dd class="mt-1 text-gray-700">{{ appointment.intake_notes }}</dd>
                        </div>
                        <div class="flex justify-between text-base">
                            <dt class="font-semibold text-gray-900">Te betalen</dt>
                            <dd class="font-bold text-gray-900">€ {{ feeEuros }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-dashed border-indigo-300 bg-indigo-50/40 p-6 text-center">
                    <p class="font-medium text-indigo-700">Stripe betalingsformulier komt in PF-5</p>
                    <p class="mt-1 text-sm text-indigo-600/70">
                        Hier verschijnt straks de Stripe Elements-kaart met "Betaal € {{ feeEuros }}" knop
                    </p>
                </div>

                <Link
                    :href="route('patient.dashboard')"
                    class="block text-center text-sm text-gray-500 underline hover:text-gray-700"
                >
                    ← Terug naar dashboard
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
