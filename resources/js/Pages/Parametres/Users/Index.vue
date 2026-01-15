<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    users: Array,
    roles: Array,
});

const assignRole = (user, roleId) => {
    router.post(
        route('parametres.users.assign-role', user.id),
        { role_id: roleId },
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Succès', 'Le rôle a été assigné avec succès.', 'success');
            },
            onError: () => {
                Swal.fire('Erreur', 'Une erreur est survenue.', 'error');
            },
        }
    );
};

const toggleBlock = (user) => {
    const action = user.est_bloque ? 'débloquer' : 'bloquer';
    Swal.fire({
        title: `Êtes-vous sûr de vouloir ${action} cet utilisateur ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Oui, ${action}`,
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(
                route('parametres.users.toggle-block', user.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Succès', `L'utilisateur a été ${action}é avec succès.`, 'success');
                    },
                    onError: () => {
                        Swal.fire('Erreur', 'Une erreur est survenue.', 'error');
                    },
                }
            );
        }
    });
};
</script>

<template>
    <Head title="Gestion des Utilisateurs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Gestion des Utilisateurs
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Utilisateur
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rôle
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="user in users" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ user.name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ user.email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select
                                        :value="user.role_id || ''"
                                        @change="assignRole(user, $event.target.value)"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">Aucun rôle</option>
                                        <option
                                            v-for="role in roles"
                                            :key="role.id"
                                            :value="role.id"
                                        >
                                            {{ role.nom }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800': !user.est_bloque,
                                            'bg-red-100 text-red-800': user.est_bloque,
                                        }"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ user.est_bloque ? 'Bloqué' : 'Actif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <DangerButton
                                        class="text-xs"
                                        @click="toggleBlock(user)"
                                    >
                                        {{ user.est_bloque ? 'Débloquer' : 'Bloquer' }}
                                    </DangerButton>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Aucun utilisateur trouvé
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
