<script setup>
import { computed } from 'vue';

const props = defineProps({
    reservations: {
        type: Array,
        default: () => [],
    },
    totalSpaces: {
        type: Number,
        required: true,
    },
    viewDate: {
        type: Date,
        required: true,
    }
});

const operatingHours = Array.from({ length: 16 }, (_, i) => i + 8); // 8 AM to 11 PM (23:00)

const hourlyOccupancy = computed(() => {
    const occupancy = {};
    operatingHours.forEach(hour => {
        occupancy[hour] = 0;
    });

    if (!props.reservations || props.reservations.length === 0) {
        return occupancy;
    }

    const viewDateStr = props.viewDate.toISOString().slice(0, 10);

    props.reservations.forEach(res => {
        const start = new Date(res.start);
        const end = new Date(res.end);

        // Check if the reservation is on the current view date
        if (start.toISOString().slice(0, 10) !== viewDateStr && end.toISOString().slice(0, 10) !== viewDateStr && start > props.viewDate && end < props.viewDate) {
            return;
        }

        for (let hour = start.getHours(); hour < end.getHours(); hour++) {
            if (operatingHours.includes(hour)) {
                occupancy[hour]++;
            }
        }
    });

    return occupancy;
});

const getHeatmapColor = (count) => {
    if (count === 0) return 'bg-gray-100 text-gray-400';
    if (!props.totalSpaces || props.totalSpaces === 0) return 'bg-gray-200';

    const percentage = (count / props.totalSpaces) * 100;
    if (percentage >= 80) return 'bg-red-500 text-white';
    if (percentage >= 60) return 'bg-orange-400 text-white';
    if (percentage >= 40) return 'bg-yellow-400 text-gray-800';
    if (percentage >= 20) return 'bg-green-400 text-gray-800';
    return 'bg-blue-300 text-white';
};

const formatHour = (hour) => {
    if (hour === 12) return '12 PM';
    if (hour > 12) return `${hour - 12} PM`;
    return `${hour} AM`;
};
</script>

<template>
    <div class="p-4 bg-white rounded-lg shadow-sm border border-gray-200">
        <h4 class="font-semibold text-gray-800 mb-3">Hourly Occupancy Summary</h4>
        <div class="grid grid-cols-8 md:grid-cols-16 gap-1">
            <div 
                v-for="hour in operatingHours" 
                :key="hour" 
                class="flex flex-col items-center"
            >
                <div 
                    class="w-full h-16 rounded-md flex items-center justify-center transition-all duration-300"
                    :class="getHeatmapColor(hourlyOccupancy[hour])"
                    :title="`${hourlyOccupancy[hour]} / ${totalSpaces} booked`"
                >
                    <span class="font-bold text-lg">{{ hourlyOccupancy[hour] }}</span>
                </div>
                <span class="text-xs text-gray-500 mt-1">{{ formatHour(hour) }}</span>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-end space-x-4 text-xs text-gray-600">
            <span>Legend:</span>
            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-blue-300 mr-1"></span> Low</div>
            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-green-400 mr-1"></span> Med</div>
            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-yellow-400 mr-1"></span> High</div>
            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-orange-400 mr-1"></span> Very High</div>
            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-red-500 mr-1"></span> Max</div>
        </div>
    </div>
</template>
