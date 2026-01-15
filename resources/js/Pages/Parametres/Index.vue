<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
</script>

<template>
    <Head title="Paramètres" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Paramètres
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Gestion des Rôles -->
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Gestion des Rôles
                                </h3>
                                <svg
                                    class="h-8 w-8 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Créez et gérez les rôles et permissions des utilisateurs.
                            </p>
                            <Link :href="route('parametres.roles.index')">
                                <PrimaryButton class="w-full">Gérer les rôles</PrimaryButton>
                            </Link>
                        </div>
                    </div>

                    <!-- Gestion des Utilisateurs -->
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Gestion des Utilisateurs
                                </h3>
                                <svg
                                    class="h-8 w-8 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Assignez des rôles aux utilisateurs et gérez leurs accès.
                            </p>
                            <Link :href="route('parametres.users.index')">
                                <PrimaryButton class="w-full">Gérer les utilisateurs</PrimaryButton>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
