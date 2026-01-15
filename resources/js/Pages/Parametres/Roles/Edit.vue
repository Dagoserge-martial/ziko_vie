<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Textarea from '@/Components/Textarea.vue';
import Checkbox from '@/Components/Checkbox.vue';

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

const selectedPermissions = ref({});

onMounted(() => {
    // Initialiser les permissions sélectionnées
    if (props.role.permissions) {
        props.role.permissions.forEach((permission) => {
            selectedPermissions.value[permission] = true;
        });
    }
});

const togglePermission = (permission) => {
    const currentValue = selectedPermissions.value[permission];
    if (currentValue) {
        const newSelected = { ...selectedPermissions.value };
        delete newSelected[permission];
        selectedPermissions.value = newSelected;
    } else {
        selectedPermissions.value = {
            ...selectedPermissions.value,
            [permission]: true,
        };
    }
    form.permissions = Object.keys(selectedPermissions.value);
};

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
                                <InputLabel value="Permissions" />
                                <div class="mt-2 space-y-2 max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-4">
                                    <div
                                        v-for="(label, permission) in permissions"
                                        :key="permission"
                                        class="flex items-center"
                                    >
                                        <Checkbox
                                            :id="`permission-${permission}`"
                                            :checked="!!selectedPermissions[permission]"
                                            @update:checked="() => togglePermission(permission)"
                                        />
                                        <label
                                            :for="`permission-${permission}`"
                                            class="ms-2 text-sm text-gray-700 cursor-pointer"
                                            @click="togglePermission(permission)"
                                        >
                                            {{ label }}
                                        </label>
                                    </div>
                                </div>
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
