<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    roles: Array,
});

const deleteRole = (role) => {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: 'Cette action est irréversible !',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('parametres.roles.destroy', role.id), {
                onSuccess: () => {
                    Swal.fire('Supprimé !', 'Le rôle a été supprimé.', 'success');
                },
                onError: (errors) => {
                    const errorMessage = errors?.message || 'Une erreur est survenue lors de la suppression';
                    Swal.fire('Erreur', errorMessage, 'error');
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Gestion des Rôles" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Gestion des Rôles
                </h2>
                <Link :href="route('parametres.roles.create')">
                    <PrimaryButton>Nouveau rôle</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nom
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Description
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Utilisateurs
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
                            <tr v-for="role in roles" :key="role.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ role.nom }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500">
                                        {{ role.description || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ role.utilisateurs_count || 0 }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800': role.actif,
                                            'bg-red-100 text-red-800': !role.actif,
                                        }"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ role.actif ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('parametres.roles.show', role.id)">
                                            <SecondaryButton class="text-xs">Voir</SecondaryButton>
                                        </Link>
                                        <Link :href="route('parametres.roles.edit', role.id)">
                                            <PrimaryButton class="text-xs">Modifier</PrimaryButton>
                                        </Link>
                                        <DangerButton
                                            class="text-xs"
                                            @click="deleteRole(role)"
                                            :disabled="role.slug === 'admin'"
                                        >
                                            Supprimer
                                        </DangerButton>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="roles.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Aucun rôle trouvé
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
