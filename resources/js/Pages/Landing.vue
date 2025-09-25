<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import logo from '@/../img/logo.png';

// Dynamically import slideshow images (support jpg/jpeg/png/webp)
const imageModules = import.meta.glob('../../img/slideshow/*.{jpg,jpeg,png,webp}', { eager: true, import: 'default' });
const slides = Object.keys(imageModules)
  .sort()
  .map((k, idx) => ({ id: idx, url: imageModules[k] }));

const current = ref(0);
const intervalMs = 5000; // 5s per slide
let timer;

function next() {
  if (!slides.length) return;
  current.value = (current.value + 1) % slides.length;
}
function go(i) { current.value = i; resetTimer(); }
function resetTimer() {
  if (timer) clearInterval(timer);
  if (slides.length > 1) timer = setInterval(next, intervalMs);
}

onMounted(() => { resetTimer(); });
onBeforeUnmount(() => { if (timer) clearInterval(timer); });

// Palette derived from existing accent blues (adjust if logo palette expands)
const palette = ['#2f4686', '#3956a3', '#4d6ec0'];
const accentGradient = computed(() => `linear-gradient(135deg, ${palette[0]} 0%, ${palette[1]} 45%, ${palette[2]} 100%)`);

defineProps({ canLogin: Boolean, canRegister: Boolean });
</script>

<template>
  <Head title="CO-Z Co-Workspace & Study Hub" />
  <div class="min-h-screen flex flex-col bg-white text-[#0b0c10] overflow-hidden">
    <!-- Header -->
    <header class="relative z-20 w-full flex items-center justify-between px-6 md:px-10 py-4 md:py-6">
      <div class="flex items-center gap-3">
        <img :src="logo" alt="CO-Z Co-Workspace & Study Hub" class="h-12 md:h-14 w-auto select-none" draggable="false" />
        <div class="hidden sm:flex gap-1" aria-hidden="true">
          <span v-for="(c,i) in palette" :key="i" class="h-2 w-5 rounded" :style="{ background:c }" />
        </div>
      </div>
      <div class="flex items-center gap-3">
        <Link v-if="canLogin" href="/login" class="inline-flex items-center gap-2 text-xs font-semibold text-[#2f4686] hover:text-[#3956a3] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3956a3] rounded-sm px-2 py-1">
          Login
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </Link>
      </div>
    </header>

    <!-- Main Hero -->
    <div class="relative flex-1 w-full flex">
      <!-- Text / CTA Panel -->
      <section class="relative z-20 w-full max-w-md md:max-w-lg xl:max-w-xl px-7 md:px-12 py-10 md:py-16 flex flex-col gap-8">
        <div class="space-y-5">
          <h1 class="text-3xl md:text-5xl font-bold leading-tight tracking-tight text-[#2f4686]">
            Manage your <span class="text-[#3956a3]">workspace</span><br class="hidden sm:block" /> with confidence.
          </h1>
          <p class="text-lg md:text-xl leading-relaxed text-[#0b0c10]/80 max-w-xl">
            Secure admin portal for booking oversight, customer records, and service coordination at CO-Z Co-Workspace & Study Hub.
          </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
          <Link href="/login" class="group inline-flex items-center justify-center gap-3 rounded-full px-7 py-3 text-lg font-semibold text-white shadow-lg shadow-[#2f4686]/25 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#3956a3] transition-colors" :style="{ background: accentGradient }">
            <span>Login to Portal</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 stroke-current" fill="none" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
          </Link>
        </div>
        <div class="pt-4 flex gap-6 text-base md:text-lg font-medium text-[#2f4686]/70">
          <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full" :style="{background:palette[0]}"/> Single Admin Security</div>
          <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full" :style="{background:palette[1]}"/> Bookings & Services</div>
          <div class="hidden sm:flex items-center gap-2"><span class="h-3 w-3 rounded-full" :style="{background:palette[2]}"/> Customer Records</div>
        </div>
      </section>

      <!-- Slideshow Background / Right Panel -->
      <section class="absolute inset-0 md:static md:flex-1">
        <!-- Layered gradient overlay for readability -->
        <div class="absolute inset-0 pointer-events-none" :style="{ background: 'linear-gradient(90deg, rgba(255,255,255,0.92) 0%, rgba(255,255,255,0.85) 40%, rgba(255,255,255,0.35) 70%, rgba(255,255,255,0.05) 100%)' }" />

        <!-- Slides container -->
        <div class="h-full w-full relative">
          <div v-for="slide in slides" :key="slide.id" class="absolute inset-0 transition-opacity duration-[1200ms] ease-out" :style="{ backgroundImage:`url(${slide.url})`, backgroundSize:'cover', backgroundPosition:'center', opacity: current===slide.id ? 1 : 0 }" />
          <!-- Fallback if no slides -->
          <div v-if="!slides.length" class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[#2f4686] to-[#3956a3] text-white text-lg">Add JPGs to /resources/img/slideshow</div>
        </div>

        <!-- Navigation dots -->
        <div v-if="slides.length" class="absolute bottom-4 right-5 flex gap-3 z-30">
          <button v-for="slide in slides" :key="'dot-'+slide.id" @click="go(slide.id)" class="h-4 w-4 rounded-full border border-white/60 transition-all duration-200" :class="current===slide.id ? 'bg-white shadow-md scale-125' : 'bg-white/30 hover:bg-white/60'" :aria-label="'Go to slide '+(slide.id+1)" />
        </div>
      </section>
    </div>

    <!-- Footer -->
    <footer class="relative z-20 py-6 px-8 text-base md:text-lg text-[#0b0c10]/60 flex flex-wrap items-center gap-6">
      <p class="m-0">&copy; {{ new Date().getFullYear() }} CO-Z Co-Workspace & Study Hub.</p>
      <div class="flex gap-3 items-center text-[#2f4686]/70">
        <span class="h-2 w-10 rounded-full" :style="{background:palette[0]}" />
        <span class="h-2 w-10 rounded-full" :style="{background:palette[1]}" />
        <span class="h-2 w-10 rounded-full" :style="{background:palette[2]}" />
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Extra subtle noise/texture (optional) */
/* intentionally left blank; add styles here if needed */
</style>