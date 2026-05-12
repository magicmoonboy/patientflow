<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { loadStripe, type Stripe, type StripeElements, type StripePaymentElement } from '@stripe/stripe-js';

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

const isPaid = computed(() => props.appointment.status === 'confirmed');

const stripe = ref<Stripe | null>(null);
const elements = ref<StripeElements | null>(null);
const paymentElement = ref<StripePaymentElement | null>(null);
const paymentElementRef = ref<HTMLDivElement | null>(null);
const errorMessage = ref<string | null>(null);
const submitting = ref(false);
const initialised = ref(false);

const initStripe = async () => {
    if (isPaid.value) return;

    try {
        const { data } = await axios.post(route('payments.intent'), {
            appointment_id: props.appointment.id,
        });

        const stripeInstance = await loadStripe(data.publishable_key);
        if (!stripeInstance) throw new Error('Stripe.js failed to load');
        stripe.value = stripeInstance;

        elements.value = stripeInstance.elements({ clientSecret: data.client_secret });
        paymentElement.value = elements.value.create('payment');

        if (paymentElementRef.value) {
            paymentElement.value.mount(paymentElementRef.value);
        }

        initialised.value = true;
    } catch (e: any) {
        errorMessage.value = e?.response?.data?.message ?? e?.message ?? 'Kon Stripe niet initialiseren';
    }
};

const submit = async () => {
    if (!stripe.value || !elements.value) return;

    submitting.value = true;
    errorMessage.value = null;

    const { error } = await stripe.value.confirmPayment({
        elements: elements.value,
        confirmParams: {
            return_url: window.location.origin + route('patient.appointments.payment', props.appointment.id, false),
        },
        redirect: 'if_required',
    });

    submitting.value = false;

    if (error) {
        errorMessage.value = error.message ?? 'Betaling mislukt';
        return;
    }

    // Webhook will flip appointment to confirmed; reload to pick up new state.
    router.reload({ only: ['appointment'] });
};

onMounted(() => {
    initStripe();
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

                <div v-if="isPaid" class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-6 text-center">
                    <p class="text-lg font-semibold text-emerald-800">✓ Betaling ontvangen</p>
                    <p class="mt-1 text-sm text-emerald-700">Je afspraak is bevestigd.</p>
                </div>

                <div v-else class="rounded-lg bg-white p-6 shadow-sm">
                    <h4 class="font-semibold text-gray-900">Betaalgegevens</h4>
                    <p class="text-sm text-gray-500">Stripe test-modus · gebruik kaart 4242 4242 4242 4242, willekeurige CVC en toekomstige datum.</p>

                    <div ref="paymentElementRef" class="mt-4 min-h-[40px]">
                        <p v-if="!initialised" class="text-sm text-gray-400">Stripe wordt geladen...</p>
                    </div>

                    <p v-if="errorMessage" class="mt-3 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        {{ errorMessage }}
                    </p>

                    <button
                        type="button"
                        @click="submit"
                        :disabled="!initialised || submitting"
                        class="mt-4 w-full rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ submitting ? 'Bezig met betalen...' : `Betaal € ${feeEuros}` }}
                    </button>
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
