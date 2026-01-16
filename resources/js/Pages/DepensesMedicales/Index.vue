<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    depenses: Object,
    membres: Array,
    localites: Array,
    categories: Array,
    filters: Object,
});

const searchMembre = ref(props.filters?.membre_id || '');
const localite = ref(props.filters?.localite_id || '');
const annee = ref(props.filters?.annee || '');
const mois = ref(props.filters?.mois || '');

// Obtenir l'année et le mois actuels
const currentDate = new Date();
const currentYear = currentDate.getFullYear();
const currentMonth = currentDate.getMonth() + 1; // getMonth() retourne 0-11, donc +1 pour 1-12

// Générer les années (année actuelle et années précédentes depuis 2020)
const annees = computed(() => {
    const anneesList = [];
    for (let annee = currentYear; annee >= 2020; annee--) {
        anneesList.push({ value: annee, label: annee.toString() });
    }
    return anneesList;
});

// Générer les mois
const moisOptions = computed(() => {
    return [
        { value: 1, label: 'Janvier' },
        { value: 2, label: 'Février' },
        { value: 3, label: 'Mars' },
        { value: 4, label: 'Avril' },
        { value: 5, label: 'Mai' },
        { value: 6, label: 'Juin' },
        { value: 7, label: 'Juillet' },
        { value: 8, label: 'Août' },
        { value: 9, label: 'Septembre' },
        { value: 10, label: 'Octobre' },
        { value: 11, label: 'Novembre' },
        { value: 12, label: 'Décembre' }
    ];
});

// Fonction pour obtenir le nom du mois à partir de son numéro
const getMonthName = (monthNumber) => {
    const month = parseInt(monthNumber);
    if (isNaN(month) || month < 1 || month > 12) {
        return monthNumber || '-';
    }
    const monthOption = moisOptions.value.find(m => m.value === month);
    return monthOption ? monthOption.label : monthNumber;
};

// Calculer le montant total des dépenses affichées
const montantTotal = computed(() => {
    if (!props.depenses || !props.depenses.data) {
        return 0;
    }
    return props.depenses.data.reduce((total, depense) => {
        return total + (parseFloat(depense.montant_total || depense.montant) || 0);
    }, 0);
});

// Watcher pour recharger automatiquement les données quand les filtres changent
watch([searchMembre, annee, mois], () => {
    router.get(route('depenses-medicales.index'), {
        membre_id: searchMembre.value || null,
        localite_id: localite.value || null,
        annee: annee.value || null,
        mois: mois.value || null,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
});

// Watcher spécifique pour la localité : réinitialiser le membre quand la localité change
watch(localite, (newLocalite, oldLocalite) => {
    // Réinitialiser le filtre membre si la localité change
    if (newLocalite !== oldLocalite) {
        searchMembre.value = '';
    }
    
    router.get(route('depenses-medicales.index'), {
        membre_id: null, // Toujours null quand on change de localité
        localite_id: localite.value || null,
        annee: annee.value || null,
        mois: mois.value || null,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
});

// Watcher spécifique pour l'année : réinitialiser le mois quand l'année change
watch(annee, (newAnnee, oldAnnee) => {
    // Réinitialiser le filtre mois si l'année change
    if (newAnnee !== oldAnnee) {
        mois.value = '';
    }
    
    router.get(route('depenses-medicales.index'), {
        membre_id: searchMembre.value || null,
        localite_id: localite.value || null,
        annee: annee.value || null,
        mois: null, // Toujours null quand on change d'année
    }, {
        preserveState: true,
        preserveScroll: true,
    });
});
</script>

<template>
    <Head title="Dépenses médicales" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Gestion des Dépenses Médicales
                </h2>
                <Link :href="route('depenses-medicales.create')">
                    <PrimaryButton>Nouvelle dépense</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Filtres -->
                <div class="mb-6 bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
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
                        <div>
                            <InputLabel for="membre_id" value="Membre" />
                            <MultiSelect
                                id="membre_id"
                                :model-value="searchMembre || null"
                                @update:model-value="searchMembre = $event || ''"
                                :options="membres"
                                option-label="prenom"
                                option-value="id"
                                placeholder="Tous les membres..."
                                search-placeholder="Rechercher un membre..."
                                max-height="250px"
                                :multiple="false"
                            />
                        </div>
                        <div>
                            <InputLabel for="annee" value="Année" />
                            <select
                                id="annee"
                                v-model="annee"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Toutes les années</option>
                                <option
                                    v-for="anneeOption in annees"
                                    :key="anneeOption.value"
                                    :value="anneeOption.value"
                                >
                                    {{ anneeOption.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="mois" value="Mois" />
                            <select
                                id="mois"
                                v-model="mois"
                                :disabled="!annee"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                            >
                                <option value="">{{ annee ? 'Tous les mois' : 'Sélectionnez d\'abord une année' }}</option>
                                <option
                                    v-for="moisOption in moisOptions"
                                    :key="moisOption.value"
                                    :value="moisOption.value"
                                >
                                    {{ moisOption.label }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <div class="w-full">
                                <InputLabel value="Montant total" />
                                <p class="mt-1 text-2xl font-semibold text-red-600">
                                    {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(montantTotal) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-end">
                            <a
                                :href="route('depenses-medicales.print', {
                                    membre_id: searchMembre || null,
                                    localite_id: localite || null,
                                    annee: annee || null,
                                    mois: mois || null
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

                <!-- Liste des dépenses -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Membre
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Catégorie
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Montant
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="depense in depenses.data" :key="depense.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ depense.membre?.prenom }} {{ depense.membre?.nom }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <template v-if="depense.date_depense">
                                            {{ getMonthName(new Date(depense.date_depense).getMonth() + 1) }} {{ new Date(depense.date_depense).getFullYear() }}
                                        </template>
                                        <template v-else>
                                            -
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ depense.description }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ depense.categorie_depense?.libelle || depense.categorieDepense?.libelle || depense.categorie_depense?.nom || depense.categorieDepense?.nom || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-red-600">
                                        {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(depense.montant_total || depense.montant || 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link
                                            :href="route('depenses-medicales.show', depense.id)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4"
                                        >
                                            Voir
                                        </Link>
                                        <Link
                                            :href="route('depenses-medicales.edit', depense.id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            Modifier
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="depenses.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        Aucune dépense médicale trouvée
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="depenses && depenses.links && depenses.links.length > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <!-- Pagination mobile -->
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="depenses.links && depenses.links[0] && depenses.links[0].url"
                                    :href="depenses.links[0].url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Précédent
                                </Link>
                                <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                                    Précédent
                                </span>
                                <Link
                                    v-if="depenses.links && depenses.links.length > 0 && depenses.links[depenses.links.length - 1] && depenses.links[depenses.links.length - 1].url"
                                    :href="depenses.links[depenses.links.length - 1].url"
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
                                        <span v-if="depenses.from && depenses.to && depenses.total">
                                            Affichage de
                                            <span class="font-medium">{{ depenses.from }}</span>
                                            à
                                            <span class="font-medium">{{ depenses.to }}</span>
                                            sur
                                            <span class="font-medium">{{ depenses.total }}</span>
                                            résultat<span v-if="depenses.total > 1">s</span>
                                        </span>
                                        <span v-else class="text-gray-500">
                                            Aucun résultat
                                        </span>
                                    </p>
                                </div>
                                <div v-if="depenses.links && depenses.links.length > 0">
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <Link
                                            v-for="(link, index) in depenses.links"
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

