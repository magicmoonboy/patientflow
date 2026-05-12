<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SpecialistProfile {
    specialty: string;
    bio: string | null;
    consultation_fee_cents: number;
    slot_duration_minutes: number;
}

interface AppointmentRow {
    id: number;
    starts_at_label: string;
    patient_name: string;
    status: string;
    payment_status: string | null;
    amount_cents: number | null;
    intake_notes: string | null;
    intake_summary: string | null;
}

const props = defineProps<{
    profile: SpecialistProfile | null;
    upcoming: AppointmentRow[];
    past: AppointmentRow[];
    monthRevenueCents: number;
    monthAppointments: number;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const monthRevenueLabel = computed(() => `€ ${(props.monthRevenueCents / 100).toFixed(2)}`);

const statusBadge = (status: string) => {
    const map: Record<string, { label: string; class: string }> = {
        pending_payment: { label: 'Wacht op betaling', class: 'bg-amber-100 text-amber-800' },
        confirmed: { label: 'Bevestigd', class: 'bg-emerald-100 text-emerald-800' },
        cancelled: { label: 'Geannuleerd', class: 'bg-red-100 text-red-800' },
        completed: { label: 'Voltooid', class: 'bg-gray-100 text-gray-700' },
    };
    return map[status] ?? { label: status, class: 'bg-gray-100 text-gray-700' };
};

const formatAmount = (cents: number | null) => (cents ? `€ ${(cents / 100).toFixed(2)}` : '—');

const completeForm = useForm({});
const markComplete = (id: number) => {
    completeForm.patch(route('specialist.appointments.complete', id), { preserveScroll: true });
};
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
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800">
                                {{ profile?.specialty }}
                            </div>
                            <h3 class="mt-2 text-2xl font-bold">{{ user.name }}</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Consult-tarief: € {{ ((profile?.consultation_fee_cents ?? 0) / 100).toFixed(2) }} · {{ profile?.slot_duration_minutes }} min
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Omzet deze maand</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-600">{{ monthRevenueLabel }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Afspraken deze maand</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ monthAppointments }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Komende afspraken</p>
                        <p class="mt-2 text-3xl font-bold text-indigo-600">{{ upcoming.length }}</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b p-6">
                        <h4 class="font-semibold text-gray-900">Komende afspraken</h4>
                    </div>

                    <div v-if="upcoming.length === 0" class="p-12 text-center text-sm text-gray-400">
                        Geen komende afspraken.
                    </div>

                    <table v-else class="min-w-full divide-y">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Datum & tijd</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Patiënt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Bedrag</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Klacht</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y bg-white">
                            <tr v-for="a in upcoming" :key="a.id">
                                <td class="px-6 py-4 text-sm font-medium capitalize text-gray-900">{{ a.starts_at_label }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ a.patient_name }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusBadge(a.status).class">
                                        {{ statusBadge(a.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ formatAmount(a.amount_cents) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div v-if="a.intake_summary" class="rounded bg-indigo-50 p-2 text-indigo-900">
                                        <p class="text-xs font-semibold uppercase text-indigo-600">AI-samenvatting</p>
                                        <p class="mt-1 whitespace-pre-line">{{ a.intake_summary }}</p>
                                    </div>
                                    <p v-else-if="a.intake_notes" class="line-clamp-3 italic text-gray-500">"{{ a.intake_notes }}"</p>
                                    <p v-else class="text-gray-300">—</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        v-if="a.status === 'confirmed'"
                                        type="button"
                                        :disabled="completeForm.processing"
                                        @click="markComplete(a.id)"
                                        class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                                    >
                                        Markeer voltooid
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <details class="rounded-lg bg-white shadow-sm">
                    <summary class="cursor-pointer p-6 font-semibold text-gray-900">
                        Eerdere afspraken ({{ past.length }})
                    </summary>
                    <div v-if="past.length === 0" class="p-6 pt-0 text-sm text-gray-400">
                        Nog niets in de historie.
                    </div>
                    <table v-else class="min-w-full divide-y border-t">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Datum</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Patiënt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Bedrag</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y bg-white">
                            <tr v-for="a in past" :key="a.id">
                                <td class="px-6 py-3 text-sm capitalize text-gray-700">{{ a.starts_at_label }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ a.patient_name }}</td>
                                <td class="px-6 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs" :class="statusBadge(a.status).class">
                                        {{ statusBadge(a.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ formatAmount(a.amount_cents) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </details>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
