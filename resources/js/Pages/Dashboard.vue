<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    stats: Object,
    dernieresCotisations: Array,
    dernieresDepenses: Array,
    statsMensuelles: Array,
    filters: Object,
});

// Initialiser le filtre année depuis les props ou vide
const annee = ref(props.filters?.annee || '');

// Obtenir l'année actuelle
const currentDate = new Date();
const currentYear = currentDate.getFullYear();

// Générer les années (année actuelle et années précédentes depuis 2020)
const annees = computed(() => {
    const anneesList = [];
    for (let anneeValue = currentYear; anneeValue >= 2020; anneeValue--) {
        anneesList.push({ value: anneeValue, label: anneeValue.toString() });
    }
    return anneesList;
});

// Watcher pour recharger automatiquement les données quand l'année change
watch(annee, (newAnnee) => {
    if (newAnnee === '' || newAnnee === null) {
        // Si aucune année n'est sélectionnée, on passe null
        router.get(route('dashboard'), {
            annee: null,
        }, {
            preserveState: false,
            preserveScroll: true,
            replace: false,
        });
    } else {
        // Sinon, on passe l'année sélectionnée
        router.get(route('dashboard'), {
            annee: parseInt(newAnnee),
        }, {
            preserveState: false,
            preserveScroll: true,
            replace: false,
        });
    }
});
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Tableau de bord
                    </h2>
                    <div>
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
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Statistiques principales -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                    <!-- Total Membres -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Total Membres</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.totalMembres }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ stats.membresActifs }} actifs</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Solde de la caisse -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Solde de la caisse</p>
                                    <p class="text-2xl font-semibold" :class="stats.solde >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(stats.solde) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">Total récolté - Total dépensé</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Cotisations -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Total Cotisations</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(stats.totalCotisations) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(stats.cotisationsMois) }} ce mois</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Dépenses -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Total Dépenses</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(stats.totalDepenses) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(stats.depensesMois) }} ce mois</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphique d'évolution -->
                <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Évolution sur 6 mois</h3>
                        <div class="grid grid-cols-6 gap-4">
                            <div v-for="(stat, index) in statsMensuelles" :key="index" class="text-center">
                                <p class="text-xs text-gray-500 mb-2">{{ stat.mois }}</p>
                                <div class="space-y-1">
                                    <div class="bg-green-100 rounded p-2">
                                        <p class="text-xs font-semibold text-green-800">
                                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(stat.cotisations) }}
                                        </p>
                                        <p class="text-xs text-green-600">Entrées</p>
                                    </div>
                                    <div class="bg-red-100 rounded p-2">
                                        <p class="text-xs font-semibold text-red-800">
                                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(stat.depenses) }}
                                        </p>
                                        <p class="text-xs text-red-600">Sorties</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dernières contributions et dépenses -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Dernières cotisations -->
                    <div class="bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Dernières cotisations</h3>
                                <Link :href="route('cotisations.index')" class="text-sm text-indigo-600 hover:text-indigo-800">
                                    Voir tout
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <div v-for="cotisation in dernieresCotisations.slice(0, 4)" :key="cotisation.id" class="border-l-4 border-indigo-500 pl-4 py-2">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ cotisation.membre.prenom }} {{ cotisation.membre.nom }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ new Date(cotisation.date_paiement).toLocaleDateString('fr-FR') }} - 
                                                {{ cotisation.mode_paiement?.libelle || cotisation.mode_paiement?.nom || '-' }}
                                            </p>
                                        </div>
                                        <p class="text-sm font-semibold text-indigo-600">
                                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(cotisation.montant) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="dernieresCotisations.length === 0" class="text-center py-4 text-gray-500">
                                    Aucune cotisation récente
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dernières dépenses -->
                    <div class="bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Dernières dépenses</h3>
                                <Link :href="route('depenses-medicales.index')" class="text-sm text-red-600 hover:text-red-800">
                                    Voir tout
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <div v-for="depense in dernieresDepenses.slice(0, 4)" :key="depense.id" class="border-l-4 border-red-500 pl-4 py-2">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ depense.membre.prenom }} {{ depense.membre.nom }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ depense.categorie_depense?.nom || '-' }} - {{ new Date(depense.date_depense).toLocaleDateString('fr-FR') }}
                                            </p>
                                        </div>
                                        <p class="text-sm font-semibold text-red-600">
                                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(depense.montant_total || depense.montant || 0) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="dernieresDepenses.length === 0" class="text-center py-4 text-gray-500">
                                    Aucune dépense récente
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="mt-6 bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions rapides</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <Link :href="route('membres.create')">
                                <PrimaryButton class="w-full">
                                    Ajouter un membre
                                </PrimaryButton>
                            </Link>
                            <Link :href="route('cotisations.create')">
                                <PrimaryButton class="w-full">
                                    Enregistrer une cotisation
                                </PrimaryButton>
                            </Link>
                            <Link :href="route('depenses-medicales.create')">
                                <PrimaryButton class="w-full">
                                    Enregistrer une dépense
                                </PrimaryButton>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
