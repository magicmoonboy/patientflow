<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Patiënt Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Patiënt Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-4 inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800">
                            Rol: Patiënt
                        </div>

                        <h3 class="text-2xl font-bold">Welkom, {{ user.name }}</h3>
                        <p class="mt-2 text-gray-600">
                            Vanaf hier kun je een specialist boeken en je afspraken beheren.
                        </p>

                        <div class="mt-6 rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-400">
                            Boekingsflow volgt in PF-4
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
