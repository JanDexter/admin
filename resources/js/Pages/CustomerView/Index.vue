<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import logo from '../../../img/logo.png';
import heroImage from '../../../img/customer_view/Exclusive Space.jpg';
import gcashLogo from '../../../img/customer_view/GCash_logo.svg';
import mayaLogo from '../../../img/customer_view/Maya_logo.svg';
import SpaceCalendar from '../../Components/SpaceCalendar.vue';
import ReservationDetailModal from '../../Components/ReservationDetailModal.vue';
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
    reservations: {
        type: Array,
        default: () => [],
    },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const getManilaNow = () => {
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).formatToParts(new Date());

    const lookup = {};
    parts.forEach((part) => {
        if (part.type !== 'literal') {
            lookup[part.type] = part.value;
        }
    });

    return {
        date: `${lookup.year}-${lookup.month}-${lookup.day}`,
        time: `${lookup.hour}:${lookup.minute}`,
    };
};

const currentManilaTime = ref(getManilaNow());
let currentTimeTimer;

const syncCurrentManilaTime = () => {
    currentManilaTime.value = getManilaNow();
};


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

const handleVisibilityChange = () => {
    if (typeof document === 'undefined') return;
    if (document.visibilityState === 'visible') {
        syncCurrentManilaTime();
        startHeroRotation();
    } else {
        stopHeroRotation();
    }
};

onMounted(() => {
    syncCurrentManilaTime();
    startHeroRotation();
    currentTimeTimer = setInterval(syncCurrentManilaTime, 60000);

    if (typeof document !== 'undefined') {
        document.addEventListener('visibilitychange', handleVisibilityChange);
    }

    // Equal-height cards: recompute on mount and on resize
    if (typeof window !== 'undefined') {
        recomputeCardHeights();
        window.addEventListener('resize', recomputeCardHeights);
    }

    // Attach observer to keep cards equalized as content changes
    attachCardsResizeObserver();

    // Recompute after fonts load to capture any late reflow
    if (typeof document !== 'undefined' && document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => recomputeCardHeights());
    }
});

onBeforeUnmount(() => {
    stopHeroRotation();
    if (currentTimeTimer) {
        clearInterval(currentTimeTimer);
        currentTimeTimer = undefined;
    }

    if (toastTimerId) {
        clearTimeout(toastTimerId);
        toastTimerId = null;
    }

    if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
    }

    if (typeof document !== 'undefined') {
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    }

    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', recomputeCardHeights);
    }

    if (cardsResizeObserver) {
        try { cardsResizeObserver.disconnect(); } catch {}
        cardsResizeObserver = undefined;
    }
});

const fallbackDescriptions = {
    'shared-space': 'A vibrant shared workspace perfect for students and professionals who thrive in collaborative environments.',
    'exclusive-space': 'Closed-off spaces that deliver the privacy you need for interviews, consultations, and focused work.',
    'private-space': 'Personal workstations designed for deep concentration with comfortable seating and ample desk space.',
    'conference-room': 'A polished conference room ideal for team meetings, client presentations, and group study sessions.',
    'drafting-table': 'Spacious drafting tables with excellent lighting for architectural drawings, artwork, and project planning.',
};

const manualGalleryMap = {
    // Keep only space-specific or neutral aliases; avoid cross-referencing other spaces
    'shared-space': ['shared-space', 'dsc3583', 'dsc3581', 'dsc3596', 'co-z-workspace', 'co-z'],
    'exclusive-space': ['exclusive-space', 'dsc3586', 'dsc3569', 'co-z-workspace', 'co-z'],
    'private-space': ['private-space', 'dsc3581', 'dsc3583', 'co-z-workspace', 'co-z'],
    'conference-room': ['conference-room', 'conference-room-2', 'dsc3596', 'co-z-workspace', 'co-z'],
    'drafting-table': ['drafting-table', 'dsc3586', 'dsc3569', 'co-z-workspace', 'co-z'],
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
    const displaySlug = toSlug(displayName || '');
    const displayLower = (displayName || '').toLowerCase();

    // Manual aliases help widen the gallery, but must NEVER outrank the exact slug
    const manualAliases = manualGalleryMap[normalizedSlug] || manualGalleryMap[displaySlug] || [];
    const manualAliasSlugs = manualAliases.map((v) => toSlug(v)).filter(Boolean);

    // Only use customer-view images for matching; hero slides are fallback-only
    const candidates = customerImageEntries;

    const items = [];
    for (const entry of candidates) {
        if (!entry?.slug) continue;
        const entryLower = (entry.label || '').toLowerCase();

        // Priority 0: exact file slug equals normalizedSlug (strongest)
        if (entry.slug === normalizedSlug) {
            items.push({ url: entry.url, alt: entry.alt, label: entry.label, priority: 0 });
            continue;
        }

        // Priority 1: matches display name slug (if different from normalized)
        if (displaySlug && displaySlug !== normalizedSlug && entry.slug === displaySlug) {
            items.push({ url: entry.url, alt: entry.alt, label: entry.label, priority: 1 });
            continue;
        }

        // Priority 2: matches any manual alias exactly
        if (manualAliasSlugs.length && manualAliasSlugs.includes(entry.slug)) {
            items.push({ url: entry.url, alt: entry.alt, label: entry.label, priority: 2 });
            continue;
        }

        // Priority 3: fuzzy includes, very weak
        if (
            (normalizedSlug && entry.slug.includes(normalizedSlug)) ||
            (displayLower && entryLower.includes(displayLower))
        ) {
            items.push({ url: entry.url, alt: entry.alt, label: entry.label, priority: 3 });
            continue;
        }
    }

    // Fallback: if still nothing, show first customer image or a hero slide as last resort
    if (!items.length) {
        const fallback = customerImageEntries[0] ?? heroSlides[0];
        if (fallback) {
            items.push({ url: fallback.url, alt: fallback.alt, label: fallback.label, priority: 4 });
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

        // Prefer uploaded photo from admin if available; otherwise build gallery
        let gallery = [];
        if (type.photo_url) {
            gallery = [{ url: type.photo_url, alt: `${type.name} photo`, label: type.name }];
        } else {
            gallery = buildGallery(slug, type.name ?? 'CO-Z Space');
        }
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

// Equal-height cards across the grid: measure tallest and apply as min-height to all
const cardsContainer = ref(null);
const cardMinHeight = ref(null);
const cardTopMinHeight = ref(null); // keep title/description/discount block equal height
let cardsResizeObserver;

const recomputeCardHeights = async () => {
    await nextTick();
    // Clear previous minHeight to get natural height
    cardMinHeight.value = null;
    cardTopMinHeight.value = null;
    await nextTick();
    const root = cardsContainer.value;
    if (!root) return;
    const nodes = root.querySelectorAll('[data-space-card]');
    const topNodes = root.querySelectorAll('[data-card-top]');
    let max = 0;
    let topMax = 0;
    nodes.forEach((el) => {
        if (!(el instanceof HTMLElement)) return;
        el.style.minHeight = '';
        const h = el.getBoundingClientRect().height;
        if (h > max) max = h;
    });
    topNodes.forEach((el) => {
        if (!(el instanceof HTMLElement)) return;
        el.style.minHeight = '';
        const h = el.getBoundingClientRect().height;
        if (h > topMax) topMax = h;
    });
    if (max > 0) cardMinHeight.value = Math.ceil(max);
    if (topMax > 0) cardTopMinHeight.value = Math.ceil(topMax);
};

// Keep card heights synchronized when content/image sizes change
const attachCardsResizeObserver = () => {
    if (typeof ResizeObserver === 'undefined') return;
    if (cardsResizeObserver) {
        try { cardsResizeObserver.disconnect(); } catch {}
    }
    const root = cardsContainer.value;
    if (!root) return;
    cardsResizeObserver = new ResizeObserver(() => {
        requestAnimationFrame(() => recomputeCardHeights());
    });
    cardsResizeObserver.observe(root);
    root.querySelectorAll('[data-space-card]').forEach((el) => cardsResizeObserver.observe(el));
};

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

// Toast notifications
const toast = ref({ show: false, type: 'success', message: '' });
let toastTimerId = null;
const showToast = (message, type = 'success', duration = 3000) => {
    toast.value = { show: true, type, message };
    if (toastTimerId) clearTimeout(toastTimerId);
    toastTimerId = setTimeout(() => { toast.value.show = false; }, duration);
};

// Booking selection and availability gating
// Initialize with current Manila time
const initialManilaTime = getManilaNow();
const bookingDate = ref(initialManilaTime.date); // YYYY-MM-DD
const bookingStart = ref(initialManilaTime.time); // HH:MM (24h)
const bookingHours = ref(1); // duration in hours
const bookingPax = ref(1);
const showAvailability = ref(false);
const isAuthenticated = computed(() => Boolean(props.auth?.user));
const showAuthPrompt = ref(false);
const googleAuthUrl = computed(() => route('auth.google.redirect', { intent: 'customer' }));
const loginUrl = computed(() => route('login'));
const registerUrl = computed(() => route('register'));

const handleSignOut = () => {
    try {
        router.post(route('logout'));
    } catch (e) {
        // no-op; Inertia will handle navigation
    }
};

const handleViewReservationClick = (event) => {
    if (event?.preventDefault) event.preventDefault();
    if (!isAuthenticated.value) {
        showAuthPrompt.value = true;
        return;
    }
    if (typeof window !== 'undefined') {
        const target = document.getElementById('reservations');
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

watch(isAuthenticated, (value) => {
    if (value) {
        showAuthPrompt.value = false;
    }
});

const minBookingDate = computed(() => currentManilaTime.value.date);
const minBookingTime = computed(() =>
    bookingDate.value === currentManilaTime.value.date ? currentManilaTime.value.time : '00:00'
);

const enforceFutureBookingSelection = ([nextDate, nextStart, minDate, minTime]) => {
    if (!nextDate) {
        return;
    }

    if (nextDate < minDate) {
        bookingDate.value = minDate;
        nextDate = minDate;
    }

    if (nextDate !== minDate) {
        return;
    }

    if (!nextStart || nextStart < minTime) {
        bookingStart.value = minTime;
    }
};

watch(
    [bookingDate, bookingStart, () => currentManilaTime.value.date, () => currentManilaTime.value.time],
    enforceFutureBookingSelection,
    { immediate: true }
);

const availabilityData = ref(null);
const isCheckingAvailability = ref(false);

// Recompute card heights when availability toggles, spaces change, or images likely finish layout
watch([showAvailability, availabilityData], () => {
    recomputeCardHeights();
    attachCardsResizeObserver();
});

watch(() => props.spaceTypes, () => {
    recomputeCardHeights();
    attachCardsResizeObserver();
}, { deep: true });

// Also after hero slide changes, in case layout shifts
watch(() => activeHeroSlide.value, () => {
    recomputeCardHeights();
});

const canCheckAvailability = computed(() => {
    const hrs = Number(bookingHours.value || 0);
    const pax = Number(bookingPax.value || 0);
    return Boolean(bookingDate.value && bookingStart.value && hrs > 0 && pax > 0);
});

const checkAvailability = async () => {
    if (!canCheckAvailability.value) return;
    
    isCheckingAvailability.value = true;
    availabilityData.value = null;
    
    try {
        const startDateTime = `${bookingDate.value}T${bookingStart.value}:00`;
        
        // Use axios which is included with Laravel and handles CSRF automatically
        const { default: axios } = await import('axios');
        
        const response = await axios.post('/public/check-availability', {
            start_time: startDateTime,
            hours: Number(bookingHours.value || 1),
            pax: Number(bookingPax.value || 1),
        });
        
        if (response.data && response.data.availability) {
            availabilityData.value = response.data.availability;
            showAvailability.value = true;
        }
    } catch (error) {
        console.error('Error checking availability:', error);
        if (error.response?.data?.errors) {
            const firstError = Object.values(error.response.data.errors)[0];
            showToast(Array.isArray(firstError) ? firstError[0] : firstError, 'error', 5000);
        } else if (error.response?.data?.message) {
            showToast(error.response.data.message, 'error', 5000);
        } else {
            showToast('Failed to check availability. Please try again.', 'error', 5000);
        }
    } finally {
        isCheckingAvailability.value = false;
    }
};

// Update decorated spaces to reflect real-time availability
const decoratedSpacesWithAvailability = computed(() => {
    const requiredPax = Number(bookingPax.value || 1);

    return decoratedSpaces.value.map(space => {
        if (!availabilityData.value) {
            return space;
        }
        
        const availInfo = availabilityData.value.find(a => a.id === space.id);
        if (!availInfo) {
            return space;
        }
        
        const availableCount = Math.max(0, Number(availInfo.available_capacity ?? space.availableCount ?? 0));
        const totalCount = Number(space.totalCount ?? space.total_slots ?? 0);
        const occupiedCount = totalCount > 0 ? Math.max(0, totalCount - availableCount) : 0;
        const progress = totalCount > 0 ? Math.round((occupiedCount / totalCount) * 100) : 0;
        const canAccommodate = availInfo.can_accommodate ?? availInfo.is_available ?? false;
        const hasSomeCapacity = availableCount > 0;
        const statusText = canAccommodate
            ? 'Available'
            : hasSomeCapacity
                ? `Need ${requiredPax}, only ${availableCount} slot${availableCount === 1 ? '' : 's'} free`
                : 'Fully Booked';

        return {
            ...space,
            availableCount,
            isAvailable: canAccommodate,
            statusText,
            statusClass: canAccommodate ? 'text-green-600' : 'text-red-500',
            availableLabel: totalCount > 0 
                ? `(${availableCount}/${totalCount}) Available` 
                : 'Call for availability',
            progress,
            canAccommodate,
        };
    });
});

// Mock payment modal state and handlers
const showPaymentModal = ref(false);
const selectedSpace = ref(null);
const selectedPayment = ref(null); // 'gcash' | 'maya' | 'cash'
const paymentStatus = ref(null); // { type: 'success' | 'hold' }
const paymentStep = ref(1); // 1: customer details, 2: payment method, 3: confirmation

// Customer details form
const customerDetails = ref({
    name: '',
    email: '',
    phone: '',
    company_name: '',
});

const formErrors = ref({});

const openPayment = (space) => {
    if (!isAuthenticated.value) {
        showAuthPrompt.value = true;
        return;
    }
    
    // Must check availability first
    if (!showAvailability.value) {
        alert('Please check availability first by selecting a date, time, hours, and number of people, then clicking "Check Availability".');
        return;
    }
    
    // Check if space is available
    if (!space.isAvailable) {
        alert('This space is fully booked for the selected time. Please choose a different time or space.');
        return;
    }
    
    selectedSpace.value = space;
    selectedPayment.value = null;
    paymentStatus.value = null;
    paymentStep.value = 1;
    customerDetails.value = {
        name: props.auth.user?.name || '',
        email: props.auth.user?.email || '',
        phone: '',
        company_name: props.auth.user?.company_name || '',
    };
    formErrors.value = {};
    showPaymentModal.value = true;
};

const closePayment = () => {
    showPaymentModal.value = false;
    wifiCredentials.value = null;
    reservationTimer.value = null;
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
    }
};

const closeAuthPrompt = () => {
    showAuthPrompt.value = false;
};

const handleReserveClick = (event) => {
    if (event?.preventDefault) {
        event.preventDefault();
    }

    if (!isAuthenticated.value) {
        showAuthPrompt.value = true;
        return;
    }

    if (typeof window !== 'undefined') {
        const target = document.getElementById('spaces');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
};

const selectPayment = (method) => {
    selectedPayment.value = method;
    paymentStatus.value = null;
};

const validateCustomerDetails = () => {
    formErrors.value = {};
    
    if (!customerDetails.value.name.trim()) {
        formErrors.value.name = 'Name is required';
    }
    
    if (!customerDetails.value.email.trim()) {
        formErrors.value.email = 'Email is required';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerDetails.value.email)) {
        formErrors.value.email = 'Invalid email format';
    }
    
    if (!customerDetails.value.phone.trim()) {
        formErrors.value.phone = 'Phone number is required';
    } else if (!/^(\+63\d{10}|09\d{9})$/.test(customerDetails.value.phone.trim())) {
        formErrors.value.phone = 'Invalid phone number. Use +639XXXXXXXXX or 09XXXXXXXXX';
    }
    
    return Object.keys(formErrors.value).length === 0;
};

const proceedToPayment = () => {
    if (validateCustomerDetails()) {
        paymentStep.value = 2;
    }
};

const backToDetails = () => {
    paymentStep.value = 1;
};

const confirmPayment = () => {
    if (!selectedPayment.value || !selectedSpace.value) return;

    // Build start_time from booking date and time if available
    let startTime = null;
    if (bookingDate.value && bookingStart.value) {
        startTime = `${bookingDate.value}T${bookingStart.value}:00`;
    }

    // For GCash/Maya, show mock payment success immediately
    if (selectedPayment.value === 'gcash' || selectedPayment.value === 'maya') {
        paymentStatus.value = { type: 'success' };
        paymentStep.value = 3;
        
        // Generate WiFi credentials and start timer for immediate reservations
        const mockReservation = {
            id: Date.now(), // Mock ID
            start_time: startTime || new Date().toISOString(),
            end_time: startTime 
                ? new Date(new Date(startTime).getTime() + (Number(bookingHours.value || 1) * 60 * 60 * 1000)).toISOString()
                : new Date(Date.now() + (Number(bookingHours.value || 1) * 60 * 60 * 1000)).toISOString(),
        };
        startReservationTimer(mockReservation);
        
        // Still send to backend for record keeping
        const payload = {
            space_type_id: selectedSpace.value.id,
            payment_method: selectedPayment.value,
            hours: Number(bookingHours.value || 1),
            pax: Number(bookingPax.value || 1),
            customer_name: customerDetails.value.name,
            customer_email: customerDetails.value.email,
            customer_phone: customerDetails.value.phone,
            customer_company_name: customerDetails.value.company_name,
        };
        
        if (startTime) {
            payload.start_time = startTime;
        }
        
        router.post(route('public.reservations.store'), payload, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                // Update with real reservation data if available
                const reservation = page.props?.reservationCreated;
                if (reservation) {
                    mockReservation.id = reservation.id;
                    wifiCredentials.value = generateWiFiCredentials(reservation.id);
                }
            },
            onError: (errors) => {
                // Show error if booking fails
                paymentStatus.value = null;
                paymentStep.value = 1;
                if (errors.space_type_id) {
                    showToast(errors.space_type_id, 'error', 5000);
                } else {
                    showToast('Failed to create reservation. Please try again.', 'error', 5000);
                }
            },
        });
        return;
    }

    // For cash payment
    const payload = {
        space_type_id: selectedSpace.value.id,
        payment_method: selectedPayment.value,
        hours: 1, // Cash always reserves 1 hour
        pax: Number(bookingPax.value || 1),
        customer_name: customerDetails.value.name,
        customer_email: customerDetails.value.email,
        customer_phone: customerDetails.value.phone,
        customer_company_name: customerDetails.value.company_name,
    };
    
    if (startTime) {
        payload.start_time = startTime;
    }

    router.post(route('public.reservations.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            paymentStatus.value = { type: 'hold' };
            paymentStep.value = 3;
        },
        onError: (errors) => {
            paymentStatus.value = { type: 'hold' };
            paymentStep.value = 3;
            if (errors.space_type_id) {
                showToast(errors.space_type_id, 'error', 5000);
            }
        }
    });
};

const calculatePricing = (hours, spaceOverride = null) => {
    const space = spaceOverride ?? selectedSpace.value;

    if (!space) {
        return {
            hourlyRate: 0,
            hours: Number(hours) || 0,
            discountHours: 0,
            discountPercentage: 0,
            baseCost: 0,
            discountAmount: 0,
            finalCost: 0,
            qualifies: false,
        };
    }

    const rawHours = Number(hours) || 0;
    const normalizedHours = rawHours > 0 ? rawHours : 0;
    const hourlyRate = Number(
        space.price_per_hour ?? space.pricePerHour ?? space.hourly_rate ?? 0
    );
    const discountHours = Number(space.discount_hours ?? space.discountHours ?? 0);
    const discountPercentage = Number(
        space.discount_percentage ?? space.discountPercentage ?? 0
    );

    const baseCost = normalizedHours * hourlyRate;
    const qualifies =
        discountHours > 0 &&
        discountPercentage > 0 &&
        normalizedHours >= discountHours;
    const discountAmount = qualifies ? baseCost * (discountPercentage / 100) : 0;
    const finalCost = Math.max(baseCost - discountAmount, 0);

    return {
        hourlyRate,
        hours: normalizedHours,
        discountHours,
        discountPercentage,
        baseCost,
        discountAmount,
        finalCost,
        qualifies,
    };
};

const estimatedPricing = computed(() => {
    const hours = Math.max(1, Number(bookingHours.value || 1));
    return calculatePricing(hours);
});

const finalizedPricing = computed(() => {
    const hours = selectedPayment.value === 'cash'
        ? 1
        : Math.max(1, Number(bookingHours.value || 1));

    return calculatePricing(hours);
});

// Calendar and reservation management
const activeTab = ref(null);
const selectedReservation = ref(null);
const showReservationDetail = ref(false);

const reservationsBySpaceType = computed(() => {
    const grouped = {};
    props.reservations.forEach(res => {
        const typeId = res.space_type_id;
        if (!grouped[typeId]) grouped[typeId] = [];
        grouped[typeId].push(res);
    });
    return grouped;
});

const openReservationDetail = (reservation) => {
    selectedReservation.value = reservation;
    showReservationDetail.value = true;
};

const closeReservationDetail = () => {
    showReservationDetail.value = false;
    selectedReservation.value = null;
};

const refreshReservations = () => {
    router.reload({ only: ['reservations'] });
};

// WiFi Credentials Generator
const generateWiFiCredentials = (reservationId) => {
    // Generate mock credentials based on reservation ID and timestamp
    const timestamp = Date.now();
    const ssid = 'COZ-WORKSPACE';
    const username = `user_${reservationId}_${timestamp.toString().slice(-6)}`;
    const password = btoa(`${reservationId}${timestamp}`).slice(0, 12).toUpperCase();
    
    return {
        ssid,
        username,
        password,
        expiresAt: null, // Will be set based on reservation end time
    };
};

const wifiCredentials = ref(null);
const reservationTimer = ref(null);
const timerInterval = ref(null);

const startReservationTimer = (reservation) => {
    if (!reservation || !reservation.start_time) return;
    
    const updateTimer = () => {
        const now = new Date();
        const start = new Date(reservation.start_time);
        const end = reservation.end_time ? new Date(reservation.end_time) : null;
        
        // Check if reservation has started
        if (now >= start) {
            if (!wifiCredentials.value) {
                wifiCredentials.value = generateWiFiCredentials(reservation.id);
                if (end) {
                    wifiCredentials.value.expiresAt = end;
                }
            }
            
            if (end) {
                const timeRemaining = end - now;
                if (timeRemaining > 0) {
                    const hours = Math.floor(timeRemaining / (1000 * 60 * 60));
                    const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);
                    reservationTimer.value = {
                        hours,
                        minutes,
                        seconds,
                        total: timeRemaining,
                        started: true,
                    };
                } else {
                    reservationTimer.value = { hours: 0, minutes: 0, seconds: 0, total: 0, started: true };
                    if (timerInterval.value) {
                        clearInterval(timerInterval.value);
                        timerInterval.value = null;
                    }
                }
            }
        } else {
            // Reservation hasn't started yet - show countdown to start
            const timeToStart = start - now;
            const hours = Math.floor(timeToStart / (1000 * 60 * 60));
            const minutes = Math.floor((timeToStart % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeToStart % (1000 * 60)) / 1000);
            reservationTimer.value = {
                hours,
                minutes,
                seconds,
                total: timeToStart,
                started: false,
            };
        }
    };
    
    updateTimer();
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }
    timerInterval.value = setInterval(updateTimer, 1000);
};

const copyToClipboard = async (text, label = 'Text') => {
    try {
        await navigator.clipboard.writeText(text);
        showToast(`${label} copied to clipboard!`, 'success', 2000);
    } catch (err) {
        showToast('Failed to copy to clipboard', 'error', 3000);
    }
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
                    <a href="#reservations" @click.prevent="handleViewReservationClick" class="inline-flex items-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold text-xs sm:text-sm tracking-wide uppercase px-4 py-2 rounded-full transition-colors">
                        View Reservation
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                    <button
                        v-if="!isAuthenticated"
                        type="button"
                        @click="showAuthPrompt = true"
                        class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold uppercase tracking-wide text-[#2f4686] hover:text-[#3956a3]"
                    >
                        Sign in
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="handleSignOut"
                        class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold uppercase tracking-wide text-[#2f4686] hover:text-[#3956a3]"
                    >
                        Sign out
                    </button>
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
                                        <div class="flex">
                                            <a
                                                href="#spaces"
                                                @click.prevent="handleReserveClick"
                                                class="inline-flex items-center justify-center gap-3 bg-[#ff6b35] hover:bg-[#ff824f] text-white font-semibold text-sm md:text-base tracking-wide uppercase px-6 py-3 rounded-full transition-colors"
                                            >
                                                Reserve Now
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
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
                                        <input
                                            type="date"
                                            v-model="bookingDate"
                                            :min="minBookingDate"
                                            class="h-10 rounded-lg border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2f4686]/30"
                                        />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Start Time</label>
                                        <input
                                            type="time"
                                            v-model="bookingStart"
                                            :min="minBookingTime"
                                            class="h-10 rounded-lg border border-slate-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2f4686]/30"
                                        />
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
                                    <button type="button" @click="checkAvailability" :disabled="!canCheckAvailability || isCheckingAvailability" class="inline-flex items-center justify-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-xs sm:text-sm uppercase tracking-wide px-4 py-2 rounded-full">
                                        <svg v-if="isCheckingAvailability" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>{{ isCheckingAvailability ? 'Checking...' : 'Check Availability' }}</span>
                                    </button>
                                </div>
                            </div>

                            <div v-if="!decoratedSpacesWithAvailability.length" class="bg-white rounded-2xl shadow p-6 text-center text-slate-500">
                                Spaces will appear here once configured in the admin portal.
                            </div>

                            <div v-else ref="cardsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
                                <article
                                    v-for="space in decoratedSpacesWithAvailability"
                                    :key="space.id"
                                    class="bg-white rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl flex flex-col h-full"
                                    :class="{ 'grayscale opacity-70': showAvailability && !space.isAvailable }"
                                    data-space-card
                                    :style="cardMinHeight ? { minHeight: cardMinHeight + 'px' } : undefined"
                                >
                                    <!-- Image at the top -->
                                    <div class="relative h-56 overflow-hidden">
                                        <img
                                            v-if="space.image"
                                            :src="space.image"
                                            :alt="space.imageAlt || `${space.name} preview`"
                                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 hover:scale-110"
                                            loading="lazy"
                                            sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw"
                                            @load="recomputeCardHeights"
                                        />
                                        <div v-else class="absolute inset-0 flex items-center justify-center bg-slate-200 text-slate-500 text-sm">
                                            Image coming soon
                                        </div>
                                        <!-- Status badge overlay -->
                                        <div v-if="showAvailability" class="absolute top-3 right-3">
                                            <span :class="['inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide shadow-lg backdrop-blur-sm', space.isAvailable ? 'bg-green-500/90 text-white' : 'bg-red-500/90 text-white']">
                                                <span class="h-2 w-2 rounded-full" :class="space.isAvailable ? 'bg-white' : 'bg-white/80'" />
                                                {{ space.statusText }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex flex-col flex-1 p-5 space-y-4">
                                        <div data-card-top :style="cardTopMinHeight ? { minHeight: cardTopMinHeight + 'px' } : undefined">
                                            <h3 class="text-xl font-bold text-[#2f4686] leading-tight mb-2 line-clamp-2">{{ space.name }}</h3>
                                            <p class="text-sm text-slate-600 leading-relaxed line-clamp-3">{{ space.description }}</p>
                                            <div
                                                v-if="space.discount_percentage && space.discount_hours"
                                                class="mt-3 inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Save {{ space.discount_percentage }}% when you book {{ space.discount_hours }}+ hrs</span>
                                            </div>
                                        </div>

                                        <!-- Price -->
                                        <div class="border-t border-slate-200 pt-3">
                                            <div class="flex items-baseline justify-between">
                                                <div>
                                                    <div class="text-2xl font-bold text-[#2f4686]">{{ space.priceLabel }}</div>
                                                    <div class="text-xs uppercase tracking-wide text-slate-500">per person per hour</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Availability info -->
                                        <div v-if="showAvailability" class="space-y-2">
                                            <div class="flex items-center justify-between text-xs text-slate-600">
                                                <span class="font-semibold">{{ space.availableLabel }}</span>
                                                <span class="text-slate-500">{{ space.totalCount - space.availableCount }} occupied</span>
                                            </div>
                                            <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-[#2f4686] transition-all duration-300" :style="{ width: `${space.progress}%` }" />
                                            </div>
                                        </div>

                                        <!-- Action button -->
                                        <div class="pt-2 mt-auto">
                                            <button
                                                type="button"
                                                class="w-full inline-flex items-center justify-center gap-2 text-white font-bold text-sm uppercase tracking-wide px-5 py-3 rounded-xl transition-colors shadow-md"
                                                :class="!showAvailability || !space.isAvailable 
                                                    ? 'bg-gray-400 cursor-not-allowed opacity-60' 
                                                    : 'bg-[#2f4686] hover:bg-[#3956a3] hover:shadow-lg'"
                                                :disabled="!showAvailability || !space.isAvailable"
                                                @click="openPayment(space)"
                                            >
                                                <span v-if="!showAvailability">Check Availability First</span>
                                                <span v-else-if="!space.isAvailable">Fully Booked</span>
                                                <span v-else>Book Now</span>
                                                <svg v-if="showAvailability && space.isAvailable" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>
                </main>

        <section v-if="auth.user && reservations.length > 0" id="reservations" class="py-12 bg-gradient-to-b from-white to-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Your Reservations</h2>
                    <p class="text-gray-600">View and manage your space bookings with real-time status updates</p>
                </div>

                <!-- Tabs for each space type -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-4 overflow-x-auto" aria-label="Tabs">
                            <button
                                v-for="spaceType in spaceTypes.filter(st => reservationsBySpaceType[st.id]?.length > 0)"
                                :key="spaceType.id"
                                @click="activeTab = spaceType.id"
                                :class="[
                                    activeTab === spaceType.id || (activeTab === null && spaceType === spaceTypes.find(st => reservationsBySpaceType[st.id]?.length > 0))
                                        ? 'border-[#2f4686] text-[#2f4686]'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-6 border-b-2 font-semibold text-sm transition-colors'
                                ]"
                            >
                                {{ spaceType.name }}
                                <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-bold"
                                      :class="activeTab === spaceType.id || (activeTab === null && spaceType === spaceTypes.find(st => reservationsBySpaceType[st.id]?.length > 0))
                                          ? 'bg-[#2f4686] text-white'
                                          : 'bg-gray-200 text-gray-600'">
                                    {{ reservationsBySpaceType[spaceType.id]?.length || 0 }}
                                </span>
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Calendar for each space type -->
                <div v-for="spaceType in spaceTypes" :key="spaceType.id">
                    <div
                        v-show="activeTab === spaceType.id || (activeTab === null && spaceType === spaceTypes.find(st => reservationsBySpaceType[st.id]?.length > 0))"
                        v-if="reservationsBySpaceType[spaceType.id]?.length > 0"
                    >
                        <SpaceCalendar
                            :space-type="spaceType"
                            :reservations="reservationsBySpaceType[spaceType.id]"
                            @open-detail="openReservationDetail"
                            @refresh="refreshReservations"
                        />
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!Object.keys(reservationsBySpaceType).length" class="text-center py-12 bg-white rounded-2xl shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">No reservations yet</h3>
                    <p class="mt-2 text-gray-600">Start by booking a space above</p>
                </div>
            </div>
        </section>

        <!-- Reservation Detail Modal -->
        <ReservationDetailModal
            v-if="selectedReservation"
            :reservation="selectedReservation"
            :show="showReservationDetail"
            @close="closeReservationDetail"
            @updated="refreshReservations"
        />

        <footer class="mt-12 bg-white border-t border-slate-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-sm text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p>&copy; {{ new Date().getFullYear() }} CO-Z Co-Workspace & Study Hub. All rights reserved.</p>
                <div class="flex items-center gap-4 text-xs">
                    <span>Follow us @cozworskspace</span>
                    <span>Mon - Sat: 9 AM – 12 AM</span>
                </div>
            </div>
        </footer>
        
        <!-- Payment Modal with Customer Details -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/50" @click="closePayment" />
            <div class="relative z-10 max-w-lg w-11/12 sm:w-full mx-auto mt-16 mb-8 bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between px-5 py-4 border-b">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">
                            Step {{ paymentStep }} of 3
                        </p>
                        <h3 class="text-lg font-semibold text-[#2f4686]">{{ selectedSpace?.name || 'Reservation' }}</h3>
                    </div>
                    <button class="h-9 w-9 inline-flex items-center justify-center rounded-full hover:bg-slate-100" @click="closePayment" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="overflow-y-auto flex-1">
                    <div class="p-5 space-y-4">
                        <!-- Step 1: Customer Details -->
                        <div v-if="paymentStep === 1" class="space-y-4">
                            <div>
                                <p class="text-sm font-semibold text-[#2f4686] mb-1">Enter Your Details</p>
                                <p class="text-xs text-slate-500">We need your information to process the reservation.</p>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        v-model="customerDetails.name" 
                                        type="text" 
                                        placeholder="Juan Dela Cruz"
                                        class="w-full h-11 rounded-lg border px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2f4686]/30 transition"
                                        :class="formErrors.name ? 'border-red-500' : 'border-slate-300'"
                                    />
                                    <p v-if="formErrors.name" class="text-xs text-red-500 mt-1">{{ formErrors.name }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        v-model="customerDetails.email" 
                                        type="email" 
                                        placeholder="juan@example.com"
                                        class="w-full h-11 rounded-lg border px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2f4686]/30 transition"
                                        :class="formErrors.email ? 'border-red-500' : 'border-slate-300'"
                                    />
                                    <p v-if="formErrors.email" class="text-xs text-red-500 mt-1">{{ formErrors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        v-model="customerDetails.phone" 
                                        type="tel" 
                                        placeholder="09123456789"
                                        class="w-full h-11 rounded-lg border px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2f4686]/30 transition"
                                        :class="formErrors.phone ? 'border-red-500' : 'border-slate-300'"
                                    />
                                    <p v-if="formErrors.phone" class="text-xs text-red-500 mt-1">{{ formErrors.phone }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Format: 09XXXXXXXXX or +639XXXXXXXXX</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                        Company Name (Optional)
                                    </label>
                                    <input 
                                        v-model="customerDetails.company_name" 
                                        type="text" 
                                        placeholder="e.g., Mega Corporation"
                                        class="w-full h-11 rounded-lg border border-slate-300 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2f4686]/30 transition"
                                    />
                                </div>
                            </div>

                            <div class="bg-slate-50 rounded-xl p-4 space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Space:</span>
                                    <span class="font-semibold text-[#2f4686]">{{ selectedSpace?.name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Date:</span>
                                    <span class="font-semibold text-[#2f4686]">{{ bookingDate || 'Not selected' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Start Time:</span>
                                    <span class="font-semibold text-[#2f4686]">{{ bookingStart || 'Not selected' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Duration:</span>
                                    <span class="font-semibold text-[#2f4686]">{{ bookingHours }} hour(s)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Pax:</span>
                                    <span class="font-semibold text-[#2f4686]">{{ bookingPax }} person(s)</span>
                                </div>
                                <div class="h-px bg-slate-200 my-1"></div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Base Cost</span>
                                    <span class="font-semibold text-[#2f4686]">{{ formatCurrency(estimatedPricing.baseCost || 0) }}</span>
                                </div>
                                <div
                                    v-if="estimatedPricing.qualifies"
                                    class="flex justify-between text-emerald-600 font-semibold"
                                >
                                    <span>Discount ({{ estimatedPricing.discountPercentage }}% for {{ estimatedPricing.discountHours }}+ hrs)</span>
                                    <span>-{{ formatCurrency(estimatedPricing.discountAmount || 0) }}</span>
                                </div>
                                <div
                                    v-else-if="estimatedPricing.discountPercentage && estimatedPricing.discountHours"
                                    class="text-[11px] text-slate-500"
                                >
                                    Book {{ estimatedPricing.discountHours }}+ hours to save {{ estimatedPricing.discountPercentage }}%.
                                </div>
                                <div class="flex justify-between text-sm font-bold text-[#2f4686]">
                                    <span>Estimated Total</span>
                                    <span>{{ formatCurrency(estimatedPricing.finalCost || 0) }}</span>
                                </div>
                            </div>

                            <button 
                                type="button" 
                                class="w-full inline-flex items-center justify-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold text-sm px-5 py-3 rounded-xl transition-colors"
                                @click="proceedToPayment"
                            >
                                Continue to Payment
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </button>
                        </div>

                        <!-- Step 2: Payment Method -->
                        <div v-if="paymentStep === 2" class="space-y-4">
                            <div>
                                <p class="text-sm font-semibold text-[#2f4686] mb-1">Choose Payment Method</p>
                                <p class="text-xs text-slate-500">Select how you'd like to pay for your reservation.</p>
                            </div>

                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-xs text-amber-800">
                                    <strong>Note:</strong> GCash and Maya payments are mock transactions for demonstration purposes only. No actual charges will be made.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button type="button" @click="selectPayment('gcash')" :class="['h-24 rounded-xl border flex items-center justify-center gap-3 transition', selectedPayment === 'gcash' ? 'border-[#2f4686] ring-2 ring-[#2f4686]/30 bg-[#2f4686]/5' : 'border-slate-200 hover:border-slate-300']">
                                    <img :src="gcashLogo" alt="GCash" class="h-8 w-auto" />
                                    <span class="sr-only">GCash</span>
                                </button>
                                <button type="button" @click="selectPayment('maya')" :class="['h-24 rounded-xl border flex items-center justify-center gap-3 transition', selectedPayment === 'maya' ? 'border-[#2f4686] ring-2 ring-[#2f4686]/30 bg-[#2f4686]/5' : 'border-slate-200 hover:border-slate-300']">
                                    <img :src="mayaLogo" alt="Maya" class="h-8 w-auto" />
                                    <span class="sr-only">Maya</span>
                                </button>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-px flex-1 bg-slate-200" />
                                <span class="text-[11px] uppercase tracking-wider text-slate-400">or</span>
                                <div class="h-px flex-1 bg-slate-200" />
                            </div>
                            <button type="button" @click="selectPayment('cash')" :class="['w-full h-14 rounded-xl border flex items-center justify-between px-4 transition', selectedPayment === 'cash' ? 'border-[#2f4686] ring-2 ring-[#2f4686]/30 bg-[#2f4686]/5' : 'border-slate-200 hover:border-slate-300']">
                                <div class="flex flex-col text-left">
                                    <span class="text-sm font-semibold text-[#2f4686]">Cash (Onsite)</span>
                                    <span class="text-xs text-slate-500">Secures 1 hour; confirm and pay at the counter</span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <div class="flex gap-3 pt-2">
                                <button 
                                    type="button" 
                                    class="flex-1 inline-flex items-center justify-center gap-2 border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm px-5 py-3 rounded-xl transition-colors"
                                    @click="backToDetails"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                                    </svg>
                                    Back
                                </button>
                                <button 
                                    type="button" 
                                    class="flex-1 inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-5 py-3 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed transition-colors" 
                                    :disabled="!selectedPayment" 
                                    @click="confirmPayment"
                                >
                                    <span v-if="selectedPayment === 'cash'">Reserve 1 Hour</span>
                                    <span v-else>Complete Payment</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Confirmation -->
                        <div v-if="paymentStep === 3" class="text-center py-10">
                            <div v-if="paymentStatus.type === 'success'" class="space-y-4">
                                <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-emerald-700">Payment Successful!</h4>
                                
                                <!-- WiFi Credentials Card -->
                                <div v-if="wifiCredentials && reservationTimer" class="bg-blue-50 border-2 border-blue-300 rounded-xl p-4 text-left space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                                            </svg>
                                            <p class="text-sm font-bold text-blue-900">WiFi Access Credentials</p>
                                        </div>
                                        <div v-if="reservationTimer.started" class="bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-mono font-bold">
                                            {{ String(reservationTimer.hours).padStart(2, '0') }}:{{ String(reservationTimer.minutes).padStart(2, '0') }}:{{ String(reservationTimer.seconds).padStart(2, '0') }}
                                        </div>
                                        <div v-else class="bg-amber-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                            Starts in {{ String(reservationTimer.hours).padStart(2, '0') }}:{{ String(reservationTimer.minutes).padStart(2, '0') }}:{{ String(reservationTimer.seconds).padStart(2, '0') }}
                                        </div>
                                    </div>
                                    
                                    <div v-if="reservationTimer.started" class="space-y-2">
                                        <div class="bg-white rounded-lg p-3 space-y-1">
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-blue-700 font-semibold">Network Name (SSID)</span>
                                                <button @click="copyToClipboard(wifiCredentials.ssid, 'SSID')" class="text-blue-600 hover:text-blue-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            <p class="font-mono text-sm font-bold text-blue-900">{{ wifiCredentials.ssid }}</p>
                                        </div>
                                        
                                        <div class="bg-white rounded-lg p-3 space-y-1">
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-blue-700 font-semibold">Username</span>
                                                <button @click="copyToClipboard(wifiCredentials.username, 'Username')" class="text-blue-600 hover:text-blue-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            <p class="font-mono text-sm font-bold text-blue-900">{{ wifiCredentials.username }}</p>
                                        </div>
                                        
                                        <div class="bg-white rounded-lg p-3 space-y-1">
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-blue-700 font-semibold">Password</span>
                                                <button @click="copyToClipboard(wifiCredentials.password, 'Password')" class="text-blue-600 hover:text-blue-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            <p class="font-mono text-sm font-bold text-blue-900">{{ wifiCredentials.password }}</p>
                                        </div>
                                        
                                        <p class="text-[10px] text-blue-700 italic flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            Credentials valid for {{ finalizedPricing.hours }} hour(s) starting from {{ bookingStart || 'now' }}
                                        </p>
                                    </div>
                                    
                                    <div v-else class="text-center py-4">
                                        <p class="text-sm text-blue-800 font-semibold">WiFi credentials will be available when your reservation starts</p>
                                        <p class="text-xs text-blue-600 mt-1">Check back in {{ String(reservationTimer.hours).padStart(2, '0') }}:{{ String(reservationTimer.minutes).padStart(2, '0') }}:{{ String(reservationTimer.seconds).padStart(2, '0') }}</p>
                                    </div>
                                </div>
                                
                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-left space-y-2">
                                    <p class="text-sm text-emerald-900 font-semibold">Reservation Confirmed</p>
                                    <div class="text-xs text-emerald-800 space-y-1">
                                        <p><strong>Name:</strong> {{ customerDetails.name }}</p>
                                        <p><strong>Email:</strong> {{ customerDetails.email }}</p>
                                        <p><strong>Phone:</strong> {{ customerDetails.phone }}</p>
                                        <p v-if="customerDetails.company_name"><strong>Company:</strong> {{ customerDetails.company_name }}</p>
                                        <p><strong>Space:</strong> {{ selectedSpace?.name }}</p>
                                        <p><strong>Date:</strong> {{ bookingDate }}</p>
                                        <p><strong>Time:</strong> {{ bookingStart }}</p>
                                        <p><strong>Duration:</strong> {{ finalizedPricing.hours }} hour(s)</p>
                                        <p><strong>Rate per Hour:</strong> {{ formatCurrency(finalizedPricing.hourlyRate || 0) }}</p>
                                        <p v-if="finalizedPricing.qualifies"><strong>Discount Applied:</strong> {{ finalizedPricing.discountPercentage }}% (saved {{ formatCurrency(finalizedPricing.discountAmount || 0) }})</p>
                                        <p v-else-if="finalizedPricing.discountPercentage && finalizedPricing.discountHours"><strong>Discount Info:</strong> Save {{ finalizedPricing.discountPercentage }}% on {{ finalizedPricing.discountHours }}+ hour bookings.</p>
                                        <p><strong>Total Paid:</strong> {{ formatCurrency(finalizedPricing.finalCost || 0) }}</p>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 italic">Note: This was a mock payment for demonstration purposes. No actual charges were made.</p>
                                <button type="button" class="mt-2 inline-flex items-center justify-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold text-sm px-6 py-2.5 rounded-full" @click="closePayment">
                                    Close
                                </button>
                            </div>
                            <div v-else class="space-y-4">
                                <div class="mx-auto h-16 w-16 rounded-full bg-amber-100 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-amber-700">Reservation Held</h4>
                                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-left space-y-2">
                                    <p class="text-sm text-amber-900 font-semibold">Your slot is reserved for 1 hour</p>
                                    <div class="text-xs text-amber-800 space-y-1">
                                        <p><strong>Name:</strong> {{ customerDetails.name }}</p>
                                        <p><strong>Email:</strong> {{ customerDetails.email }}</p>
                                        <p><strong>Phone:</strong> {{ customerDetails.phone }}</p>
                                        <p v-if="customerDetails.company_name"><strong>Company:</strong> {{ customerDetails.company_name }}</p>
                                        <p><strong>Space:</strong> {{ selectedSpace?.name }}</p>
                                        <p><strong>Duration Held:</strong> {{ finalizedPricing.hours }} hour(s)</p>
                                        <p><strong>Amount Due Onsite:</strong> {{ formatCurrency(finalizedPricing.finalCost || 0) }}</p>
                                    </div>
                                </div>
                                <p class="text-sm text-slate-600">Please visit the counter within 1 hour to confirm and pay for your reservation.</p>
                                <button type="button" class="mt-2 inline-flex items-center justify-center gap-2 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold text-sm px-6 py-2.5 rounded-full" @click="closePayment">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showAuthPrompt" class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/60" @click="closeAuthPrompt" />
            <div class="relative z-10 max-w-md w-11/12 sm:w-full mx-auto mt-24 bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-5 border-b flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Sign in required</p>
                        <h3 class="text-lg font-semibold text-[#2f4686]">Log in to reserve your space</h3>
                    </div>
                    <button class="h-9 w-9 inline-flex items-center justify-center rounded-full hover:bg-slate-100" @click="closeAuthPrompt" aria-label="Close auth prompt">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-slate-600">Create an account or log in to book a workspace and manage your reservations.</p>
                    <div class="space-y-3">
                        <a :href="route('register')" class="flex items-center justify-center gap-2 rounded-xl bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold text-sm px-4 py-3 transition-colors">
                            Create an Account
                        </a>
                        <a :href="route('login')" class="flex items-center justify-center gap-2 rounded-xl border border-[#2f4686] text-[#2f4686] hover:bg-[#eef2ff] font-semibold text-sm px-4 py-3 transition-colors">
                            Log In
                        </a>
                        <a :href="googleAuthUrl" class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 hover:border-slate-300 text-slate-700 font-semibold text-sm px-4 py-3 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 48 48">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.28 9.21 3.77l6.84-6.84C35.1 2.53 29.92 0 24 0 14.64 0 6.51 5.38 2.69 13.22l7.98 6.2C12.37 13.99 17.74 9.5 24 9.5z" />
                                <path fill="#4285F4" d="M46.5 24c0-1.63-.15-3.2-.42-4.71H24v9.02h12.7c-.55 2.97-2.21 5.5-4.7 7.19l7.18 5.59C43.73 36.79 46.5 30.86 46.5 24z" />
                                <path fill="#FBBC05" d="M10.67 28.02c-.48-1.45-.76-3-.76-4.59s.27-3.14.76-4.59l-7.98-6.2C.96 15.82 0 19.29 0 23c0 3.71.96 7.18 2.69 10.36l7.98-5.34z" />
                                <path fill="#34A853" d="M24 46c5.92 0 11.09-1.95 14.78-5.29l-7.18-5.59c-2 1.35-4.56 2.14-7.6 2.14-6.26 0-11.62-4.49-13.35-10.52l-7.98 5.34C6.51 42.62 14.64 48 24 48z" />
                                <path fill="none" d="M0 0h48v48H0z" />
                            </svg>
                            Continue with Google
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div v-if="toast.show" class="fixed top-4 right-4 z-[9999]">
        <div 
            class="px-4 py-3 rounded shadow-lg border text-sm min-w-[250px]"
            :class="toast.type === 'success' ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200'"
        >
            {{ toast.message }}
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
.line-clamp-3 {
    display: -webkit-box;
    line-clamp: 3;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
