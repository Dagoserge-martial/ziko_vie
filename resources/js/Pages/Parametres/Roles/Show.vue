<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    role: Object,
    permissions: Object,
});
</script>

<template>
    <Head :title="`Rôle - ${role.nom}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Détails du Rôle
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('parametres.roles.index')">
                        <SecondaryButton>Retour</SecondaryButton>
                    </Link>
                    <Link :href="route('parametres.roles.edit', role.id)">
                        <PrimaryButton>Modifier</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="space-y-6">
                    <!-- Informations générales -->
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                Informations générales
                            </h3>
                            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Nom</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ role.nom }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Slug</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ role.slug }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ role.description || '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Statut</dt>
                                    <dd class="mt-1">
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800': role.actif,
                                                'bg-red-100 text-red-800': !role.actif,
                                            }"
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        >
                                            {{ role.actif ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Nombre d'utilisateurs</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ role.utilisateurs?.length || 0 }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                Permissions
                            </h3>
                            <div v-if="role.permissions && role.permissions.length > 0" class="space-y-2">
                                <div
                                    v-for="permission in role.permissions"
                                    :key="permission"
                                    class="flex items-center text-sm text-gray-700"
                                >
                                    <svg
                                        class="h-5 w-5 text-green-500 me-2"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ permissions[permission] || permission }}
                                </div>
                            </div>
                            <p v-else class="text-sm text-gray-500">Aucune permission assignée</p>
                        </div>
                    </div>

                    <!-- Utilisateurs avec ce rôle -->
                    <div v-if="role.utilisateurs && role.utilisateurs.length > 0" class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                Utilisateurs avec ce rôle
                            </h3>
                            <div class="space-y-2">
                                <div
                                    v-for="user in role.utilisateurs"
                                    :key="user.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                >
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ user.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
