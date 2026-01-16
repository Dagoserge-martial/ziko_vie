<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    users: Object,
    roles: Array,
});

// Stocker les rôles sélectionnés pour chaque utilisateur
const userRoles = ref({});

// Fonction pour initialiser les rôles
const initializeUserRoles = () => {
    if (props.users && props.users.data) {
        props.users.data.forEach(user => {
            userRoles.value[user.id] = [...(user.role_ids || [])];
        });
    }
};

// Initialiser les rôles au chargement
initializeUserRoles();

// Surveiller les changements de users pour mettre à jour les rôles
watch(() => props.users, () => {
    initializeUserRoles();
}, { deep: true });

const updateUserRoles = (user, selectedRoleIds) => {
    // Sauvegarder l'état actuel avant modification
    const previousRoles = [...(userRoles.value[user.id] || [])];
    
    // Mettre à jour les rôles
    userRoles.value[user.id] = selectedRoleIds;
    
    // Envoyer la mise à jour au serveur
    router.post(
        route('parametres.users.assign-role', user.id),
        { role_ids: selectedRoleIds },
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Succès', 'Les rôles ont été mis à jour avec succès.', 'success');
            },
            onError: () => {
                Swal.fire('Erreur', 'Une erreur est survenue.', 'error');
                // Restaurer les rôles précédents en cas d'erreur
                userRoles.value[user.id] = previousRoles;
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
                                    Rôles
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
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ user.name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ user.email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <MultiSelect
                                        :model-value="userRoles[user.id] || []"
                                        @update:model-value="(value) => updateUserRoles(user, value)"
                                        :options="roles"
                                        option-label="nom"
                                        option-value="id"
                                        placeholder="Sélectionner des rôles..."
                                        search-placeholder="Rechercher un rôle..."
                                        max-height="250px"
                                        :multiple="true"
                                    />
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
                            <tr v-if="!users.data || users.data.length === 0">
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Aucun utilisateur trouvé
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="users && users.links && users.links.length > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <!-- Pagination mobile -->
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="users.links && users.links[0] && users.links[0].url"
                                    :href="users.links[0].url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Précédent
                                </Link>
                                <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                                    Précédent
                                </span>
                                <Link
                                    v-if="users.links && users.links.length > 0 && users.links[users.links.length - 1] && users.links[users.links.length - 1].url"
                                    :href="users.links[users.links.length - 1].url"
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
                                        <span class="font-medium">{{ users.from || 0 }}</span>
                                        à
                                        <span class="font-medium">{{ users.to || 0 }}</span>
                                        sur
                                        <span class="font-medium">{{ users.total || 0 }}</span>
                                        résultats
                                    </p>
                                </div>
                                <div v-if="users.links && users.links.length > 0">
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <Link
                                            v-for="(link, index) in users.links"
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
