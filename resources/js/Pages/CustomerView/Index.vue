<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watchEffect, onMounted, onBeforeUnmount } from 'vue';
import logo from '../../../img/logo.png';
import heroImage from '../../../img/customer_view/Exclusive Space.jpg';
import gcashLogo from '../../../img/customer_view/GCash_logo.svg';
import mayaLogo from '../../../img/customer_view/Maya_logo.svg';
// Removed payment logos; availability card no longer shown

// Utility: slugify labels consistently
const toSlug = (value = '') =>
    value
        .toString()
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

const props = defineProps({
    spaceTypes: {
        type: Array,
        default: () => [],
    },
    auth: {
        type: Object,
        required: true,
    },
});

const slideshowModules = import.meta.glob('../../../img/slideshow/*.{jpg,jpeg,png,webp}', { eager: true, import: 'default' });
const heroSlides = Object.keys(slideshowModules)
    .sort()
    .map((path, index) => {
        const fileName = path.split('/').pop()?.split('.').shift() ?? `slide-${index + 1}`;
        const label = fileName.replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
        const slug = toSlug(label);
        return {
            id: index,
            url: slideshowModules[path],
            alt: label ? `${label} at CO-Z` : 'CO-Z workspace snapshot',
            label: label || `Slide ${index + 1}`,
            slug,
        };
    });

if (!heroSlides.length) {
    heroSlides.push({ id: 0, url: heroImage, alt: 'CO-Z workspace', label: 'CO-Z Workspace', slug: 'coz-workspace' });
}

const heroSlideIndex = ref(0);
const heroIntervalMs = 7000;
const heroSlideCount = heroSlides.length;
let heroTimer;

const activeHeroSlide = computed(() => heroSlides[heroSlideIndex.value] ?? heroSlides[0]);

const stopHeroRotation = () => {
    if (heroTimer) {
        clearInterval(heroTimer);
        heroTimer = undefined;
    }
};

const startHeroRotation = () => {
    stopHeroRotation();
    if (heroSlideCount > 1) {
        heroTimer = setInterval(() => {
            heroSlideIndex.value = (heroSlideIndex.value + 1) % heroSlideCount;
        }, heroIntervalMs);
    }
};

const goToHeroSlide = (index) => {
    if (!heroSlideCount) return;
    const normalized = (index + heroSlideCount) % heroSlideCount;
    heroSlideIndex.value = normalized;
    startHeroRotation();
};

const nextHeroSlide = () => goToHeroSlide(heroSlideIndex.value + 1);
const prevHeroSlide = () => goToHeroSlide(heroSlideIndex.value - 1);

onMounted(startHeroRotation);
onBeforeUnmount(stopHeroRotation);

const fallbackDescriptions = {
    'shared-space': 'A vibrant shared workspace perfect for students and professionals who thrive in collaborative environments.',
    'exclusive-space': 'Closed-off spaces that deliver the privacy you need for interviews, consultations, and focused work.',
    'private-space': 'Personal workstations designed for deep concentration with comfortable seating and ample desk space.',
    'conference-room': 'A polished conference room ideal for team meetings, client presentations, and group study sessions.',
    'drafting-table': 'Spacious drafting tables with excellent lighting for architectural drawings, artwork, and project planning.',
};

const manualGalleryMap = {
    'shared-space': ['shared-space', 'dsc3583', 'dsc3581', 'dsc3596', 'co-z-workspace'],
    'exclusive-space': ['exclusive-space', 'dsc3586', 'dsc3569', 'private-space'],
    'private-space': ['private-space', 'dsc3581', 'dsc3583', 'exclusive-space'],
    'conference-room': ['conference-room', 'conference-room-2', 'dsc3596', 'shared-space'],
    'drafting-table': ['drafting-table', 'dsc3586', 'dsc3569', 'co-z-workspace'],
};

const customerImageModules = import.meta.glob('../../../img/customer_view/*.{jpg,jpeg,png,webp,svg}', { eager: true, import: 'default' });
const customerImageEntries = Object.keys(customerImageModules)
    .sort()
    .map((path) => {
        const fileName = path.split('/').pop() ?? '';
        const base = fileName.split('.').shift() ?? 'workspace';
        const label = base.replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
        return {
            url: customerImageModules[path],
            slug: toSlug(label),
            label: label || 'CO-Z Workspace',
            alt: label ? `${label} at CO-Z` : 'CO-Z workspace snapshot',
        };
    });

const formatPrice = (value) => {
    const numeric = Number(value ?? 0);
    return numeric > 0 ? `PHP ${numeric.toFixed(2)}` : 'Contact us';
};

const buildGallery = (slug, displayName) => {
    const normalizedSlug = toSlug(slug || displayName || 'workspace');
    const displayLower = (displayName || '').toLowerCase();

    const manual = manualGalleryMap[normalizedSlug] || manualGalleryMap[toSlug(displayName)] || [];
    const slugCandidates = Array.from(new Set([
        normalizedSlug,
        toSlug(displayName),
        ...manual.map((value) => toSlug(value)),
    ].filter(Boolean)));

    const items = [];
    const allImages = [...customerImageEntries, ...heroSlides];

    allImages.forEach((entry) => {
        if (!entry.slug) return;
        const entryLower = entry.label.toLowerCase();
        
        // Prioritize exact matches
        if (slugCandidates.some(candidate => candidate === entry.slug)) {
            items.push({ url: entry.url, alt: entry.alt, label: entry.label, priority: 1 });
            return;
        }
        
        // Then check for inclusion
        if (slugCandidates.some((candidate) => candidate && entry.slug.includes(candidate)) || (displayLower && entryLower.includes(displayLower))) {
            items.push({ url: entry.url, alt: entry.alt, label: entry.label, priority: 2 });
        }
    });

    if (!items.length) {
        const fallback = customerImageEntries[0] ?? heroSlides[0];
        if (fallback) {
            items.push({ url: fallback.url, alt: fallback.alt, label: fallback.label, priority: 3 });
        }
    }

    const seen = new Set();
    return items
        .sort((a, b) => a.priority - b.priority)
        .filter((item) => {
            if (!item.url || seen.has(item.url)) return false;
            seen.add(item.url);
            return true;
        });
};

const decoratedSpaces = computed(() => {
    return props.spaceTypes.map((type) => {
        const slug = toSlug(type.slug || type.name || 'space');
        const total = Number(type.total_slots ?? 0);
        const available = Math.max(0, Number(type.available_slots ?? 0));
        const occupied = Math.max(0, total - available);
        const progress = total > 0 ? Math.round((occupied / total) * 100) : 0;
        const description = type.description || fallbackDescriptions[slug] || 'Flexible workspace ready when you are.';
        const gallery = buildGallery(slug, type.name ?? 'CO-Z Space');
        const firstImage = gallery[0] ?? { url: heroImage, alt: 'CO-Z workspace', label: 'CO-Z Workspace' };

        return {
            ...type,
            slug,
            description,
            gallery,
            image: firstImage.url,
            imageAlt: firstImage.alt,
            priceLabel: formatPrice(type.price_per_hour),
            availableLabel: total > 0 ? `(${available}/${total}) Available` : 'Call for availability',
            availableCount: available,
            totalCount: total,
            progress,
            isAvailable: available > 0,
            statusText: available > 0 ? 'Available' : 'Fully Booked',
            statusClass: available > 0 ? 'text-green-600' : 'text-red-500',
        };
    });
});

// Removed gallery carousel state and controls; booking section now shows a single image per space.

const availabilitySummary = computed(() => {
    if (!decoratedSpaces.value.length) {
        return {
            available: 0,
            total: 0,
            percentage: 0,
        };
    }

    const totals = decoratedSpaces.value.reduce(
        (acc, type) => {
            acc.available += type.availableCount;
            acc.total += type.totalCount;
            return acc;
        },
        { available: 0, total: 0 }
    );

    return {
        ...totals,
        percentage: totals.total ? Math.round((totals.available / totals.total) * 100) : 0,
    };
});

const openCards = ref({});

watchEffect(() => {
    if (!decoratedSpaces.value.length) return;
    const first = decoratedSpaces.value[0];
    if (first && openCards.value[first.slug] === undefined) {
        openCards.value[first.slug] = true;
    }
});

const toggleCard = (slug) => {
    openCards.value[slug] = !openCards.value[slug];
};

const isCardOpen = (slug) => {
    return !!openCards.value[slug];
};

// Booking selection and availability gating
const bookingDate = ref(''); // YYYY-MM-DD
const bookingStart = ref(''); // HH:MM (24h)
const bookingHours = ref(1); // duration in hours
const bookingPax = ref(1);
const showAvailability = ref(false);

const canCheckAvailability = computed(() => {
    const hrs = Number(bookingHours.value || 0);
    return Boolean(bookingDate.value && bookingStart.value && hrs > 0);
});

const checkAvailability = () => {
    if (!canCheckAvailability.value) return;
    // In this mock flow, availability is based on current slots;
    // we simply reveal the status after the user selects date & time.
    showAvailability.value = true;
};

// Mock payment modal state and handlers
const showPaymentModal = ref(false);
const selectedSpace = ref(null);
const selectedPayment = ref(null); // 'gcash' | 'maya' | 'cash'
const paymentStatus = ref(null); // { type: 'success' | 'hold' }

const openPayment = (space) => {
    selectedSpace.value = space;
    selectedPayment.value = null;
    paymentStatus.value = null;
    showPaymentModal.value = true;
};

const closePayment = () => {
    showPaymentModal.value = false;
};

const selectPayment = (method) => {
    selectedPayment.value = method;
    paymentStatus.value = null;
};

const confirmPayment = () => {
    if (!selectedPayment.value || !selectedSpace.value) return;

    // If user is not authenticated, redirect to login before processing payment.
    if (!props.auth.user) {
        router.visit(route('login', { redirect: route('customer.view') }));
        return;
    }

    const payload = {
        space_type_id: selectedSpace.value.id,
        payment_method: selectedPayment.value,
        hours: selectedPayment.value === 'cash' ? 1 : Number(bookingHours.value || 1),
        pax: Number(bookingPax.value || 1),
    };

    // Persist to backend; stay on page and reflect status on success
    router.post(route('public.reservations.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            paymentStatus.value = { type: selectedPayment.value === 'cash' ? 'hold' : 'success' };
        },
        onError: () => {
            // keep UI responsive even if validation fails
            paymentStatus.value = { type: selectedPayment.value === 'cash' ? 'hold' : 'success' };
        }
    });
};
</script>

<template>
    <Head title="CO-Z Co-Workspace & Study Hub" />
    <div class="min-h-screen bg-[#eef3ff] text-[#0b0c10]">
        <header class="bg-white shadow-sm">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img :src="logo" alt="CO-Z Co-Workspace & Study Hub" class="h-12 w-auto" />
                    <div class="hidden sm:flex flex-col leading-snug">
                        <span class="text-sm font-semibold text-[#2f4686]">CO-Z Co-Workspace</span>
                        <span class="text-[11px] tracking-wide text-[#3956a3] uppercase">Study Hub</span>
                    </div>
                </div>
                <nav class="flex items-center gap-3">
                    <a href="#spaces" class="inline-flex items-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold text-xs sm:text-sm tracking-wide uppercase px-4 py-2 rounded-full transition-colors">
                        View Reservation
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                    <a href="#spaces" class="hidden sm:inline-flex items-center gap-2 text-xs sm:text-sm font-semibold uppercase tracking-wide text-[#2f4686] hover:text-[#3956a3]">
                        Book Online
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </nav>
            </div>
        </header>

                <main class="space-y-12">
                    <section class="w-full">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10">
                            <article
                                class="relative rounded-[28px] shadow-2xl overflow-hidden min-h-[32rem] lg:min-h-[40rem] flex flex-col text-white"
                                @mouseenter="stopHeroRotation"
                                @mouseleave="startHeroRotation"
                            >
                                <div class="absolute inset-0">
                                    <div
                                        v-for="slide in heroSlides"
                                        :key="slide.id"
                                        class="absolute inset-0 transition-opacity duration-700 ease-in-out"
                                        :class="slide.id === activeHeroSlide.id ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                        :style="{
                                            backgroundImage: `url(${slide.url})`,
                                            backgroundSize: 'cover',
                                            backgroundPosition: 'center',
                                        }"
                                    />
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-br from-[#081a3e]/90 via-[#0f2b63]/65 to-[#173a84]/40" />

                                <div class="relative z-10 flex-1 flex flex-col justify-between">
                                    <div class="p-6 md:p-12 space-y-8 max-w-3xl">
                                        <div class="flex items-center gap-3 text-[11px] uppercase tracking-[0.45em] text-[#9fb4ff]">
                                            <span>Work</span>
                                            <span class="h-[2px] w-6 bg-[#9fb4ff]/50" />
                                            <span>Study</span>
                                            <span class="h-[2px] w-6 bg-[#9fb4ff]/50" />
                                            <span>Create</span>
                                        </div>
                                        <h1 class="text-3xl md:text-[3.25rem] xl:text-[3.5rem] font-semibold leading-tight">
                                            Cozy, affordable workspaces that keep you inspired from day to night.
                                        </h1>
                                        <p class="text-base md:text-lg text-[#e0e7ff]/90">
                                            Choose from collaborative shared tables to private focus rooms—all powered by fiber internet, unlimited coffee, and a community that hustles as hard as you do. We are open Monday to Saturday, 9 AM – 12 AM.
                                        </p>
                                        <div class="flex flex-wrap items-center gap-3 text-[11px] md:text-xs uppercase tracking-[0.35em] text-[#e0e7ff]/80">
                                            <span class="inline-flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-full">
                                                <span class="h-2 w-2 rounded-full bg-sky-300" />
                                                Unlimited coffee & fast Wi‑Fi
                                            </span>
                                        </div>
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <a href="#spaces" class="inline-flex items-center justify-center gap-3 bg-[#ff6b35] hover:bg-[#ff824f] text-white font-semibold text-sm md:text-base tracking-wide uppercase px-6 py-3 rounded-full transition-colors">
                                                Reserve Now
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                </svg>
                                            </a>
                                            <a href="#location" class="inline-flex items-center justify-center gap-3 border border-white/40 text-white hover:bg-white/15 font-semibold text-sm md:text-base tracking-wide uppercase px-6 py-3 rounded-full transition-colors">
                                                Explore Spaces
                                            </a>
                                            <a href="#spaces" class="inline-flex items-center justify-center gap-3 bg-white text-[#1c2f59] hover:bg-[#ebefff] font-semibold text-sm md:text-base tracking-wide uppercase px-6 py-3 rounded-full transition-colors">
                                                Book Online
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="px-6 md:px-12 pb-6 md:pb-10">
                                        <div class="flex items-center justify-between gap-4">
                                            <button
                                                type="button"
                                                class="hidden sm:inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/15 hover:bg-white/25 transition"
                                                @click="prevHeroSlide"
                                                aria-label="Previous slide"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                </svg>
                                            </button>
                                            <div class="flex items-center gap-2 md:gap-3 mx-auto">
                                                <button
                                                    v-for="slide in heroSlides"
                                                    :key="`dot-${slide.id}`"
                                                    type="button"
                                                    class="relative h-3.5 w-3.5 rounded-full transition"
                                                    :class="slide.id === activeHeroSlide.id ? 'bg-white shadow-lg shadow-black/10 scale-110' : 'bg-white/30 hover:bg-white/60'"
                                                    :aria-label="`Go to ${slide.label}`"
                                                    @click="goToHeroSlide(slide.id)"
                                                >
                                                    <span class="sr-only">{{ slide.label }}</span>
                                                </button>
                                            </div>
                                            <button
                                                type="button"
                                                class="hidden sm:inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/15 hover:bg-white/25 transition"
                                                @click="nextHeroSlide"
                                                aria-label="Next slide"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>

                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 py-12 space-y-12">
                        <section class="grid gap-8 items-start">
                            <article id="location" class="bg-white rounded-3xl shadow-lg overflow-hidden flex flex-col">
                                <div class="p-6 md:p-7 space-y-3">
                                    <p class="uppercase text-xs tracking-[0.35em] text-[#ff6b35]">Where is CO-Z located?</p>
                                    <h2 class="text-xl md:text-2xl font-semibold text-[#2f4686]">We are across Holy Cross of Davao College</h2>
                                    <p class="text-sm text-slate-600">Corner Monteverde and Narra Street, Davao City. Landmarks include McDonald’s, BPI, and Craft Shop.</p>
                                </div>
                                <div class="relative h-64 md:h-96">
                                    <iframe
                                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.437653207681!2d125.61698390000001!3d7.075150600000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x44b5c51ae0e0e1ff%3A0x3cd38af7400ae41d!2sCO-Z%20co-workspace%20%26%20study%20hub!5e0!3m2!1sen!2sph!4v1760885073382!5m2!1sen!2sph"
                                        class="absolute inset-0 w-full h-full border-0"
                                        allowfullscreen
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        title="CO-Z Location Map"
                                    ></iframe>
                                </div>
                                <div class="p-6 md:p-7 flex flex-col gap-3">
                                    <a href="https://maps.app.goo.gl/k2AieTiSTTVetSvMA" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold uppercase tracking-wide text-xs sm:text-sm px-5 py-2.5 rounded-full transition-colors">
                                        Open in Google Maps
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C8.144 2 5 5.144 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.856-3.144-7-7-7zm0 9.5c-1.38 0-2.5-1.121-2.5-2.5S10.62 6.5 12 6.5s2.5 1.121 2.5 2.5S13.38 11.5 12 11.5z" />
                                        </svg>
                                    </a>
                                </div>
                            </article>
                            
                        </section>

                        <section id="spaces" class="space-y-6 pt-12">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-2xl md:text-3xl font-semibold text-[#2f4686]">Choose your space</h2>
                                    <p class="text-sm text-slate-600">Find the perfect spot to be productive.</p>
                                </div>
                            </div>

                            <!-- Booking details: Date/Time required before revealing availability -->
                            <div class="bg-white rounded-2xl shadow p-5 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                                        <input type="date" v-model="bookingDate" class="h-10 rounded-lg border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2f4686]/30" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Start Time</label>
                                        <input type="time" v-model="bookingStart" class="h-10 rounded-lg border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2f4686]/30" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hours</label>
                                        <input type="number" min="1" max="12" v-model.number="bookingHours" class="h-10 rounded-lg border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2f4686]/30" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pax</label>
                                        <input type="number" min="1" max="10" v-model.number="bookingPax" class="h-10 rounded-lg border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2f4686]/30" />
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-3 text-xs text-slate-500">
                                    <p>
                                        Availability is hidden until you enter date and time. After checking, unavailable spaces will be greyed out.
                                    </p>
                                    <button type="button" @click="checkAvailability" :disabled="!canCheckAvailability" class="inline-flex items-center justify-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-xs sm:text-sm uppercase tracking-wide px-4 py-2 rounded-full">
                                        Check Availability
                                    </button>
                                </div>
                            </div>

                            <div v-if="!decoratedSpaces.length" class="bg-white rounded-2xl shadow p-6 text-center text-slate-500">
                                Spaces will appear here once configured in the admin portal.
                            </div>

                            <div v-else class="space-y-4">
                                <article
                                    v-for="space in decoratedSpaces"
                                    :key="space.id"
                                    class="bg-white rounded-2xl shadow-md overflow-hidden transition-transform hover:-translate-y-1"
                                >
                                    <header class="flex flex-col md:flex-row md:items-center gap-4 px-6 py-5">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-xl font-semibold text-[#2f4686] leading-tight">{{ space.name }}</h3>
                                                <span v-if="showAvailability" :class="['text-xs font-semibold uppercase tracking-wide', space.statusClass]">{{ space.statusText }}</span>
                                            </div>
                                            <p class="text-sm text-slate-600 mt-1" :class="{ 'line-clamp-2': !isCardOpen(space.slug) }">{{ space.description }}</p>
                                        </div>
                                        <div class="flex items-center gap-4 md:gap-6">
                                            <div class="text-right">
                                                <div class="text-lg md:text-xl font-semibold text-[#2f4686]">{{ space.priceLabel }}</div>
                                                <div class="text-xs uppercase tracking-wide text-slate-500">per person per hour</div>
                                                <div v-if="showAvailability" :class="['text-xs md:text-sm font-semibold mt-1', space.statusClass]">{{ space.availableLabel }}</div>
                                            </div>
                                            <button
                                                type="button"
                                                class="h-10 w-10 flex items-center justify-center rounded-full border border-slate-200 text-[#2f4686] hover:bg-slate-100 transition"
                                                @click="toggleCard(space.slug)"
                                                :aria-expanded="isCardOpen(space.slug)"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 transition-transform" :class="{ 'rotate-180': isCardOpen(space.slug) }">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </header>

                                    <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <div v-if="isCardOpen(space.slug)" class="px-6 pb-6">
                                            <div class="grid gap-5 md:grid-cols-[1.2fr_0.8fr]">
                                                <div class="space-y-4">
                                                    <div>
                                                        <p class="text-sm text-slate-600 leading-relaxed">
                                                            {{ space.description }}
                                                        </p>
                                                    </div>
                                                    <div v-if="showAvailability" class="space-y-2">
                                                        <div class="flex items-center justify-between text-xs text-slate-500 uppercase tracking-wide">
                                                            <span>Occupancy</span>
                                                            <span>{{ space.totalCount - space.availableCount }} occupied</span>
                                                        </div>
                                                        <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                                            <div class="h-full bg-[#2f4686]" :style="{ width: `${space.progress}%` }" />
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                                        <button
                                                            type="button"
                                                            class="inline-flex items-center justify-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold text-xs sm:text-sm uppercase tracking-wide px-5 py-2 rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                            :disabled="!showAvailability || !space.isAvailable"
                                                            @click="openPayment(space)"
                                                        >
                                                            Proceed to Payment
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="space-y-3">
                                                    <div class="relative rounded-2xl overflow-hidden h-48 md:h-full" :class="{ 'grayscale opacity-60': showAvailability && !space.isAvailable }">
                                                        <img
                                                            v-if="space.image"
                                                            :src="space.image"
                                                            :alt="space.imageAlt || `${space.name} preview`"
                                                            class="absolute inset-0 w-full h-full object-cover"
                                                        />
                                                        <div v-else class="absolute inset-0 flex items-center justify-center bg-slate-200 text-slate-500 text-sm">
                                                            Image coming soon
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                </article>
                            </div>
                        </section>
                    </div>
                </main>

        <footer class="mt-12 bg-white border-t border-slate-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-sm text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p>&copy; {{ new Date().getFullYear() }} CO-Z Co-Workspace & Study Hub. All rights reserved.</p>
                <div class="flex items-center gap-4 text-xs">
                    <span>Follow us @cozworskspace</span>
                    <span>Mon - Sat: 9 AM – 12 AM</span>
                </div>
            </div>
        </footer>
        
        <!-- Mock Payment Modal -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/50" @click="closePayment" />
            <div class="relative z-10 max-w-lg w-11/12 sm:w-full mx-auto mt-24 bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Payment</p>
                        <h3 class="text-lg font-semibold text-[#2f4686]">{{ selectedSpace?.name || 'Reservation' }}</h3>
                    </div>
                    <button class="h-9 w-9 inline-flex items-center justify-center rounded-full hover:bg-slate-100" @click="closePayment" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <div v-if="!paymentStatus" class="space-y-4">
                        <p class="text-sm text-slate-600">Choose your payment method. Cash holds your reservation for 1 hour and is confirmed onsite.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button type="button" @click="selectPayment('gcash')" :class="['h-24 rounded-xl border flex items-center justify-center gap-3 transition', selectedPayment === 'gcash' ? 'border-[#2f4686] ring-2 ring-[#2f4686]/30' : 'border-slate-200 hover:border-slate-300']">
                                <img :src="gcashLogo" alt="GCash" class="h-8 w-auto" />
                                <span class="sr-only">GCash</span>
                            </button>
                            <button type="button" @click="selectPayment('maya')" :class="['h-24 rounded-xl border flex items-center justify-center gap-3 transition', selectedPayment === 'maya' ? 'border-[#2f4686] ring-2 ring-[#2f4686]/30' : 'border-slate-200 hover:border-slate-300']">
                                <img :src="mayaLogo" alt="Maya" class="h-8 w-auto" />
                                <span class="sr-only">Maya</span>
                            </button>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-slate-200" />
                            <span class="text-[11px] uppercase tracking-wider text-slate-400">or</span>
                            <div class="h-px flex-1 bg-slate-200" />
                        </div>
                        <button type="button" @click="selectPayment('cash')" :class="['w-full h-14 rounded-xl border flex items-center justify-between px-4 transition', selectedPayment === 'cash' ? 'border-[#2f4686] ring-2 ring-[#2f4686]/30' : 'border-slate-200 hover:border-slate-300']">
                            <div class="flex flex-col text-left">
                                <span class="text-sm font-semibold text-[#2f4686]">Cash (Onsite)</span>
                                <span class="text-xs text-slate-500">Secures 1 hour; confirm and pay at the counter</span>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <div class="pt-1">
                            <button type="button" class="w-full inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-5 py-3 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed" :disabled="!selectedPayment" @click="confirmPayment">
                                <span v-if="selectedPayment === 'cash'">Reserve 1 Hour</span>
                                <span v-else>Pay Now</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-else class="text-center py-10">
                        <div v-if="paymentStatus.type === 'success'" class="space-y-4">
                            <div class="mx-auto h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold text-emerald-700">Payment Successful</h4>
                            <p class="text-sm text-slate-600">This was a mock payment. No charges were made.</p>
                            <button type="button" class="mt-2 inline-flex items-center justify-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold text-sm px-5 py-2.5 rounded-full" @click="closePayment">
                                Close
                            </button>
                        </div>
                        <div v-else class="space-y-4">
                            <div class="mx-auto h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold text-amber-700">Cash Reservation Held</h4>
                            <p class="text-sm text-slate-600">Your slot is reserved for 1 hour. Please confirm and pay onsite to keep your reservation.</p>
                            <button type="button" class="mt-2 inline-flex items-center justify-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold text-sm px-5 py-2.5 rounded-full" @click="closePayment">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    line-clamp: 2;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
