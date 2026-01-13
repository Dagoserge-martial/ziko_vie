<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Textarea from '@/Components/Textarea.vue';
import { showValidationErrors, validateRequired } from '@/plugins/sweetalert';

const props = defineProps({
    cotisation: Object,
    membres: Array,
    modesPaiement: Array,
    statutsCotisation: Array,
});

const form = useForm({
    membre_id: props.cotisation.membre_id || '',
    montant: props.cotisation.montant || '',
    date_paiement: props.cotisation.date_paiement ? new Date(props.cotisation.date_paiement).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    mode_paiement_id: props.cotisation.mode_paiement_id || '',
    statut_cotisation_id: props.cotisation.statut_cotisation_id || null,
    reference: props.cotisation.reference || '',
    notes: props.cotisation.notes || '',
});

const validateForm = () => {
    if (!validateRequired(form, 'membre_id', 'Membre')) return false;
    if (!validateRequired(form, 'montant', 'Montant')) return false;
    if (!validateRequired(form, 'date_paiement', 'Date de paiement')) return false;
    if (!validateRequired(form, 'mode_paiement_id', 'Mode de paiement')) return false;
    
    const montant = parseFloat(form.montant);
    if (isNaN(montant) || montant <= 0) {
        showValidationErrors({ montant: ['Le montant doit être un nombre positif'] });
        return false;
    }
    
    return true;
};

const submit = () => {
    if (!validateForm()) {
        return;
    }
    
    form.put(route('cotisations.update', props.cotisation.id), {
        onSuccess: () => {
            // Le message de succès sera affiché automatiquement
        },
        onError: (errors) => {
            showValidationErrors(errors);
        },
    });
};

watch(
    () => form.errors,
    (errors) => {
        if (errors && Object.keys(errors).length > 0) {
            showValidationErrors(errors);
        }
    },
    { deep: true }
);
</script>

<template>
    <Head :title="`Modifier cotisation`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Modifier la cotisation
                </h2>
                <Link :href="route('cotisations.index')">
                    <SecondaryButton>Retour</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Membre et Montant -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="membre_id" value="Membre *" />
                                <select
                                    id="membre_id"
                                    v-model="form.membre_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Sélectionner un membre</option>
                                    <option
                                        v-for="membre in membres"
                                        :key="membre.id"
                                        :value="membre.id"
                                    >
                                        {{ membre.prenom }} {{ membre.nom }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.membre_id" />
                            </div>

                            <div>
                                <InputLabel for="montant" value="Montant *" />
                                <TextInput
                                    id="montant"
                                    v-model="form.montant"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.montant" />
                            </div>
                        </div>

                        <!-- Date de paiement et Mode de paiement -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="date_paiement" value="Date de paiement *" />
                                <TextInput
                                    id="date_paiement"
                                    v-model="form.date_paiement"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.date_paiement" />
                            </div>

                            <div>
                                <InputLabel for="mode_paiement_id" value="Mode de paiement *" />
                                <select
                                    id="mode_paiement_id"
                                    v-model="form.mode_paiement_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Sélectionner un mode de paiement</option>
                                    <option
                                        v-for="mode in modesPaiement"
                                        :key="mode.id"
                                        :value="mode.id"
                                    >
                                        {{ mode.libelle || mode.nom }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.mode_paiement_id" />
                            </div>
                        </div>

                        <!-- Statut et Référence -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="statut_cotisation_id" value="Statut" />
                                <select
                                    id="statut_cotisation_id"
                                    v-model="form.statut_cotisation_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option :value="null">Sélectionner un statut</option>
                                    <option
                                        v-for="statut in statutsCotisation"
                                        :key="statut.id"
                                        :value="statut.id"
                                    >
                                        {{ statut.libelle || statut.nom }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.statut_cotisation_id" />
                            </div>

                            <div>
                                <InputLabel for="reference" value="Référence" />
                                <TextInput
                                    id="reference"
                                    v-model="form.reference"
                                    type="text"
                                    class="mt-1 block w-full"
                                    maxlength="255"
                                />
                                <InputError class="mt-2" :message="form.errors.reference" />
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <InputLabel for="notes" value="Notes" />
                            <Textarea
                                id="notes"
                                v-model="form.notes"
                                class="mt-1 block w-full"
                                rows="3"
                            />
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <!-- Boutons d'action -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                            <Link :href="route('cotisations.index')">
                                <SecondaryButton type="button">Annuler</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Mise à jour...' : 'Mettre à jour' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

