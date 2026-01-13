<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import { showValidationErrors, showSuccess } from '@/plugins/sweetalert';
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
        ToggleSwitch,
    },
    props: {
        membres: {
            type: [Object, Array],
            default: () => []
        },
        localites: Array,
        cotisationsExistantes: {
            type: Object,
            default: () => ({})
        },
        modesPaiement: Array,
        statutsCotisation: Array,
        filtre: {
            type: String,
            default: 'non_payes'
        },
        annee: {
            type: [Number, String],
            default: null
        },
        mois: {
            type: [Number, String],
            default: null
        },
        localite_id: {
            type: [Number, String],
            default: null
        },
    },
    setup(props) {
        // Structure pour stocker les montants modifiés par l'utilisateur (saisie manuelle)
        const membresMontantsModifies = ref({});
        // Structure pour stocker l'état "payé" modifié par l'utilisateur
        const membresPayesModifies = ref({});
        // Champ de recherche pour filtrer les membres
        const searchQuery = ref('');
        // Flag pour éviter les rechargements multiples
        const isReloading = ref(false);

        // Obtenir l'année et le mois actuels
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth() + 1; // getMonth() retourne 0-11, donc +1 pour 1-12

        // Générer les années (année actuelle et année précédente)
        const annees = computed(() => {
            return [
                { value: currentYear, label: currentYear.toString() },
                { value: currentYear - 1, label: (currentYear - 1).toString() }
            ];
        });

        // Générer les mois selon l'année sélectionnée
        const mois = computed(() => {
            const tousLesMois = [
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
            
            // Si l'année sélectionnée est l'année en cours, afficher uniquement les mois passés et en cours
            if (form.annee === currentYear) {
                return tousLesMois.filter(m => m.value <= currentMonth);
            }
            
            // Sinon, afficher tous les mois
            return tousLesMois;
        });

        // Options pour le filtre des membres
        const filtreMembresOptions = [
            { value: 'tous', label: 'Tous les membres' },
            { value: 'payes', label: 'Membres ayant payé' },
            { value: 'non_payes', label: 'Membres n\'ayant pas payé' }
        ];

        // Informations communes pour toutes les cotisations
        // Utiliser les valeurs des props si disponibles, sinon les valeurs par défaut
        const form = useForm({
            filtre_membres: props.filtre || 'non_payes',
            localite_id: props.localite_id ? Number(props.localite_id) : null,
            annee: props.annee ? Number(props.annee) : currentYear,
            mois: props.mois ? Number(props.mois) : currentMonth,
        });

        // Calculer les montants finaux : combinaison des données serveur et des modifications utilisateur
        const membresMontants = computed(() => {
            const result = {};
            // Gérer à la fois la pagination (objet avec .data) et le tableau simple
            const membresList = Array.isArray(props.membres) 
                ? props.membres 
                : (props.membres?.data || []);
            if (membresList && membresList.length > 0) {
                membresList.forEach(membre => {
                    const membreId = membre.id;
                    const membreIdStr = String(membreId);
                    const membreIdNum = Number(membreId);
                    
                    // Si l'utilisateur a modifié le montant, utiliser sa valeur
                    if (membresMontantsModifies.value[membreId] !== undefined) {
                        result[membreId] = membresMontantsModifies.value[membreId];
                    } else {
                        // Sinon, utiliser la valeur du serveur (cotisation existante)
                        const cotisationExistante = (props.cotisationsExistantes && typeof props.cotisationsExistantes === 'object') 
                            ? (props.cotisationsExistantes[membreIdStr] || props.cotisationsExistantes[membreIdNum])
                            : null;
                        
                        if (cotisationExistante && cotisationExistante.montant != null) {
                            result[membreId] = String(cotisationExistante.montant);
                        } else {
                            result[membreId] = '';
                        }
                    }
                });
            }
            return result;
        });

        // Calculer l'état "payé" final : combinaison des données serveur et des modifications utilisateur
        const membresPayes = computed(() => {
            const result = {};
            // Gérer à la fois la pagination (objet avec .data) et le tableau simple
            const membresList = Array.isArray(props.membres) 
                ? props.membres 
                : (props.membres?.data || []);
            if (membresList && membresList.length > 0) {
                membresList.forEach(membre => {
                    const membreId = membre.id;
                    const membreIdStr = String(membreId);
                    const membreIdNum = Number(membreId);
                    
                    // Si l'utilisateur a modifié l'état, utiliser sa valeur
                    if (membresPayesModifies.value[membreId] !== undefined) {
                        result[membreId] = membresPayesModifies.value[membreId];
                    } else {
                        // Sinon, déterminer depuis les cotisations existantes
                        const cotisationExistante = (props.cotisationsExistantes && typeof props.cotisationsExistantes === 'object') 
                            ? (props.cotisationsExistantes[membreIdStr] || props.cotisationsExistantes[membreIdNum])
                            : null;
                        
                        result[membreId] = cotisationExistante && cotisationExistante.montant != null;
                    }
                });
            }
            return result;
        });

        // Watcher pour mettre à jour le formulaire quand les props changent (après un rechargement)
        watch([() => props.filtre, () => props.annee, () => props.mois], ([newFiltre, newAnnee, newMois]) => {
            if (newFiltre && form.filtre_membres !== newFiltre) {
                form.filtre_membres = newFiltre;
            }
            if (newAnnee && form.annee !== Number(newAnnee)) {
                form.annee = Number(newAnnee);
            }
            if (newMois && form.mois !== Number(newMois)) {
                form.mois = Number(newMois);
            }
        });

        // Watcher pour réinitialiser les modifications quand les données du serveur changent
        watch([() => {
            return Array.isArray(props.membres) 
                ? props.membres 
                : (props.membres?.data || []);
        }, () => props.cotisationsExistantes], () => {
            // Réinitialiser les modifications utilisateur quand les données sont rechargées
            membresMontantsModifies.value = {};
            membresPayesModifies.value = {};
        }, { deep: true });

        // Watcher pour cocher/décocher automatiquement le switch "Payé(e)" selon le montant saisi
        // Note: La logique principale est dans updateMontant, ce watcher est un backup
        watch(membresMontantsModifies, (newMontants) => {
            for (const [membreId, montant] of Object.entries(newMontants)) {
                const num = parseFloat(montant);
                // Cocher si montant > 0, décocher si vide ou 0
                if (!isNaN(num) && num > 0) {
                    membresPayesModifies.value[membreId] = true;
                } else {
                    membresPayesModifies.value[membreId] = false;
                }
            }
        }, { deep: true });

        // Watcher pour réinitialiser le mois si l'année change et que le mois sélectionné n'est plus valide
        watch(() => form.annee, (newAnnee) => {
            if (form.mois && newAnnee === currentYear) {
                // Si l'année est l'année en cours et le mois sélectionné est supérieur au mois actuel, réinitialiser
                if (form.mois > currentMonth) {
                    form.mois = currentMonth;
                }
            }
        });

        // Watcher pour recharger les données quand le filtre, la localité, l'année ou le mois change
        watch([() => form.filtre_membres, () => form.localite_id, () => form.annee, () => form.mois], ([newFiltre, newLocalite, newAnnee, newMois], [oldFiltre, oldLocalite, oldAnnee, oldMois]) => {
            // Éviter le rechargement au premier rendu
            if (oldFiltre === undefined && oldLocalite === undefined && oldAnnee === undefined && oldMois === undefined) {
                return;
            }
            
            // Si la localité change, réinitialiser le filtre membre
            if (newLocalite !== oldLocalite && oldLocalite !== undefined) {
                form.filtre_membres = 'tous';
            }
            
            if (newFiltre && newAnnee && newMois && !isReloading.value) {
                isReloading.value = true;
                
                // Réinitialiser les modifications avant de recharger
                membresMontantsModifies.value = {};
                membresPayesModifies.value = {};
                searchQuery.value = '';
                
                // Recharger la page avec les nouveaux paramètres
                router.get(route('cotisations.create'), {
                    filtre: newFiltre,
                    localite_id: newLocalite || null,
                    annee: newAnnee,
                    mois: newMois
                }, {
                    preserveState: false, // Ne pas préserver l'état pour réinitialiser correctement
                    preserveScroll: true,
                    only: ['membres', 'cotisationsExistantes', 'filtre', 'annee', 'mois', 'localite_id'],
                    onFinish: () => {
                        isReloading.value = false;
                    }
                });
            }
        });

        // Calculer le nombre de cotisations à créer (membres avec montant > 0)
        const cotisationsCount = computed(() => {
            return Object.entries(membresMontants.value).filter(([membreId, montant]) => {
                const num = parseFloat(montant);
                return !isNaN(num) && num > 0;
            }).length;
        });

        // Calculer le montant total
        const montantTotal = computed(() => {
            return Object.values(membresMontants.value).reduce((total, montant) => {
                const num = parseFloat(montant);
                return total + (isNaN(num) ? 0 : num);
            }, 0);
        });

        // Fonction pour mettre à jour le montant d'un membre
        const updateMontant = (membreId, montant) => {
            const montantStr = montant != null ? String(montant).trim() : '';
            if (montantStr === '') {
                // Si le champ est vide, supprimer la modification pour utiliser la valeur du serveur
                delete membresMontantsModifies.value[membreId];
                // Décocher automatiquement le bouton "Payé(e)"
                membresPayesModifies.value[membreId] = false;
            } else {
                membresMontantsModifies.value[membreId] = montantStr;
                // Cocher automatiquement le bouton "Payé(e)" si le montant est valide
                const num = parseFloat(montantStr);
                if (!isNaN(num) && num > 0) {
                    membresPayesModifies.value[membreId] = true;
                } else {
                    membresPayesModifies.value[membreId] = false;
                }
            }
        };

        // Fonction pour mettre à jour l'état "payé" d'un membre
        const updatePaye = (membreId, estPaye) => {
            if (estPaye === undefined) {
                // Si undefined, supprimer la modification pour utiliser la valeur du serveur
                delete membresPayesModifies.value[membreId];
            } else {
                membresPayesModifies.value[membreId] = estPaye;
            }
        };

        // Filtrer les membres en fonction de la recherche
        const membresFiltres = computed(() => {
            // Gérer à la fois la pagination (objet avec .data) et le tableau simple
            const membresList = Array.isArray(props.membres) 
                ? props.membres 
                : (props.membres?.data || []);
            if (!searchQuery.value || searchQuery.value.trim() === '') {
                return membresList;
            }
            
            const query = searchQuery.value.toLowerCase().trim();
            return membresList.filter(membre => {
                const nom = (membre.nom || '').toLowerCase();
                const prenom = (membre.prenom || '').toLowerCase();
                const telephone = (membre.telephone || '').toLowerCase();
                const fullName = `${prenom} ${nom}`.trim();
                
                return nom.includes(query) || 
                       prenom.includes(query) || 
                       fullName.includes(query) ||
                       telephone.includes(query);
            });
        });

        const validateForm = () => {
            // Vérifier les champs communs
            if (!form.annee) {
                showValidationErrors({ annee: ['L\'année est obligatoire'] });
                return false;
            }
            
            if (!form.mois) {
                showValidationErrors({ mois: ['Le mois est obligatoire'] });
                return false;
            }
            
            // Vérifier qu'aucun membre n'a le switch "Payé(e)" coché sans montant
            for (const [membreId, estPaye] of Object.entries(membresPayes.value)) {
                if (estPaye) {
                    const montant = membresMontants.value[membreId];
                    // Convertir en string si nécessaire et vérifier si vide
                    const montantStr = montant != null ? String(montant).trim() : '';
                    if (!montantStr || montantStr === '') {
                        const membresList = Array.isArray(props.membres) 
                            ? props.membres 
                            : (props.membres?.data || []);
                        const membre = membresList.find(m => m.id == membreId);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur de validation',
                            text: `Le membre ${membre?.prenom} ${membre?.nom} est marqué comme payé(e) mais aucun montant n'a été renseigné. Veuillez saisir un montant ou décocher le bouton "Payé(e)".`,
                            confirmButtonText: 'OK'
                        });
                        return false;
                    }
                    // Vérifier aussi que le montant est valide
                    const num = parseFloat(montantStr);
                    if (isNaN(num) || num <= 0) {
                        const membresList = Array.isArray(props.membres) 
                            ? props.membres 
                            : (props.membres?.data || []);
                        const membre = membresList.find(m => m.id == membreId);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur de validation',
                            text: `Le membre ${membre?.prenom} ${membre?.nom} est marqué comme payé(e) mais le montant saisi (${montantStr}) n'est pas valide. Veuillez saisir un montant positif.`,
                            confirmButtonText: 'OK'
                        });
                        return false;
                    }
                }
            }
            
            // Vérifier qu'au moins un membre a un montant
            if (cotisationsCount.value === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Attention',
                    text: 'Veuillez saisir au moins un montant pour un membre.',
                    confirmButtonText: 'OK'
                });
                return false;
            }
            
            // Vérifier que tous les montants saisis sont valides
            for (const [membreId, montant] of Object.entries(membresMontants.value)) {
                // Convertir en string si nécessaire et vérifier si non vide
                const montantStr = montant != null ? String(montant).trim() : '';
                if (montantStr !== '') {
                    const num = parseFloat(montantStr);
                    if (isNaN(num) || num <= 0) {
                        const membresList = Array.isArray(props.membres) 
                            ? props.membres 
                            : (props.membres?.data || []);
                        const membre = membresList.find(m => m.id == membreId);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: `Le montant pour ${membre?.prenom} ${membre?.nom} doit être un nombre positif.`,
                            confirmButtonText: 'OK'
                        });
                        return false;
                    }
                }
            }
            
            return true;
        };

        const submit = () => {
            if (!validateForm()) {
                return;
            }
            
            // Construire le tableau de cotisations (seulement pour les membres avec montant > 0)
            const cotisations = [];
            for (const [membreId, montant] of Object.entries(membresMontants.value)) {
                // Convertir en string si nécessaire et vérifier si non vide
                const montantStr = montant != null ? String(montant).trim() : '';
                if (montantStr !== '') {
                    const num = parseFloat(montantStr);
                    if (!isNaN(num) && num > 0) {
                        // Trouver le statut "Payé" si le membre est marqué comme payé
                        let statutId = form.statut_cotisation_id;
                        if (membresPayes.value[membreId]) {
                            const statutPaye = props.statutsCotisation?.find(s => 
                                s.libelle?.toLowerCase() === 'payé' || s.libelle?.toLowerCase() === 'paye'
                            );
                            if (statutPaye) {
                                statutId = statutPaye.id;
                            }
                        }
                        
                        // Construire la date de paiement à partir de l'année et du mois
                        // const datePaiement = `${form.annee}-${String(form.mois).padStart(2, '0')}-01`;
                        
                        cotisations.push({
                            membre_id: membreId,
                            montant: num,
                            // date_paiement: datePaiement,
                            statut_cotisation_id: statutId,
                            annee: `${form.annee}`,
                            mois: `${form.mois}`
                        });
                    }
                }
            }
            
            const submitForm = useForm({
                cotisations: cotisations,
            });
            
            submitForm.post(route('cotisations.store-multiple'), {
                onSuccess: () => {
                    showSuccess(`${cotisations.length} cotisation(s) enregistrée(s) avec succès.`);
                },
                onError: (errors) => {
                    showValidationErrors(errors);
                },
            });
        };

        return {
            form,
            membresMontants,
            membresPayes,
            searchQuery,
            annees,
            mois,
            filtreMembresOptions,
            cotisationsCount,
            montantTotal,
            membresFiltres,
            validateForm,
            submit,
            updateMontant,
            updatePaye,
            currentYear,
            currentMonth,
        };
    },
};
</script>

<template>
    <Head title="Nouvelle cotisation" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Nouvelle cotisation
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
                        <!-- Informations communes -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-5 mb-6 pb-6 border-b border-gray-200">
                            <div>
                                <InputLabel for="filtre_membres" value="Filtre des membres" />
                                <select
                                    id="filtre_membres"
                                    v-model="form.filtre_membres"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option
                                        v-for="option in filtreMembresOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.filtre_membres" />
                            </div>

                            <div>
                                <InputLabel for="localite_id" value="Localité" />
                                <select
                                    id="localite_id"
                                    v-model="form.localite_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option :value="null">Toutes les localités</option>
                                    <option
                                        v-for="localite in localites"
                                        :key="localite.id"
                                        :value="localite.id"
                                    >
                                        {{ localite.libelle }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.localite_id" />
                            </div>

                            <div>
                                <InputLabel for="annee" value="Année *" />
                                <select
                                    id="annee"
                                    v-model="form.annee"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option
                                        v-for="annee in annees"
                                        :key="annee.value"
                                        :value="annee.value"
                                    >
                                        {{ annee.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.annee" />
                            </div>

                            <div>
                                <InputLabel for="mois" value="Mois *" />
                                <select
                                    id="mois"
                                    v-model="form.mois"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option
                                        v-for="moisItem in mois"
                                        :key="moisItem.value"
                                        :value="moisItem.value"
                                    >
                                        {{ moisItem.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.mois" />
                            </div>
                        </div>

                        <!-- Liste des membres avec montants -->
                        <div class="mb-6">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-4">
                                <div class="w-full sm:w-1/2">
                                    <!-- <InputLabel for="search_membre" value="Rechercher un membre" /> -->
                                    <TextInput
                                        id="search_membre"
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Rechercher par nom, prénom ou téléphone..."
                                        class="mt-1 block w-full"
                                    />
                                </div>
                                <div class="text-sm text-gray-600 whitespace-nowrap">
                                    <span class="font-semibold">{{ cotisationsCount }}</span> cotisation(s) • 
                                    Total: <span class="font-semibold">{{ montantTotal.toFixed(2) }}</span> FCFA
                                </div>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Nom et Prénom
                                            </th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                                Payé(e)
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                                                Montant (FCFA)
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="membre in membresFiltres" :key="membre.id">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ membre.prenom }} {{ membre.nom }}
                                                </div>
                                                <div v-if="membre.telephone" class="text-sm text-gray-500">
                                                    {{ membre.telephone }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                                <ToggleSwitch
                                                    :checked="membresPayes[membre.id]"
                                                    @update:checked="updatePaye(membre.id, $event)"
                                                />
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <TextInput
                                                    :id="`montant_${membre.id}`"
                                                    :model-value="membresMontants[membre.id]"
                                                    @update:model-value="updateMontant(membre.id, $event)"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0.00"
                                                    class="block w-full"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div v-if="membres && !Array.isArray(membres) && membres.links && membres.links.length > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6 mt-4">
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
                                                    membre<span v-if="membres.total > 1">s</span>
                                                </span>
                                                <span v-else class="text-gray-500">
                                                    Aucun membre
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

                        <!-- Boutons d'action -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                            <Link :href="route('cotisations.index')">
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

