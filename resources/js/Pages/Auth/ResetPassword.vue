<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: 'zikobouevie', // Mot de passe par défaut
    password_confirmation: 'zikobouevie', // Confirmation du mot de passe par défaut
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            // Ne rien réinitialiser, le formulaire sera soumis avec le mot de passe par défaut
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Réinitialiser le mot de passe" />

        <div class="mb-4 text-sm text-gray-600">
            Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe avec
            le mot de passe par défaut "zikobouevie".
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Adresse e-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    readonly
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Réinitialiser le mot de passe
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
