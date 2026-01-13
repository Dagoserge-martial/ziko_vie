<script setup>
import { watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { showFlashMessages } from '../plugins/sweetalert';

const page = usePage();

// Afficher les messages flash au montage
onMounted(() => {
    if (page.props.flash) {
        showFlashMessages(page.props.flash);
    }
});

// Surveiller les changements de messages flash lors des navigations
watch(
    () => page.props.flash,
    (flash) => {
        if (flash) {
            showFlashMessages(flash);
        }
    },
    { deep: true }
);
</script>

<template>
    <!-- Ce composant ne rend rien visuellement, il gère juste les messages flash -->
</template>

