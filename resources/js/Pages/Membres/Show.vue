<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { showError } from '@/plugins/sweetalert';
import Swal from '@/plugins/sweetalert';

const props = defineProps({
    membre: Object,
    totalCotisations: Number,
    totalDepenses: Number,
    isUpToDate: Boolean,
});

// Laravel sérialise les relations en snake_case par défaut
const depensesMedicales = computed(() => {
    return props.membre.depenses_medicales || props.membre.depensesMedicales || [];
});

// Année sélectionnée pour le filtre
const anneeSelectionnee = ref(null);

// Obtenir toutes les cotisations
const cotisations = computed(() => {
    return props.membre.cotisations || [];
});

// Générer les années de 2020 à l'année actuelle (décroissant)
const anneesDisponibles = computed(() => {
    const currentYear = new Date().getFullYear();
    const annees = [];
    for (let annee = currentYear; annee >= 2020; annee--) {
        annees.push(annee);
    }
    return annees;
});

// Calculer le montant total pour l'année sélectionnée
const montantAnneeSelectionnee = computed(() => {
    if (!anneeSelectionnee.value) {
        return 0;
    }
    return cotisations.value
        .filter(cotisation => cotisation.annee == anneeSelectionnee.value)
        .reduce((total, cotisation) => total + (parseFloat(cotisation.montant) || 0), 0);
});

// Initialiser avec l'année actuelle
const currentYear = new Date().getFullYear();
if (!anneeSelectionnee.value) {
    anneeSelectionnee.value = currentYear;
}

// Filtrer les cotisations selon l'année sélectionnée
const cotisationsFiltrees = computed(() => {
    if (!anneeSelectionnee.value) {
        return cotisations.value;
    }
    return cotisations.value.filter(cotisation => {
        return cotisation.annee == anneeSelectionnee.value;
    });
});

// Fonction pour obtenir le nom du mois en français
const getMonthName = (monthNumber) => {
    const months = [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
    ];
    const month = parseInt(monthNumber);
    return (month >= 1 && month <= 12) ? months[month - 1] : monthNumber || '-';
};

const deleteMembre = () => {
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
            router.delete(route('membres.destroy', props.membre.id), {
                onSuccess: () => {
                    // Le message de succès sera affiché automatiquement
                },
                onError: () => {
                    showError('Une erreur est survenue lors de la suppression');
                },
            });
        }
    });
};
</script>

<template>
    <Head :title="`${membre.prenom} ${membre.nom}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Détails du membre
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('membres.edit', membre.id)">
                        <PrimaryButton>Modifier</PrimaryButton>
                    </Link>
                    <Link :href="route('membres.index')">
                        <SecondaryButton>Retour</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Informations du membre -->
                <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">
                                    {{ membre.prenom }} {{ membre.nom }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Membre depuis le {{ membre.date_adhesion ? new Date(membre.date_adhesion).toLocaleDateString('fr-FR') : '-' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span
                                    :class="isUpToDate ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    class="px-3 py-1 text-sm font-semibold rounded-full"
                                >
                                    {{ isUpToDate ? 'À jour' : 'En retard' }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Informations de contact</h4>
                                <div class="space-y-2">
                                    <p class="text-sm text-gray-900">
                                        <span class="font-medium">Téléphone :</span>
                                        {{ membre.telephone || '-' }}
                                    </p>
                                    <p v-if="membre.utilisateur" class="text-sm text-gray-900">
                                        <span class="font-medium">Email :</span>
                                        {{ membre.utilisateur.email }}
                                    </p>
                                    <p v-if="membre.localite" class="text-sm text-gray-900">
                                        <span class="font-medium">Localité :</span>
                                        {{ membre.localite.libelle }}
                                    </p>
                                    <p v-if="membre.adresse" class="text-sm text-gray-900">
                                        <span class="font-medium">Adresse :</span>
                                        {{ membre.adresse }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Statistiques financières</h4>
                                <div class="space-y-2">
                                    <p class="text-sm text-gray-900">
                                        <span class="font-medium">Total cotisations :</span>
                                        <span class="text-green-600 font-semibold">
                                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(totalCotisations || 0) }}
                                        </span>
                                    </p>
                                    <p class="text-sm text-gray-900">
                                        <span class="font-medium">Total dépenses médicales :</span>
                                        <span class="text-red-600 font-semibold">
                                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(totalDepenses || 0) }}
                                        </span>
                                    </p>
                                    <p class="text-sm text-gray-900">
                                        <span class="font-medium">Solde :</span>
                                        <span :class="(totalCotisations - totalDepenses) >= 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format((totalCotisations || 0) - (totalDepenses || 0)) }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cotisations récentes -->
                <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Cotisations récentes</h3>
                            <Link :href="route('cotisations.index', { membre_id: membre.id })" class="text-sm text-indigo-600 hover:text-indigo-900">
                                Voir toutes
                            </Link>
                        </div>
                        
                        <!-- Select pour filtrer par année avec montant -->
                        <div class="mb-4 flex items-end gap-4">
                            <div class="flex-1">
                                <select
                                    id="annee_filter"
                                    v-model="anneeSelectionnee"
                                    class="mt-1 block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option
                                        v-for="annee in anneesDisponibles"
                                        :key="annee"
                                        :value="annee"
                                    >
                                        {{ annee }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex-shrink-0">
                                <p class="text-sm text-gray-500 mb-1">Montant total</p>
                                <p class="text-lg font-semibold text-green-600">
                                    {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(montantAnneeSelectionnee) }}
                                </p>
                            </div>
                        </div>
                        
                        <div v-if="cotisationsFiltrees && cotisationsFiltrees.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Année</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mois</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="cotisation in cotisationsFiltrees" :key="cotisation.id">
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ cotisation.annee || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ getMonthName(cotisation.mois) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(cotisation.montant || 0) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-sm text-gray-500 text-center py-4">
                            Aucune cotisation enregistrée
                        </p>
                    </div>
                </div>

                <!-- Dépenses médicales récentes -->
                <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Dépenses médicales récentes</h3>
                            <Link :href="route('depenses-medicales.index', { membre_id: membre.id })" class="text-sm text-indigo-600 hover:text-indigo-900">
                                Voir toutes
                            </Link>
                        </div>
                        <div v-if="depensesMedicales && depensesMedicales.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="depense in depensesMedicales" :key="depense.id">
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ depense.date_depense ? new Date(depense.date_depense).toLocaleDateString('fr-FR') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ depense.description }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ depense.categorie_depense?.nom || depense.categorieDepense?.nom || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-red-600">
                                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(depense.montant || 0) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-sm text-gray-500 text-center py-4">
                            Aucune dépense médicale enregistrée
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                        <div class="flex gap-4">
                            <Link :href="route('cotisations.create', { membre_id: membre.id })">
                                <PrimaryButton>Ajouter une cotisation</PrimaryButton>
                            </Link>
                            <Link :href="route('depenses-medicales.create', { membre_id: membre.id })">
                                <PrimaryButton>Ajouter une dépense médicale</PrimaryButton>
                            </Link>
                            <DangerButton @click="deleteMembre">
                                Supprimer le membre
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

