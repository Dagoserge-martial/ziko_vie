<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    membres: Object,
    localites: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const localite = ref(props.filters?.localite_id || '');

// Watcher pour recharger automatiquement les données quand les filtres changent
watch([search, localite], () => {
    router.get(route('membres.index'), {
        search: search.value || null,
        localite_id: localite.value || null,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
});
</script>

<template>
    <Head title="Membres" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Gestion des Membres
                </h2>
                <Link :href="route('membres.create')">
                    <PrimaryButton>Nouveau membre</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Filtres -->
                <div class="mb-6 bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel for="search" value="Rechercher" />
                            <TextInput
                                id="search"
                                v-model="search"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Nom, prénom, téléphone..."
                            />
                        </div>
                        <div>
                            <InputLabel for="localite_id" value="Localité" />
                            <select
                                id="localite_id"
                                v-model="localite"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Toutes les localités</option>
                                <option
                                    v-for="localiteOption in localites"
                                    :key="localiteOption.id"
                                    :value="localiteOption.id"
                                >
                                    {{ localiteOption.libelle }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <a
                                :href="route('membres.print', {
                                    search: search || null,
                                    localite_id: localite || null
                                })"
                                target="_blank"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Imprimer
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Liste des membres -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nom complet
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Contact
                                    </th>
                                    <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Statut
                                    </th> -->
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cotisations
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Dépenses
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="membre in membres.data" :key="membre.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ membre.prenom }} {{ membre.nom }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Adhésion le {{ membre.date_adhesion ? new Date(membre.date_adhesion).toLocaleDateString('fr-FR') : '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ membre.telephone || '-' }}</div>
                                        <div v-if="membre.utilisateur" class="text-sm text-gray-500">{{ membre.utilisateur.email }}</div>
                                    </td>
                                    <!-- <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800': membre.statut === 0,
                                                'bg-gray-100 text-gray-800': membre.statut === 1,
                                            }"
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        >
                                            {{ membre.statut === 0 ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td> -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(membre.total_cotisations || 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-red-600">
                                        {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(membre.total_depenses || membre.total_depenses_medicales || 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link
                                            :href="route('membres.show', membre.id)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4"
                                        >
                                            Voir
                                        </Link>
                                        <Link
                                            :href="route('membres.edit', membre.id)"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            Modifier
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="membres.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        Aucun membre trouvé
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="membres && membres.links && membres.links.length > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <!-- Pagination mobile -->
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="membres.links && membres.links[0] && membres.links[0].url"
                                    :href="membres.links[0].url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Précédent
                                </Link>
                                <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                                    Précédent
                                </span>
                                <Link
                                    v-if="membres.links && membres.links.length > 0 && membres.links[membres.links.length - 1] && membres.links[membres.links.length - 1].url"
                                    :href="membres.links[membres.links.length - 1].url"
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
                                        <span v-if="membres.from && membres.to && membres.total">
                                            Affichage de
                                            <span class="font-medium">{{ membres.from }}</span>
                                            à
                                            <span class="font-medium">{{ membres.to }}</span>
                                            sur
                                            <span class="font-medium">{{ membres.total }}</span>
                                            résultat<span v-if="membres.total > 1">s</span>
                                        </span>
                                        <span v-else class="text-gray-500">
                                            Aucun résultat
                                        </span>
                                    </p>
                                </div>
                                <div v-if="membres.links && membres.links.length > 0">
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <Link
                                            v-for="(link, index) in membres.links"
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

