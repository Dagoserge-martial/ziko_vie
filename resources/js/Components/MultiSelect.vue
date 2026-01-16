<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Array, String, Number],
        default: () => [],
    },
    options: {
        type: Array,
        required: true,
        default: () => [],
    },
    optionLabel: {
        type: String,
        default: 'nom',
    },
    optionValue: {
        type: String,
        default: 'id',
    },
    placeholder: {
        type: String,
        default: 'Sélectionner...',
    },
    searchPlaceholder: {
        type: String,
        default: 'Rechercher...',
    },
    maxHeight: {
        type: String,
        default: '200px',
    },
    multiple: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const dropdownRef = ref(null);
const searchInputRef = ref(null);

// Options filtrées selon la recherche
const filteredOptions = computed(() => {
    if (!searchQuery.value) {
        return props.options;
    }
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(option => {
        if (typeof option !== 'object') {
            return String(option).toLowerCase().includes(query);
        }
        
        // Rechercher dans le label principal
        const label = option[props.optionLabel] || option.nom || option.label || '';
        if (String(label).toLowerCase().includes(query)) {
            return true;
        }
        
        // Rechercher aussi dans prenom et nom si disponibles
        if (option.prenom && String(option.prenom).toLowerCase().includes(query)) {
            return true;
        }
        if (option.nom && String(option.nom).toLowerCase().includes(query)) {
            return true;
        }
        
        // Rechercher dans le nom complet (prenom + nom)
        if (option.prenom && option.nom) {
            const fullName = `${option.prenom} ${option.nom}`.toLowerCase();
            if (fullName.includes(query)) {
                return true;
            }
        }
        
        return false;
    });
});

// Options sélectionnées avec leurs labels
const selectedOptions = computed(() => {
    if (props.multiple) {
        // Mode multiple : modelValue est un tableau
        if (!props.modelValue || !Array.isArray(props.modelValue)) {
            return [];
        }
        return props.modelValue.map(value => {
            return props.options.find(opt => {
                const optValue = typeof opt === 'object' ? opt[props.optionValue] : opt;
                return optValue == value;
            });
        }).filter(Boolean);
    } else {
        // Mode simple : modelValue est une valeur unique
        if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
            return [];
        }
        const selected = props.options.find(opt => {
            const optValue = typeof opt === 'object' ? opt[props.optionValue] : opt;
            return optValue == props.modelValue;
        });
        return selected ? [selected] : [];
    }
});

// Vérifier si une option est sélectionnée
const isSelected = (option) => {
    const optionValue = typeof option === 'object' ? option[props.optionValue] : option;
    
    if (props.multiple) {
        // Mode multiple
        if (!props.modelValue || !Array.isArray(props.modelValue)) {
            return false;
        }
        return props.modelValue.some(v => v == optionValue);
    } else {
        // Mode simple
        return optionValue == props.modelValue;
    }
};

// Obtenir le label d'une option
const getLabel = (option) => {
    if (!option) return '';
    if (typeof option === 'object') {
        // Si l'option a un champ prenom et nom, les combiner
        if (option.prenom && option.nom) {
            return `${option.prenom} ${option.nom}`.trim();
        }
        return option[props.optionLabel] || option.nom || option.label || option.prenom || String(option);
    }
    return String(option);
};

// Toggle sélection d'une option
const toggleOption = (option) => {
    const optionValue = typeof option === 'object' ? option[props.optionValue] : option;
    
    if (props.multiple) {
        // Mode multiple
        const currentValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
        const index = currentValue.findIndex(v => v == optionValue);
        
        if (index > -1) {
            currentValue.splice(index, 1);
        } else {
            currentValue.push(optionValue);
        }
        
        emit('update:modelValue', currentValue);
    } else {
        // Mode simple : sélectionner/désélectionner
        if (optionValue == props.modelValue) {
            // Si déjà sélectionné, désélectionner
            emit('update:modelValue', null);
        } else {
            // Sinon, sélectionner
            emit('update:modelValue', optionValue);
        }
        // Fermer le dropdown après sélection en mode simple
        closeDropdown();
    }
};

// Retirer une option sélectionnée (uniquement en mode multiple)
const removeOption = (option, event) => {
    event.stopPropagation();
    if (!props.multiple) {
        emit('update:modelValue', null);
        return;
    }
    const optionValue = typeof option === 'object' ? option[props.optionValue] : option;
    const currentValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
    const newValue = currentValue.filter(v => v != optionValue);
    emit('update:modelValue', newValue);
};

// Annuler la sélection (mode simple)
const clearSelection = () => {
    emit('update:modelValue', null);
};

// Vérifier si toutes les options filtrées sont sélectionnées
const allFilteredSelected = computed(() => {
    if (!props.multiple || filteredOptions.value.length === 0) {
        return false;
    }
    const currentValue = Array.isArray(props.modelValue) ? props.modelValue : [];
    return filteredOptions.value.every(option => {
        const optionValue = typeof option === 'object' ? option[props.optionValue] : option;
        return currentValue.some(v => v == optionValue);
    });
});

// Sélectionner/Désélectionner toutes les options filtrées
const toggleSelectAll = () => {
    if (!props.multiple) return;
    
    if (allFilteredSelected.value) {
        // Désélectionner toutes les options filtrées
        const currentValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
        const filteredValues = filteredOptions.value.map(option => {
            const optionValue = typeof option === 'object' ? option[props.optionValue] : option;
            return optionValue;
        });
        const newValue = currentValue.filter(v => !filteredValues.some(fv => fv == v));
        emit('update:modelValue', newValue);
    } else {
        // Sélectionner toutes les options filtrées
        const currentValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
        const filteredValues = filteredOptions.value.map(option => {
            const optionValue = typeof option === 'object' ? option[props.optionValue] : option;
            return optionValue;
        });
        
        // Ajouter les nouvelles valeurs sans doublons
        filteredValues.forEach(fv => {
            if (!currentValue.some(v => v == fv)) {
                currentValue.push(fv);
            }
        });
        
        emit('update:modelValue', currentValue);
    }
};

// Fermer le dropdown si on clique en dehors
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

// Ouvrir le dropdown et focus sur la recherche
const openDropdown = () => {
    isOpen.value = true;
    setTimeout(() => {
        if (searchInputRef.value) {
            searchInputRef.value.focus();
        }
    }, 10);
};

// Fermer le dropdown
const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
};

watch(isOpen, (newValue) => {
    if (newValue) {
        document.addEventListener('click', handleClickOutside);
    } else {
        document.removeEventListener('click', handleClickOutside);
    }
});

onMounted(() => {
    if (isOpen.value) {
        document.addEventListener('click', handleClickOutside);
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <!-- Input principal avec tags -->
        <div
            @click="openDropdown"
            class="w-full rounded-md border border-gray-300 bg-white px-3 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer flex items-center"
            :class="{ 'ring-1 ring-indigo-500 border-indigo-500': isOpen, 'py-2': multiple, 'h-[38px]': !multiple }"
        >
            <div class="flex flex-wrap gap-1.5 items-center w-full" :class="multiple ? 'py-0.5' : ''">
                <!-- Mode multiple : Tags des éléments sélectionnés -->
                <template v-if="multiple">
                    <span
                        v-for="option in selectedOptions"
                        :key="typeof option === 'object' ? option[optionValue] : option"
                        class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 text-xs font-medium"
                    >
                        {{ getLabel(option) }}
                        <button
                            type="button"
                            @click.stop="removeOption(option, $event)"
                            class="text-indigo-600 hover:text-indigo-800 focus:outline-none"
                        >
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                    
                    <!-- Placeholder si rien n'est sélectionné -->
                    <span
                        v-if="selectedOptions.length === 0"
                        class="text-gray-400"
                    >
                        {{ placeholder }}
                    </span>
                </template>
                
                <!-- Mode simple : Afficher la valeur sélectionnée ou le placeholder -->
                <template v-else>
                    <span
                        v-if="selectedOptions.length > 0"
                        class="text-gray-900 flex items-center gap-2"
                    >
                        {{ getLabel(selectedOptions[0]) }}
                        <button
                            type="button"
                            @click.stop="clearSelection"
                            class="text-gray-400 hover:text-gray-600 focus:outline-none"
                            title="Annuler la sélection"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                    <span
                        v-else
                        class="text-gray-400"
                    >
                        {{ placeholder }}
                    </span>
                </template>
            </div>
            
            <!-- Icône flèche -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                <svg
                    class="h-5 w-5 text-gray-400 transition-transform"
                    :class="{ 'rotate-180': isOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Dropdown -->
        <div
            v-if="isOpen"
            class="absolute z-50 mt-1 w-full rounded-md bg-white shadow-lg border border-gray-200"
            :style="{ maxHeight: maxHeight }"
        >
            <!-- Barre de recherche -->
            <div class="p-2 border-b border-gray-200">
                <input
                    ref="searchInputRef"
                    v-model="searchQuery"
                    type="text"
                    :placeholder="searchPlaceholder"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    @click.stop
                />
            </div>

            <!-- Bouton Sélectionner tout (uniquement en mode multiple) -->
            <div v-if="multiple && filteredOptions.length > 0" class="px-4 py-2 border-b border-gray-200 bg-gray-50">
                <button
                    type="button"
                    @click.stop="toggleSelectAll"
                    class="w-full text-left text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                >
                    {{ allFilteredSelected ? 'Désélectionner tout' : 'Sélectionner tout' }}
                </button>
            </div>

            <!-- Liste des options -->
            <div class="overflow-y-auto" :style="{ maxHeight: `calc(${maxHeight} - 60px)` }">
                <div
                    v-if="filteredOptions.length === 0 && searchQuery"
                    class="px-4 py-3 text-sm text-gray-500 text-center"
                >
                    Aucun résultat trouvé
                </div>
                <div
                    v-else-if="filteredOptions.length === 0"
                    class="px-4 py-3 text-sm text-gray-500 text-center"
                >
                    Aucune option disponible
                </div>
                <div
                    v-for="option in filteredOptions"
                    :key="typeof option === 'object' ? option[optionValue] : option"
                    @click.stop="multiple ? toggleOption(option) : toggleOption(option)"
                    class="px-4 py-2 text-sm cursor-pointer hover:bg-indigo-50 transition-colors"
                    :class="{ 'bg-indigo-100 font-medium text-indigo-900': isSelected(option) }"
                >
                    <div class="flex items-center">
                        <input
                            v-if="multiple"
                            type="checkbox"
                            :checked="isSelected(option)"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mr-2"
                            @click.stop
                            @change="toggleOption(option)"
                        />
                        <span>
                            {{ getLabel(option) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
