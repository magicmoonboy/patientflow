<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Appointment {
    id: number;
    starts_at_label: string;
    specialist_name: string;
    specialty: string | null;
    status: string;
    payment_status: string | null;
}

defineProps<{
    appointments: Appointment[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const statusLabel = (status: string) => {
    const map: Record<string, string> = {
        pending_payment: 'Wacht op betaling',
        confirmed: 'Bevestigd',
        cancelled: 'Geannuleerd',
        completed: 'Voltooid',
    };
    return map[status] ?? status;
};

const statusClass = (status: string) => {
    const map: Record<string, string> = {
        pending_payment: 'bg-amber-100 text-amber-800',
        confirmed: 'bg-emerald-100 text-emerald-800',
        cancelled: 'bg-red-100 text-red-800',
        completed: 'bg-gray-100 text-gray-800',
    };
    return map[status] ?? 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Patiënt Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Mijn afspraken
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between p-6">
                        <div>
                            <h3 class="text-2xl font-bold">Welkom, {{ user.name }}</h3>
                            <p class="mt-1 text-gray-600">
                                Beheer je afspraken of boek een nieuwe.
                            </p>
                        </div>
                        <Link
                            :href="route('patient.book.index')"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            + Nieuwe afspraak
                        </Link>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-900">Komende afspraken</h4>

                        <div v-if="appointments.length === 0" class="mt-4 rounded-lg border border-dashed p-12 text-center text-gray-400">
                            Nog geen afspraken. Klik op "Nieuwe afspraak" om er een te boeken.
                        </div>

                        <ul v-else class="mt-4 divide-y">
                            <li v-for="a in appointments" :key="a.id" class="flex items-center justify-between py-3">
                                <div>
                                    <p class="font-medium capitalize text-gray-900">{{ a.starts_at_label }}</p>
                                    <p class="text-sm text-gray-500">{{ a.specialist_name }} · {{ a.specialty }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusClass(a.status)">
                                        {{ statusLabel(a.status) }}
                                    </span>
                                    <Link
                                        v-if="a.status === 'pending_payment'"
                                        :href="route('patient.appointments.payment', a.id)"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-700"
                                    >
                                        Betaal →
                                    </Link>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
