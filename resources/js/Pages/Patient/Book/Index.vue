<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Specialist {
    id: number;
    name: string;
    specialty: string | null;
    bio: string | null;
    consultation_fee_cents: number | null;
    slot_duration_minutes: number;
}

const props = defineProps<{
    specialists: Specialist[];
    specialties: string[];
}>();

const activeSpecialty = ref<string | null>(null);

const filtered = computed(() =>
    activeSpecialty.value
        ? props.specialists.filter((s) => s.specialty === activeSpecialty.value)
        : props.specialists
);

const formatFee = (cents: number | null) => (cents ? `€ ${(cents / 100).toFixed(0)}` : '—');
</script>

<template>
    <Head title="Boek een specialist" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Boek een specialist
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="activeSpecialty = null"
                        class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
                        :class="activeSpecialty === null ? 'border-indigo-500 bg-indigo-500 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                    >
                        Alle ({{ specialists.length }})
                    </button>
                    <button
                        v-for="s in specialties"
                        :key="s"
                        type="button"
                        @click="activeSpecialty = s"
                        class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
                        :class="activeSpecialty === s ? 'border-indigo-500 bg-indigo-500 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                    >
                        {{ s }}
                    </button>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="specialist in filtered"
                        :key="specialist.id"
                        class="rounded-lg border bg-white p-5 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ specialist.name }}</h3>
                                <p class="text-sm text-indigo-600">{{ specialist.specialty }}</p>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                {{ formatFee(specialist.consultation_fee_cents) }}
                            </span>
                        </div>

                        <p class="mt-3 line-clamp-3 text-sm text-gray-600">{{ specialist.bio }}</p>

                        <Link
                            :href="route('patient.book.show', specialist.id)"
                            class="mt-4 block w-full rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Boek afspraak →
                        </Link>
                    </div>
                </div>

                <div v-if="filtered.length === 0" class="rounded-lg border border-dashed p-12 text-center text-gray-400">
                    Geen specialisten gevonden voor "{{ activeSpecialty }}"
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
