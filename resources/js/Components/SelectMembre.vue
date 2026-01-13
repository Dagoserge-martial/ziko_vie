<script setup>
import { computed, ref, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: null,
    },
    membres: {
        type: Array,
        required: true,
        default: () => [],
    },
    error: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

// État pour savoir si vue-select est disponible
const vueSelectAvailable = ref(false);
let vSelectComponent = null;

// Essayer de charger vue-select de manière asynchrone
onMounted(async () => {
    try {
        const vSelectModule = await import('vue-select');
        vSelectComponent = vSelectModule.default;
        await import('vue-select/dist/vue-select.css');
        vueSelectAvailable.value = true;
    } catch (error) {
        console.warn('vue-select n\'est pas disponible, utilisation d\'un select standard:', error);
        vueSelectAvailable.value = false;
    }
});

// Transformer les membres pour vue-select
const membresOptions = computed(() => {
    if (!props.membres || !Array.isArray(props.membres)) {
        return [];
    }
    try {
        return props.membres.map(membre => ({
            label: `${membre.prenom || ''} ${membre.nom || ''}`.trim(),
            value: membre.id,
        }));
    } catch (e) {
        console.error('Erreur lors de la transformation des membres:', e);
        return [];
    }
});

// Trouver le membre sélectionné pour vue-select
const selectedMembre = computed({
    get: () => {
        if (!props.modelValue) return null;
        try {
            const found = membresOptions.value.find(m => String(m.value) === String(props.modelValue));
            return found || null;
        } catch (e) {
            console.error('Erreur lors de la recherche du membre:', e);
            return null;
        }
    },
    set: (value) => {
        try {
            if (value && typeof value === 'object' && 'value' in value) {
                emit('update:modelValue', value.value);
            } else if (value === null || value === undefined) {
                emit('update:modelValue', null);
            } else {
                emit('update:modelValue', value);
            }
        } catch (e) {
            console.error('Erreur lors de la mise à jour de la valeur:', e);
        }
    },
});
</script>

<template>
    <div>
        <!-- Utiliser vue-select si disponible -->
        <component
            v-if="vueSelectAvailable && vSelectComponent && membresOptions.length > 0"
            :is="vSelectComponent"
            v-model="selectedMembre"
            :options="membresOptions"
            label="label"
            :reduce="(membre) => membre.value"
            placeholder="Rechercher un membre..."
            :searchable="true"
            :clearable="true"
            class="mt-1"
        >
            <template #no-options>
                Aucun membre trouvé
            </template>
        </component>
        
        <!-- Fallback sur select standard -->
        <select
            v-else
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :class="{ 'border-red-300': error }"
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
    </div>
</template>
