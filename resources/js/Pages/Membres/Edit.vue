<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Textarea from '@/Components/Textarea.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import { showError, showValidationErrors, validateRequired } from '@/plugins/sweetalert';

const props = defineProps({
    membre: Object,
    localites: Array,
});

const isAdmin = usePage().props.auth.user?.isAdmin || false;

const hasAccount = ref(props.membre?.utilisateur_id ? true : false);
const isUtilisateur = ref(props.membre?.utilisateur_id ? true : false);
const modifierMotDePasse = ref(false);

const form = useForm({
    nom: props.membre.nom || '',
    prenom: props.membre.prenom || '',
    telephone: props.membre.telephone || '',
    localite_id: props.membre.localite_id || null,
    adresse: props.membre.adresse || '',
    date_adhesion: props.membre.date_adhesion ? new Date(props.membre.date_adhesion).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    statut: props.membre.statut ?? 0,
    // Champs pour le compte utilisateur
    est_utilisateur: props.membre?.utilisateur_id ? true : false,
    email: props.membre?.utilisateur?.email || '',
    password: '',
    password_confirmation: '',
    modifier_mot_de_passe: false,
});

// Validation avant soumission
const validateForm = () => {
    if (!validateRequired(form, 'nom', 'Nom')) return false;
    if (!validateRequired(form, 'prenom', 'Prénom')) return false;
    
    // Validation si création/modification de compte utilisateur
    if (isUtilisateur.value) {
        if (!validateRequired(form, 'email', 'Email')) return false;
        
        // Validation du format email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (form.email && !emailRegex.test(form.email)) {
            showError('Veuillez entrer une adresse email valide');
            return false;
        }
        
        // Si c'est un nouveau compte ou si on veut modifier le mot de passe
        if (!hasAccount.value || modifierMotDePasse.value) {
            if (!validateRequired(form, 'password', 'Mot de passe')) return false;
            if (!validateRequired(form, 'password_confirmation', 'Confirmation du mot de passe')) return false;
            
            if (form.password !== form.password_confirmation) {
                showError('Les mots de passe ne correspondent pas');
                return false;
            }
            
            if (form.password.length < 8) {
                showError('Le mot de passe doit contenir au moins 8 caractères');
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
    
    // Si la checkbox est cochée, inclure les données utilisateur
    if (isUtilisateur.value) {
        form.est_utilisateur = true;
        form.modifier_mot_de_passe = modifierMotDePasse.value;
        
        // Si on ne modifie pas le mot de passe, ne pas envoyer les champs password
        if (!modifierMotDePasse.value && hasAccount.value) {
            form.password = '';
            form.password_confirmation = '';
        }
    } else {
        form.est_utilisateur = false;
        form.email = '';
        form.password = '';
        form.password_confirmation = '';
        form.modifier_mot_de_passe = false;
    }
    
    form.put(route('membres.update', props.membre.id), {
        onSuccess: () => {
            // Le message de succès sera affiché automatiquement via FlashMessages
        },
        onError: (errors) => {
            showValidationErrors(errors);
        },
    });
};

// Surveiller les erreurs du formulaire
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
    <Head :title="`Modifier ${membre.prenom} ${membre.nom}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Modifier le membre
                </h2>
                <Link :href="route('membres.show', membre.id)">
                    <SecondaryButton>Retour</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Nom et Prénom -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="prenom" value="Prénom *" />
                                <TextInput
                                    id="prenom"
                                    v-model="form.prenom"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.prenom" />
                            </div>

                            <div>
                                <InputLabel for="nom" value="Nom *" />
                                <TextInput
                                    id="nom"
                                    v-model="form.nom"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.nom" />
                            </div>
                        </div>

                        <!-- Téléphone et Localité -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="telephone" value="Téléphone" />
                                <TextInput
                                    id="telephone"
                                    v-model="form.telephone"
                                    type="text"
                                    class="mt-1 block w-full"
                                    maxlength="20"
                                />
                                <InputError class="mt-2" :message="form.errors.telephone" />
                            </div>

                            <div>
                                <InputLabel for="localite_id" value="Localité" />
                                <select
                                    id="localite_id"
                                    v-model="form.localite_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option :value="null">Sélectionner une localité</option>
                                    <option
                                        v-for="localite in props.localites"
                                        :key="localite.id"
                                        :value="localite.id"
                                    >
                                        {{ localite.libelle }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.localite_id" />
                            </div>
                        </div>

                        <!-- Date d'adhésion -->
                        <div>
                            <InputLabel for="date_adhesion" value="Date d'adhésion" />
                            <TextInput
                                id="date_adhesion"
                                v-model="form.date_adhesion"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-2" :message="form.errors.date_adhesion" />
                        </div>

                        <!-- Adresse -->
                        <div>
                            <InputLabel for="adresse" value="Adresse" />
                            <Textarea
                                id="adresse"
                                v-model="form.adresse"
                                class="mt-1 block w-full"
                                rows="3"
                            />
                            <InputError class="mt-2" :message="form.errors.adresse" />
                        </div>

                        <!-- Statut et Compte utilisateur -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <!-- Statut (Actif) -->
                            <div class="flex items-start">
                                <ToggleSwitch
                                    id="statut"
                                    :checked="form.statut === 0"
                                    @update:checked="form.statut = $event ? 0 : 1"
                                />
                                <label
                                    for="statut"
                                    class="ml-3 block text-sm font-medium text-gray-900 cursor-pointer"
                                    @click="form.statut = form.statut === 0 ? 1 : 0"
                                >
                                    <span class="text-base font-semibold text-green-700">Membre actif</span>
                                    <span class="block text-xs text-gray-500 mt-1">
                                        Désactivez pour désactiver le membre
                                    </span>
                                </label>
                            </div>

                            <!-- Compte utilisateur (uniquement pour les admins) -->
                            <div v-if="isAdmin" class="flex items-start">
                                <ToggleSwitch
                                    id="est_utilisateur"
                                    :checked="isUtilisateur"
                                    @update:checked="isUtilisateur = $event"
                                />
                                <label
                                    for="est_utilisateur"
                                    class="ml-3 block text-sm font-medium text-gray-900 cursor-pointer"
                                    @click="isUtilisateur = !isUtilisateur"
                                >
                                    <span class="text-base font-semibold text-indigo-700">
                                        {{ hasAccount ? 'A un compte utilisateur' : 'Créer un compte utilisateur' }}
                                    </span>
                                    <span class="block text-xs text-gray-500 mt-1">
                                        {{ hasAccount ? 'Permet de modifier le compte' : 'Permet au membre de se connecter' }}
                                    </span>
                                </label>
                            </div>
                            <div v-else class="flex items-center p-3 bg-gray-100 rounded-lg">
                                <span class="text-sm text-gray-600">
                                    Seuls les administrateurs peuvent gérer les comptes utilisateurs.
                                </span>
                            </div>
                        </div>
                        <InputError class="mt-2" :message="form.errors.statut" />
                        <InputError class="mt-2" :message="form.errors.est_utilisateur" />

                        <!-- Formulaire de compte utilisateur conditionnel (uniquement pour les admins) -->
                        <div
                            v-if="isAdmin && isUtilisateur"
                            class="mt-6 p-6 bg-indigo-50 border border-indigo-200 rounded-lg space-y-4 transition-all duration-300"
                        >
                            <h3 class="text-lg font-semibold text-indigo-900 mb-4">
                                {{ hasAccount ? 'Modifier le compte utilisateur' : 'Créer un compte utilisateur' }}
                            </h3>

                            <div>
                                <InputLabel for="email" value="Email *" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    required
                                    autocomplete="username"
                                    placeholder="exemple@email.com"
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div v-if="hasAccount" class="flex items-start">
                                <ToggleSwitch
                                    id="modifier_mot_de_passe"
                                    :checked="modifierMotDePasse"
                                    @update:checked="modifierMotDePasse = $event"
                                />
                                <label
                                    for="modifier_mot_de_passe"
                                    class="ml-3 block text-sm font-medium text-gray-900 cursor-pointer"
                                    @click="modifierMotDePasse = !modifierMotDePasse"
                                >
                                    <span class="text-base font-semibold text-gray-700">Modifier le mot de passe</span>
                                    <span class="block text-xs text-gray-500 mt-1">
                                        Cochez pour changer le mot de passe
                                    </span>
                                </label>
                            </div>

                            <div v-if="!hasAccount || modifierMotDePasse" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="password" value="Mot de passe *" />
                                    <TextInput
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        class="mt-1 block w-full"
                                        :required="!hasAccount || modifierMotDePasse"
                                        autocomplete="new-password"
                                    />
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>

                                <div>
                                    <InputLabel
                                        for="password_confirmation"
                                        value="Confirmer le mot de passe *"
                                    />
                                    <TextInput
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        class="mt-1 block w-full"
                                        :required="!hasAccount || modifierMotDePasse"
                                        autocomplete="new-password"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.password_confirmation"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                            <Link :href="route('membres.show', membre.id)">
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

