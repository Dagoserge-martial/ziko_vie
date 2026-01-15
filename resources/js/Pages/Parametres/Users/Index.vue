<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    users: Array,
    roles: Array,
});

// Stocker les rôles sélectionnés pour chaque utilisateur
const userRoles = ref({});

// Fonction pour initialiser les rôles
const initializeUserRoles = () => {
    props.users.forEach(user => {
        userRoles.value[user.id] = [...(user.role_ids || [])];
    });
};

// Initialiser les rôles au chargement
initializeUserRoles();

// Surveiller les changements de users pour mettre à jour les rôles
watch(() => props.users, () => {
    initializeUserRoles();
}, { deep: true });

const toggleRole = (user, roleId) => {
    if (!userRoles.value[user.id]) {
        userRoles.value[user.id] = [];
    }
    
    // Sauvegarder l'état actuel avant modification
    const previousRoles = [...userRoles.value[user.id]];
    
    const roleIndex = userRoles.value[user.id].indexOf(roleId);
    if (roleIndex > -1) {
        // Retirer le rôle
        userRoles.value[user.id].splice(roleIndex, 1);
    } else {
        // Ajouter le rôle
        userRoles.value[user.id].push(roleId);
    }
    
    // Envoyer la mise à jour au serveur
    router.post(
        route('parametres.users.assign-role', user.id),
        { role_ids: userRoles.value[user.id] },
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

const isRoleSelected = (user, roleId) => {
    return userRoles.value[user.id]?.includes(roleId) || false;
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
                            <tr v-for="user in users" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ user.name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ user.email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                        <div
                                            v-for="role in roles"
                                            :key="role.id"
                                            class="flex items-center"
                                        >
                                            <Checkbox
                                                :id="`user-${user.id}-role-${role.id}`"
                                                :checked="isRoleSelected(user, role.id)"
                                                @update:checked="() => toggleRole(user, role.id)"
                                            />
                                            <label
                                                :for="`user-${user.id}-role-${role.id}`"
                                                class="ms-2 text-sm text-gray-700 cursor-pointer"
                                                @click="toggleRole(user, role.id)"
                                            >
                                                {{ role.nom }}
                                            </label>
                                        </div>
                                        <div v-if="roles.length === 0" class="text-sm text-gray-500 italic">
                                            Aucun rôle disponible
                                        </div>
                                    </div>
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
