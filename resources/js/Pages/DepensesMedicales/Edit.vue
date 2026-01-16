<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Textarea from '@/Components/Textarea.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { showValidationErrors, showSuccess, validateRequired } from '@/plugins/sweetalert';
import Swal from 'sweetalert2';

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
        depense: {
            type: Object,
            required: true,
        },
        membres: {
            type: Array,
            default: () => [],
        },
        categories: {
            type: Array,
            default: () => [],
        },
    },
    setup(props) {
        // Sécuriser l'accès aux données - utiliser directement props.depense
        const depenseData = props.depense || {};
        
        // Debug: vérifier les données reçues
        console.log('Données de la dépense:', depenseData);
        
        // Initialiser le formulaire avec les données de la dépense
        const dateDepense = depenseData.date_depense 
            ? new Date(depenseData.date_depense).toISOString().split('T')[0]
            : new Date().toISOString().split('T')[0];

        const form = useForm({
            membre_id: depenseData.membre_id ? String(depenseData.membre_id) : '',
            categorie_depense_id: depenseData.categorie_depense_id ? String(depenseData.categorie_depense_id) : '',
            description: depenseData.description || '',
            montant: depenseData.montant ? String(depenseData.montant) : '',
            date_depense: dateDepense,
            nom_prestataire: depenseData.nom_prestataire || '',
            nom_delegue: depenseData.personne_deleguee || depenseData.nom_delegue || '',
            montant_transport: depenseData.transport_pers_deleguee || depenseData.montant_transport ? String(depenseData.transport_pers_deleguee || depenseData.montant_transport) : '',
            attachments: [],
        });

        const selectedFiles = ref([]);
        const delegueId = ref('');
        const existingAttachments = ref(depenseData.attachments || []);

        // Trouver le membre délégué correspondant au nom
        const findDelegueId = () => {
            if (!form.nom_delegue || !props.membres) return;
            
            const nomDelegue = form.nom_delegue.trim();
            const membre = props.membres.find(m => {
                const nomComplet = `${m.prenom || ''} ${m.nom || ''}`.trim();
                return nomComplet === nomDelegue;
            });
            
            if (membre) {
                delegueId.value = membre.id;
            }
        };

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

        // Initialiser delegueId au montage
        onMounted(() => {
            findDelegueId();
        });

        const handleFileChange = (event) => {
            const files = Array.from(event.target.files);
            selectedFiles.value = files;
            form.attachments = files;
        };

        const removeFile = (index) => {
            selectedFiles.value.splice(index, 1);
            form.attachments = selectedFiles.value;
        };

        const deleteAttachment = (attachmentId) => {
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
                    router.delete(route('expense-attachments.destroy', attachmentId), {
                        preserveScroll: true,
                        onSuccess: () => {
                            // Retirer la pièce jointe de la liste
                            const index = existingAttachments.value.findIndex(a => a.id === attachmentId);
                            if (index !== -1) {
                                existingAttachments.value.splice(index, 1);
                            }
                            showSuccess('Pièce jointe supprimée avec succès.');
                        },
                        onError: () => {
                            showValidationErrors({ error: ['Une erreur est survenue lors de la suppression'] });
                        },
                    });
                }
            });
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
            
            const id = depenseId.value;
            if (!id) {
                showValidationErrors({ error: ['ID de dépense manquant'] });
                return;
            }
            
            console.log('Mise à jour de la dépense ID:', id);
            console.log('Données du formulaire:', form.data());
            
            // S'assurer que les IDs sont des strings pour la validation Laravel
            form.transform((data) => ({
                ...data,
                membre_id: String(data.membre_id),
                categorie_depense_id: String(data.categorie_depense_id),
            })).put(route('depenses-medicales.update', id), {
                preserveScroll: true,
                onSuccess: () => {
                    showSuccess('Dépense médicale mise à jour avec succès.');
                },
                onError: (errors) => {
                    console.error('Erreurs de validation:', errors);
                    showValidationErrors(errors);
                },
            });
        };

        // Computed pour sécuriser l'accès à depense
        const depense = computed(() => depenseData);
        
        // Computed pour la route de retour
        const showRoute = computed(() => {
            if (depense.value && depense.value.id) {
                return route('depenses-medicales.show', depense.value.id);
            }
            return null;
        });
        
        // Stocker l'ID de la dépense pour l'utiliser dans submit
        const depenseId = computed(() => depenseData.id);

        return {
            form,
            depense,
            depenseId,
            showRoute,
            selectedFiles,
            delegueId,
            existingAttachments,
            updateNomDelegue,
            handleFileChange,
            removeFile,
            deleteAttachment,
            validateForm,
            submit,
        };
    },
};
</script>

<template>
    <Head title="Modifier la dépense médicale" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Modifier la dépense médicale
                </h2>
                <Link v-if="showRoute" :href="showRoute">
                    <SecondaryButton>Retour</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="!depense.id" class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500">Aucune donnée disponible</p>
                </div>
                <div v-else class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
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

                            <!-- Pièces jointes existantes -->
                            <div v-if="existingAttachments.length > 0" class="sm:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Pièces jointes existantes</h3>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <div
                                        v-for="attachment in existingAttachments"
                                        :key="attachment.id"
                                        class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ attachment.nom_fichier || 'Fichier' }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ attachment.taille_fichier ? `${(attachment.taille_fichier / 1024).toFixed(2)} KB` : '' }}
                                                </p>
                                            </div>
                                            <div class="flex flex-col items-end gap-2 ml-4">
                                                <a
                                                    v-if="attachment.chemin_fichier"
                                                    :href="`/storage/${attachment.chemin_fichier}`"
                                                    target="_blank"
                                                    class="text-indigo-600 hover:text-indigo-900 text-sm"
                                                >
                                                    Voir
                                                </a>
                                                <button
                                                    @click="deleteAttachment(attachment.id)"
                                                    type="button"
                                                    class="text-red-600 hover:text-red-900 text-sm"
                                                    title="Supprimer"
                                                >
                                                    <svg
                                                        class="h-5 w-5"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pièces jointes -->
                            <div class="sm:col-span-2">
                                <InputLabel for="attachments" value="Ajouter de nouvelles pièces jointes" />
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
                            <Link v-if="showRoute" :href="showRoute">
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

