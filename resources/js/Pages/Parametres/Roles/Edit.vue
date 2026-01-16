<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Textarea from '@/Components/Textarea.vue';
import Checkbox from '@/Components/Checkbox.vue';
import MultiSelect from '@/Components/MultiSelect.vue';

const props = defineProps({
    role: Object,
    permissions: Object,
});

const form = useForm({
    nom: props.role.nom || '',
    description: props.role.description || '',
    permissions: props.role.permissions || [],
    actif: props.role.actif ?? true,
});

// Convertir les permissions en tableau pour le MultiSelect
const permissionsList = computed(() => {
    const perms = props.permissions || {};
    return Object.keys(perms).map(key => ({
        id: key,
        nom: perms[key],
    }));
});

onMounted(() => {
    // Initialiser les permissions sélectionnées
    if (props.role.permissions && Array.isArray(props.role.permissions)) {
        form.permissions = props.role.permissions;
    } else {
        form.permissions = [];
    }
});

const submit = () => {
    form.put(route('parametres.roles.update', props.role.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Modifier le Rôle - ${role.nom}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Modifier le Rôle
                </h2>
                <Link :href="route('parametres.roles.index')">
                    <SecondaryButton>Retour</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
                            <!-- Nom -->
                            <div>
                                <InputLabel for="nom" value="Nom du rôle *" />
                                <TextInput
                                    id="nom"
                                    v-model="form.nom"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.nom" />
                            </div>

                            <!-- Description -->
                            <div>
                                <InputLabel for="description" value="Description" />
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    class="mt-1 block w-full"
                                    rows="3"
                                />
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <!-- Permissions -->
                            <div>
                                <InputLabel for="permissions" value="Permissions" />
                                <MultiSelect
                                    id="permissions"
                                    :model-value="form.permissions"
                                    @update:model-value="form.permissions = $event"
                                    :options="permissionsList"
                                    option-label="nom"
                                    option-value="id"
                                    placeholder="Sélectionner des permissions..."
                                    search-placeholder="Rechercher une permission..."
                                    max-height="250px"
                                    :multiple="true"
                                />
                                <InputError class="mt-2" :message="form.errors.permissions" />
                            </div>

                            <!-- Statut -->
                            <div class="flex items-center">
                                <Checkbox
                                    id="actif"
                                    v-model:checked="form.actif"
                                />
                                <label for="actif" class="ms-2 text-sm text-gray-700">
                                    Rôle actif
                                </label>
                            </div>

                            <!-- Boutons -->
                            <div class="flex items-center justify-end gap-4">
                                <Link :href="route('parametres.roles.index')">
                                    <SecondaryButton>Annuler</SecondaryButton>
                                </Link>
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Enregistrer les modifications
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
