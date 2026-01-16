<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    roles: Object,
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
                            <tr v-for="role in roles.data" :key="role.id">
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
                            <tr v-if="!roles.data || roles.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Aucun rôle trouvé
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="roles && roles.links && roles.links.length > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <!-- Pagination mobile -->
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="roles.links && roles.links[0] && roles.links[0].url"
                                    :href="roles.links[0].url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Précédent
                                </Link>
                                <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                                    Précédent
                                </span>
                                <Link
                                    v-if="roles.links && roles.links.length > 0 && roles.links[roles.links.length - 1] && roles.links[roles.links.length - 1].url"
                                    :href="roles.links[roles.links.length - 1].url"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Suivant
                                </Link>
                                <span v-else class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                                    Suivant
                                </span>
                            </div>
                            <!-- Pagination desktop -->
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Affichage de
                                        <span class="font-medium">{{ roles.from || 0 }}</span>
                                        à
                                        <span class="font-medium">{{ roles.to || 0 }}</span>
                                        sur
                                        <span class="font-medium">{{ roles.total || 0 }}</span>
                                        résultats
                                    </p>
                                </div>
                                <div v-if="roles.links && roles.links.length > 0">
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <Link
                                            v-for="(link, index) in roles.links"
                                            :key="index"
                                            :href="link.url || '#'"
                                            :class="{
                                                'bg-indigo-50 border-indigo-500 text-indigo-600 z-10': link.active,
                                                'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': !link.active && link.url,
                                                'bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed pointer-events-none': !link.url,
                                            }"
                                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                            v-html="link.label"
                                        />
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
