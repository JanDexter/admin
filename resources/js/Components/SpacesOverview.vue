<script setup>
import { computed } from 'vue';

const props = defineProps({
    spaceTypes: { type: Array, default: () => [] },
});

const getStatusColor = (status) => {
    switch (status) {
        case 'available':
            return 'bg-green-500';
        case 'occupied':
            return 'bg-red-500';
        case 'maintenance':
            return 'bg-yellow-500';
        default:
            return 'bg-gray-400';
    }
};

const allSpaces = computed(() => {
    return (props.spaceTypes || []).flatMap(st => (st.spaces || []).map(s => ({
        ...s,
        spaceTypeName: st.name,
        current_customer: s.current_customer || s.currentCustomer || null,
    })));
});
</script>

<template>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Spaces Overview</h3>
        <div v-if="allSpaces.length === 0" class="text-center text-gray-500 py-8">No spaces to display.</div>
        <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-2">
            <div v-for="space in allSpaces" :key="space.id" class="relative group">
                <div :class="[getStatusColor(space.status), 'w-full h-16 rounded-lg flex items-center justify-center text-white font-bold text-sm']">
                    {{ space.name }}
                </div>
                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white text-xs p-2 rounded-b-lg opacity-0 group-hover:opacity-100 transition-opacity">
                    <p><strong>Type:</strong> {{ space.spaceTypeName }}</p>
                    <p><strong>Status:</strong> {{ space.status }}</p>
                    <p v-if="space.status === 'occupied'"><strong>Customer:</strong> {{ space.current_customer?.company_name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
