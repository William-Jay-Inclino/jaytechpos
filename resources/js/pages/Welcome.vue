<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const screenshots = [
    { src: '/images/simplepos/new-sale-1920.jpg', caption: 'POS Screen' },
    { src: '/images/simplepos/home-1920.jpg', caption: 'Home' },
    { src: '/images/simplepos/utang-payment-1920.jpg', caption: 'Utang Payment' },
    { src: '/images/simplepos/expenses-1920.jpg', caption: 'Expenses' },
    { src: '/images/simplepos/products-1920.jpg', caption: 'Products' },
    { src: '/images/simplepos/customers-1920.jpg', caption: 'Customers' },
];

// Mobile-specific screenshot filenames live in /public/images/simplepos-mobile
const screenshotsMobile = [
    { src: '/images/simplepos-mobile/new-sale.jpg', caption: 'POS Screen' },
    { src: '/images/simplepos-mobile/new-sale-checkout.jpg', caption: 'POS Screen 2' },
    { src: '/images/simplepos-mobile/home.jpg', caption: 'Home' },
    { src: '/images/simplepos-mobile/cashflow.jpg', caption: 'Cash Flow' },
    { src: '/images/simplepos-mobile/customers.jpg', caption: 'Customers' },
    { src: '/images/simplepos-mobile/customer-info.jpg', caption: 'Customer Info' },
];

const idx = ref(0);
const showLightbox = ref(false);
const direction = ref<'left'|'right'>('right');

// Note: mobile screenshots are provided in `screenshotsMobile`. We no longer build srcsets by name.

// touch swipe support
let touchStartX = 0;
let touchDeltaX = 0;
const SWIPE_THRESHOLD = 50;

function onTouchStart(e: TouchEvent) {
    touchStartX = e.touches[0].clientX;
    touchDeltaX = 0;
    stopAutoplay();
}

function onTouchMove(e: TouchEvent) {
    touchDeltaX = e.touches[0].clientX - touchStartX;
}

function onTouchEnd() {
    if (Math.abs(touchDeltaX) > SWIPE_THRESHOLD) {
        if (touchDeltaX > 0) {
            direction.value = 'left';
            prev();
        } else {
            direction.value = 'right';
            next();
        }
    }
    startAutoplay();
}

function select(i: number) {
    if (i === idx.value) return;
    direction.value = i > idx.value ? 'right' : 'left';
    idx.value = i;
}

const current = computed(() => screenshots[idx.value]);
const currentMobile = computed(() => screenshotsMobile[idx.value]);

function next() {
    direction.value = 'right';
    idx.value = (idx.value + 1) % screenshots.length;
}

function prev() {
    direction.value = 'left';
    idx.value = (idx.value - 1 + screenshots.length) % screenshots.length;
}

function openLightbox(i = 0) {
    idx.value = i;
    showLightbox.value = true;
}

function closeLightbox() {
    showLightbox.value = false;
}

// autoplay
let timer: ReturnType<typeof setInterval> | null = null;
function startAutoplay() {
    stopAutoplay();
    timer = setInterval(() => {
        next();
    }, 4000);
}

function stopAutoplay() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

onMounted(() => {
    startAutoplay();
    // keyboard nav
    const onKey = (e: KeyboardEvent) => {
        if (showLightbox.value) {
            if (e.key === 'ArrowLeft') prev();
            if (e.key === 'ArrowRight') next();
            if (e.key === 'Escape') closeLightbox();
        } else {
            if (e.key === 'ArrowLeft') prev();
            if (e.key === 'ArrowRight') next();
        }
    };
    window.addEventListener('keydown', onKey);
    onUnmounted(() => {
        window.removeEventListener('keydown', onKey);
    });
});

onUnmounted(() => {
    stopAutoplay();
});
</script>

<template>
    <Head title="Welcome to SimplePOS">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <!-- App favicon and touch icon using provided logo -->
        <link rel="icon" type="image/png" sizes="32x32" href="/images/simplepos/simplePOS-logo.png" />
        <link rel="apple-touch-icon" href="/images/simplepos/simplePOS-logo.png" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <meta name="theme-color" content="#0b0b0b" media="(prefers-color-scheme: dark)" />
        <meta name="theme-color" content="#f5f0eb" media="(prefers-color-scheme: light)" />
    </Head>

    <div class="min-h-screen bg-gradient-to-b from-white via-amber-50 to-amber-100 dark:from-[#070707] dark:via-[#0f0f0f] dark:to-[#0a0a0a]">
        <div class="mx-auto max-w-6xl px-6 py-10">
            <!-- Header / Nav -->
            <!-- Make header stack on small screens and keep nav right-aligned on larger screens -->
            <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <img src="/images/simplepos/simplePOS-logo.png" alt="SimplePOS logo" class="w-32 h-32 rounded-full object-cover" />
                        <div>
                            <h2 class="text-3xl font-semibold text-primary-gradient">SimplePOS</h2>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Made for sari-sari stores and small businesses.</p>
                        </div>
                    </div>

                <nav class="flex flex-wrap gap-3 text-sm w-full sm:w-auto justify-end">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md bg-white/60 dark:bg-white/5 px-4 py-2 text-neutral-800 dark:text-neutral-200 shadow-sm hover:bg-white dark:hover:bg-white/10"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-4 py-2 text-neutral-800 dark:text-neutral-200 hover:underline"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-md bg-amber-600 px-4 py-2 text-white shadow hover:bg-amber-700 dark:shadow-lg"
                        >
                            Create account
                        </Link>
                    </template>
                </nav>
            </header>

            <!-- Hero (single-column to avoid empty right gutter) -->
            <section class="mt-10 grid gap-8 lg:grid-cols-1 lg:items-start">
                <div class="mx-auto lg:mx-0 max-w-2xl">
                    
                    <p class="mt-4 text-neutral-600 dark:text-neutral-300">
                        Magbenta nang mabilis, i-manage ang gastos at kita, at magbigay ng utang na may automatic monthly interest — mas mabilis ang daloy ng negosyo at may dagdag kita pa.
                    </p>

                    <!-- Hide primary CTAs on very small screens to reduce clutter -->
                    <div class="mt-6 hidden sm:flex flex-wrap gap-3">
                        <Link
                            :href="register()"
                            class="inline-flex items-center gap-2 rounded-md bg-amber-600 px-5 py-3 text-white shadow-lg hover:bg-amber-700"
                        >
                            Get started — Create account
                        </Link>
                        <Link
                            :href="login()"
                            class="inline-flex items-center gap-2 rounded-md px-5 py-3 text-neutral-700 dark:text-neutral-300 bg-white/60 dark:bg-white/5 hover:underline"
                        >
                            Already have an account?
                        </Link>
                    </div>

                    <!-- Key selling points -->
                    <div class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800">
                            <h4 class="font-semibold">Sales & Payments</h4>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Mabilis na checkout at malinaw na cash tracking para sa mga busy na counter.</p>
                        </div>
                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800">
                            <h4 class="font-semibold">Utang & Interest</h4>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Pahintulutan ang customers na bumili nang pa-utang. Automatic na monthly interest calculation para sa dagdag kita.</p>
                        </div>
                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800">
                            <h4 class="font-semibold">Products & Customers</h4>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Madaling product management at customer profiles na may kasamang balance tracking.</p>
                        </div>
                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800">
                            <h4 class="font-semibold">Reports & Cash Flow</h4>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Sales reports at cash flow monitoring para mas mapalago ang negosyo.</p>
                        </div>
                    </div>
                </div>

                
            </section>

            <!-- Large Carousel (below CTAs) -->
            <section class="mt-10">
                <div class="mx-auto max-w-6xl">
                    <div class="rounded-xl bg-white p-6 shadow-lg dark:bg-[#0b0b0b] relative">
                        <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <div class="text-sm text-neutral-500 dark:text-neutral-400 text-center sm:text-right">Tap or click any image to enlarge</div>
                            <p class="sr-only">Images open a larger view when clicked. Use left/right arrow keys or swipe to navigate. Press Escape to close.</p>
                        </div>

                        <div class="relative" @mouseenter="stopAutoplay" @mouseleave="startAutoplay" @touchstart.passive="onTouchStart" @touchmove.passive="onTouchMove" @touchend.passive="onTouchEnd">
                            <transition :name="`slide-${direction}`" mode="out-in">
                                <picture>
                                    <source media="(max-width: 768px)" :srcset="currentMobile.src" />
                                    <img :key="current.src" :src="current.src" sizes="(max-width: 768px) 100vw, 1200px" :alt="current.caption" class="w-full h-[48vh] sm:h-[56vh] md:h-[64vh] object-cover rounded-md" @click="openLightbox(idx)" />
                                </picture>
                            </transition>

                            <!-- Inline overlay controls (inside carousel) -->
                            <button @click="prev" aria-label="Previous" class="absolute left-4 bottom-4 rounded-full lightbox-btn p-4 sm:p-3 shadow-lg inline-flex z-20">‹</button>
                            <button @click="next" aria-label="Next" class="absolute right-4 bottom-4 rounded-full lightbox-btn p-4 sm:p-3 shadow-lg inline-flex z-20">›</button>

                            <!-- Caption -->
                            <div class="mt-3 text-center text-sm text-neutral-600">{{ current.caption }}</div>

                            <!-- Lightbox modal -->
                            <div v-if="showLightbox" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                                <div class="max-w-5xl w-full">
                                    <div class="relative bg-white rounded-lg overflow-hidden">
                                        <button @click="closeLightbox" class="absolute right-2 top-2 z-10 rounded-full lightbox-btn p-2">✕</button>
                                        <picture>
                                            <source media="(max-width: 768px)" :srcset="currentMobile.src" />
                                            <img :src="current.src" sizes="(max-width: 768px) 100vw, 1200px" :alt="current.caption" class="w-full lightbox-img bg-black" />
                                        </picture>
                                        <div class="p-4 text-center text-sm text-neutral-700">{{ current.caption }}</div>

                                        <!-- Lightbox nav -->
                                        <button @click="prev" aria-label="Previous" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full lightbox-btn p-2">‹</button>
                                        <button @click="next" aria-label="Next" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full lightbox-btn p-2">›</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- Footer / small note -->
            <footer class="mt-8 text-center text-sm text-neutral-500">
                <p>
                    “Para sa mga na negosyanteng gustong maging mas maayos at organisado ang negosyo—nandito ang SimplePOS para gawing mas magaan ang araw-araw.”
                </p>
                <p class="mt-4">
                    Kung may tanong ka, tawag o text lang sa <b>0910-602-4370 / 0927-465-4155</b>, o i-message ako sa facebook. <a href="https://www.facebook.com/jayinclino" target="_blank" class="text-amber-600 hover:underline">Click here </a>
                </p>
            </footer>

        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 420ms cubic-bezier(.2,.8,.2,1), transform 420ms cubic-bezier(.2,.8,.2,1);
}
.fade-enter-from { opacity: 0; transform: translateY(8px) scale(.995); }
.fade-enter-to { opacity: 1; transform: translateY(0) scale(1); }
.fade-leave-from { opacity: 1; transform: translateY(0) scale(1); }
.fade-leave-to { opacity: 0; transform: translateY(-8px) scale(.995); }

/* Slide transitions with direction awareness */
.slide-left-enter-active, .slide-left-leave-active,
.slide-right-enter-active, .slide-right-leave-active {
    transition: transform 420ms cubic-bezier(.2,.8,.2,1), opacity 360ms ease;
}
.slide-left-enter-from { transform: translateX(-14px); opacity: 0; }
.slide-left-enter-to   { transform: translateX(0); opacity: 1; }
.slide-left-leave-from { transform: translateX(0); opacity: 1; }
.slide-left-leave-to   { transform: translateX(14px); opacity: 0; }

.slide-right-enter-from { transform: translateX(14px); opacity: 0; }
.slide-right-enter-to   { transform: translateX(0); opacity: 1; }
.slide-right-leave-from { transform: translateX(0); opacity: 1; }
.slide-right-leave-to   { transform: translateX(-14px); opacity: 0; }

/* Make lightbox image responsive and centered */
.lightbox-img { max-height: 80vh; width: 100%; object-fit: contain; }

/* Hide outline on buttons inside carousel but keep accessibility focus */
.carousel-thumb:focus { outline: 2px solid rgba(250,204,21,.6); outline-offset: 2px; }

/* Lightbox primary-styled controls */
.lightbox-btn {
    background: var(--primary);
    color: var(--primary-foreground);
    box-shadow: 0 6px 14px rgba(16,24,40,0.08);
}
.lightbox-btn:hover {
    filter: brightness(0.95);
}
.lightbox-btn:active {
    transform: translateY(1px);
}
</style>
