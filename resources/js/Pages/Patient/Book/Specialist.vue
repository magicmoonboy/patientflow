<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Slot {
    starts_at: string;
    label: string;
    available: boolean;
}

interface DaySlots {
    date: string;
    label: string;
    slots: Slot[];
}

interface SpecialistInfo {
    id: number;
    name: string;
    specialty: string | null;
    bio: string | null;
    consultation_fee_cents: number | null;
    slot_duration_minutes: number;
}

const props = defineProps<{
    specialist: SpecialistInfo;
    slotsByDay: DaySlots[];
}>();

const selectedSlot = ref<string | null>(null);
const intakeNotes = ref<string>('');

const form = useForm({
    specialist_id: props.specialist.id,
    starts_at: '',
    intake_notes: '',
});

const selectedSlotLabel = computed(() => {
    if (!selectedSlot.value) return null;
    for (const day of props.slotsByDay) {
        const slot = day.slots.find((s) => s.starts_at === selectedSlot.value);
        if (slot) return `${day.label} · ${slot.label}`;
    }
    return null;
});

const submit = () => {
    if (!selectedSlot.value) return;
    form.starts_at = selectedSlot.value;
    form.intake_notes = intakeNotes.value;
    form.post(route('patient.appointments.store'));
};

const feeEuros = computed(() =>
    props.specialist.consultation_fee_cents
        ? (props.specialist.consultation_fee_cents / 100).toFixed(0)
        : '—'
);
</script>

<template>
    <Head :title="`Boek ${specialist.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Boek bij {{ specialist.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-indigo-600">{{ specialist.specialty }}</p>
                            <h3 class="mt-1 text-2xl font-bold">{{ specialist.name }}</h3>
                            <p class="mt-2 max-w-2xl text-sm text-gray-600">{{ specialist.bio }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-50 px-4 py-2 text-right">
                            <p class="text-xs uppercase tracking-wide text-emerald-700">Consult</p>
                            <p class="text-xl font-bold text-emerald-700">€ {{ feeEuros }}</p>
                            <p class="text-xs text-emerald-700/70">{{ specialist.slot_duration_minutes }} min</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h4 class="font-semibold text-gray-900">Kies een tijdslot</h4>
                    <p class="text-sm text-gray-500">Komende 7 dagen · 09:00 - 17:00</p>

                    <div class="mt-4 space-y-4">
                        <div v-for="day in slotsByDay" :key="day.date">
                            <p class="mb-2 text-sm font-medium capitalize text-gray-700">{{ day.label }}</p>
                            <div class="grid grid-cols-4 gap-2 sm:grid-cols-8">
                                <button
                                    v-for="slot in day.slots"
                                    :key="slot.starts_at"
                                    type="button"
                                    :disabled="!slot.available"
                                    @click="selectedSlot = slot.starts_at"
                                    class="rounded-md border px-2 py-2 text-sm font-medium transition"
                                    :class="[
                                        !slot.available && 'cursor-not-allowed border-gray-100 bg-gray-50 text-gray-300 line-through',
                                        slot.available && selectedSlot === slot.starts_at && 'border-indigo-500 bg-indigo-500 text-white',
                                        slot.available && selectedSlot !== slot.starts_at && 'border-gray-200 bg-white text-gray-700 hover:border-indigo-400',
                                    ]"
                                >
                                    {{ slot.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedSlot" class="rounded-lg border border-indigo-200 bg-indigo-50/40 p-6">
                    <p class="text-sm text-indigo-700">Geselecteerd: <span class="font-semibold capitalize">{{ selectedSlotLabel }}</span></p>

                    <div class="mt-4">
                        <label for="intake" class="block text-sm font-medium text-gray-700">
                            Wat is je klacht? <span class="font-normal text-gray-400">(optioneel)</span>
                        </label>
                        <textarea
                            id="intake"
                            v-model="intakeNotes"
                            rows="3"
                            maxlength="2000"
                            placeholder="Bv. al een week last van hoofdpijn, vooral 's middags..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <p v-if="form.errors.intake_notes" class="mt-1 text-sm text-red-600">{{ form.errors.intake_notes }}</p>
                    </div>

                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Bevestig en ga door naar betaling →
                    </button>
                    <p v-if="form.errors.starts_at" class="mt-2 text-sm text-red-600">{{ form.errors.starts_at }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
