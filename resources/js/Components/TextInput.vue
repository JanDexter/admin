<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
});

const emit = defineEmits(['update:modelValue']);
const input = ref(null);

onMounted(() => {
    if (input.value && input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

function updateValue(e) {
    emit('update:modelValue', e.target.value);
}

defineExpose({ focus: () => input.value && input.value.focus() });
</script>

<template>
    <input
        :type="props.type"
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        :value="props.modelValue"
        @input="updateValue"
        ref="input"
        v-bind="$attrs"
    />
</template>
