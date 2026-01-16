<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Textarea from '@/Components/Textarea.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { showValidationErrors, showSuccess, validateRequired } from '@/plugins/sweetalert';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        PrimaryButton,
        SecondaryButton,
        TextInput,
        InputLabel,
        InputError,
        Textarea,
        MultiSelect,
        vSelect: window["vue-select"],
    },
    props: {
        membres: Array,
        categories: Array,
    },
    setup(props) {
        const form = useForm({
            membre_id: '',
            categorie_depense_id: '',
            description: '',
            montant: '',
            date_depense: new Date().toISOString().split('T')[0],
            nom_prestataire: '',
            nom_delegue: '',
            montant_transport: '',
            attachments: [],
        });

        const selectedFiles = ref([]);
        const delegueId = ref('');

        // Computed pour mettre à jour nom_delegue quand un membre est sélectionné
        const updateNomDelegue = () => {
            if (delegueId.value) {
                const membre = props.membres.find(m => String(m.id) === String(delegueId.value));
                if (membre) {
                    form.nom_delegue = `${membre.prenom || ''} ${membre.nom || ''}`.trim();
                }
            } else {
                form.nom_delegue = '';
            }
        };

        const handleFileChange = (event) => {
            const files = Array.from(event.target.files);
            selectedFiles.value = files;
            form.attachments = files;
        };

        const removeFile = (index) => {
            selectedFiles.value.splice(index, 1);
            form.attachments = selectedFiles.value;
        };

        const validateForm = () => {
            if (!validateRequired(form, 'membre_id', 'Membre')) return false;
            if (!validateRequired(form, 'categorie_depense_id', 'Catégorie')) return false;
            if (!validateRequired(form, 'description', 'Description')) return false;
            if (!validateRequired(form, 'montant', 'Montant')) return false;
            if (!validateRequired(form, 'date_depense', 'Date de dépense')) return false;
            
            // Validation du montant
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
            
            form.post(route('depenses-medicales.store'), {
                onSuccess: () => {
                    showSuccess('Dépense médicale enregistrée avec succès.');
                },
                onError: (errors) => {
                    showValidationErrors(errors);
                },
            });
        };

        return {
            form,
            selectedFiles,
            delegueId,
            updateNomDelegue,
            handleFileChange,
            removeFile,
            validateForm,
            submit,
        };
    },
};
</script>

<template>
    <Head title="Nouvelle dépense médicale" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Nouvelle dépense médicale
                </h2>
                <Link :href="route('depenses-medicales.index')">
                    <SecondaryButton>Retour</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <!-- Membre -->
                            <div>
                                <InputLabel for="membre_id" value="Membre *" />
                                <MultiSelect
                                    id="membre_id"
                                    :model-value="form.membre_id"
                                    @update:model-value="form.membre_id = $event"
                                    :options="membres"
                                    option-label="prenom"
                                    option-value="id"
                                    placeholder="Sélectionner un membre..."
                                    search-placeholder="Rechercher un membre..."
                                    max-height="250px"
                                    :multiple="false"
                                />
                                <InputError class="mt-2" :message="form.errors.membre_id" />
                            </div>

                            <!-- Catégorie -->
                            <div>
                                <InputLabel for="categorie_depense_id" value="Catégorie *" />
                                <select
                                    id="categorie_depense_id"
                                    v-model="form.categorie_depense_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Sélectionner une catégorie</option>
                                    <option
                                        v-for="categorie in categories"
                                        :key="categorie.id"
                                        :value="categorie.id"
                                    >
                                        {{ categorie.libelle || categorie.nom }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.categorie_depense_id" />
                            </div>

                            <!-- Description -->
                            <div class="sm:col-span-2">
                                <InputLabel for="description" value="Description *" />
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <!-- Montant -->
                            <div>
                                <InputLabel for="montant" value="Montant (FCFA) *" />
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

                            <!-- Date de dépense -->
                            <div>
                                <InputLabel for="date_depense" value="Date de dépense *" />
                                <TextInput
                                    id="date_depense"
                                    v-model="form.date_depense"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.date_depense" />
                            </div>

                            <!-- Nom du prestataire -->
                            <div>
                                <InputLabel for="nom_prestataire" value="Nom du prestataire" />
                                <TextInput
                                    id="nom_prestataire"
                                    v-model="form.nom_prestataire"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.nom_prestataire" />
                            </div>

                            <!-- Nom de la personne déléguée -->
                            <div>
                                <InputLabel for="nom_delegue" value="Personne déléguée" />
                                <MultiSelect
                                    id="nom_delegue"
                                    :model-value="delegueId"
                                    @update:model-value="delegueId = $event; updateNomDelegue()"
                                    :options="membres"
                                    option-label="prenom"
                                    option-value="id"
                                    placeholder="Sélectionner une personne déléguée..."
                                    search-placeholder="Rechercher un membre..."
                                    max-height="250px"
                                    :multiple="false"
                                />
                                <InputError class="mt-2" :message="form.errors.nom_delegue" />
                            </div>

                            <!-- Montant transport -->
                            <div>
                                <InputLabel for="montant_transport" value="Montant transport (FCFA)" />
                                <TextInput
                                    id="montant_transport"
                                    v-model="form.montant_transport"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.montant_transport" />
                            </div>

                            <!-- Pièces jointes -->
                            <div>
                                <InputLabel for="attachments" value="Pièces jointes (factures, ordonnances, etc.)" />
                                <input
                                    id="attachments"
                                    type="file"
                                    multiple
                                    accept="image/*,.pdf"
                                    @change="handleFileChange"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                />
                                <InputError class="mt-2" :message="form.errors.attachments" />
                                
                                <!-- Liste des fichiers sélectionnés -->
                                <div v-if="selectedFiles.length > 0" class="mt-2 space-y-2">
                                    <div
                                        v-for="(file, index) in selectedFiles"
                                        :key="index"
                                        class="flex items-center justify-between p-2 bg-gray-50 rounded"
                                    >
                                        <span class="text-sm text-gray-700">{{ file.name }}</span>
                                        <button
                                            type="button"
                                            @click="removeFile(index)"
                                            class="text-red-600 hover:text-red-800 text-sm"
                                        >
                                            Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                            <Link :href="route('depenses-medicales.index')">
                                <SecondaryButton type="button">Annuler</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

