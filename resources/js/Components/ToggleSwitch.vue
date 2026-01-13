<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: Boolean,
        required: true,
    },
    id: {
        type: String,
        default: null,
    },
});

const toggle = () => {
    emit('update:checked', !props.checked);
};
</script>

<template>
    <button
        type="button"
        :id="id"
        role="switch"
        :aria-checked="checked"
        @click="toggle"
        :class="{
            'bg-indigo-600': checked,
            'bg-gray-300': !checked,
        }"
        class="check-input relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
    >
        <span
            :class="{
                'translate-x-5': checked,
                'translate-x-0': !checked,
            }"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
        />
    </button>
</template>

