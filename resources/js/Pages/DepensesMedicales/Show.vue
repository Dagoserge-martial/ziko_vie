<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { showError } from '@/plugins/sweetalert';
import Swal from 'sweetalert2';

const props = defineProps({
    depense: {
        type: Object,
        required: true,
    },
});

// Computed pour sécuriser l'accès aux données
const depense = computed(() => props.depense || {});
const membre = computed(() => depense.value.membre || {});
const categorie = computed(() => depense.value.categorie_depense || depense.value.categorieDepense || {});
const attachments = computed(() => depense.value.attachments || []);
const enregistrePar = computed(() => depense.value.enregistre_par || depense.value.enregistrePar || {});

const deleteDepense = () => {
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
            router.delete(route('depenses-medicales.destroy', depense.value.id), {
                onSuccess: () => {
                    Swal.fire('Supprimé !', 'La dépense médicale a été supprimée.', 'success');
                },
                onError: () => {
                    showError('Une erreur est survenue lors de la suppression');
                },
            });
        }
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('fr-FR');
    } catch (e) {
        return '-';
    }
};

const formatCurrency = (amount) => {
    if (!amount && amount !== 0) return '-';
    try {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(parseFloat(amount));
    } catch (e) {
        return amount || '-';
    }
};
</script>

<template>
    <Head :title="`Dépense médicale - ${depense.id || ''}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Détails de la dépense médicale
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('depenses-medicales.index')">
                        <SecondaryButton>Retour</SecondaryButton>
                    </Link>
                    <Link v-if="depense.id" :href="route('depenses-medicales.edit', depense.id)">
                        <PrimaryButton>Modifier</PrimaryButton>
                    </Link>
                    <DangerButton v-if="depense.id" @click="deleteDepense">
                        Supprimer
                    </DangerButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="!depense.id" class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500">Aucune donnée disponible</p>
                </div>
                <div v-else class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 space-y-6">
                        <!-- Informations principales -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations générales</h3>
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Membre</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ (membre.prenom || '') + ' ' + (membre.nom || '') || '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Catégorie</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ categorie.libelle || categorie.nom || '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Date de dépense</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ formatDate(depense.date_depense) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Détails supplémentaires</h3>
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ depense.description || '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Nom du prestataire</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ depense.nom_prestataire || '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Personne déléguée</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ depense.personne_deleguee || depense.nom_delegue || '-' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Montants -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Montants</h3>
                            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Montant</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ formatCurrency(depense.montant) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Montant transport</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ formatCurrency(depense.transport_pers_deleguee || depense.montant_transport) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Montant total</dt>
                                    <dd class="mt-1 text-lg font-semibold text-red-600">
                                        {{ formatCurrency(depense.montant_total || (parseFloat(depense.montant || 0) + parseFloat(depense.transport_pers_deleguee || depense.montant_transport || 0))) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Pièces jointes -->
                        <div v-if="attachments.length > 0" class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Pièces jointes</h3>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div
                                    v-for="attachment in attachments"
                                    :key="attachment.id"
                                    class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ attachment.nom_fichier || 'Fichier' }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ attachment.taille_fichier ? `${(attachment.taille_fichier / 1024).toFixed(2)} KB` : '' }}
                                            </p>
                                        </div>
                                        <a
                                            v-if="attachment.chemin_fichier"
                                            :href="`/storage/${attachment.chemin_fichier}`"
                                            target="_blank"
                                            class="ml-4 text-indigo-600 hover:text-indigo-900 text-sm"
                                        >
                                            Voir
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations système -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Informations système</h3>
                            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Enregistré par</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ enregistrePar.name || enregistrePar.email || '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Date de création</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ formatDate(depense.created_at) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

