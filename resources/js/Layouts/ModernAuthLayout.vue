<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import logo from '../../img/logo.png';

const slides = [
    {
        image: 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=2069',
        title: 'Modern Co-Working Space',
        description: 'Discover your perfect workspace in a professional environment'
    },
    {
        image: 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?q=80&w=2069',
        title: 'Collaborative Environment',
        description: 'Connect with professionals and grow your network'
    },
    {
        image: 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?q=80&w=2070',
        title: 'Flexible Booking',
        description: 'Book by the hour, day, or month - whatever suits your needs'
    }
];

const currentSlide = ref(0);
let slideInterval = null;

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % slides.length;
};

const setSlide = (index) => {
    currentSlide.value = index;
};

onMounted(() => {
    slideInterval = setInterval(nextSlide, 5000);
});

onUnmounted(() => {
    if (slideInterval) {
        clearInterval(slideInterval);
    }
});
</script>

<template>
    <div class="min-h-screen flex">
        <!-- Left Side - Slideshow -->
        <div class="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-[#2f4686] to-[#1e2f5a] overflow-hidden">
            <!-- Logo Overlay -->
            <div class="absolute top-8 left-8 z-20">
                <img :src="logo" alt="CO-Z Logo" class="h-12 w-auto brightness-0 invert opacity-90" />
            </div>

            <!-- Slideshow Container -->
            <div class="relative w-full h-full">
                <TransitionGroup name="slide">
                    <div
                        v-for="(slide, index) in slides"
                        v-show="currentSlide === index"
                        :key="index"
                        class="absolute inset-0"
                    >
                        <div class="absolute inset-0 bg-black/40 z-10"></div>
                        <img
                            :src="slide.image"
                            :alt="slide.title"
                            class="w-full h-full object-cover"
                        />
                        <div class="absolute inset-0 z-20 flex flex-col justify-end p-12">
                            <h2 class="text-4xl font-bold text-white mb-4">{{ slide.title }}</h2>
                            <p class="text-xl text-white/90 max-w-md">{{ slide.description }}</p>
                        </div>
                    </div>
                </TransitionGroup>

                <!-- Slide Indicators -->
                <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-30 flex gap-2">
                    <button
                        v-for="(slide, index) in slides"
                        :key="index"
                        @click="setSlide(index)"
                        class="w-2 h-2 rounded-full transition-all duration-300"
                        :class="currentSlide === index ? 'bg-white w-8' : 'bg-white/50 hover:bg-white/75'"
                        :aria-label="`Go to slide ${index + 1}`"
                    ></button>
                </div>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-gray-50">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden flex justify-center mb-8">
                    <img :src="logo" alt="CO-Z Logo" class="h-16 w-auto" />
                </div>

                <!-- Form Content -->
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <slot />
                </div>

                <!-- Footer -->
                <p class="mt-8 text-center text-sm text-gray-600">
                    © {{ new Date().getFullYear() }} CO-Z Co-Workspace & Study Hub. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: opacity 1s ease;
}

.slide-enter-from {
    opacity: 0;
}

.slide-leave-to {
    opacity: 0;
}
</style>
