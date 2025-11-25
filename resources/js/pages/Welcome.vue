<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { CreditCard, Percent, Package, BarChart3 } from 'lucide-vue-next';
import { getBrowser, getDeviceType, getOS, getSessionId, isBot } from '@/utils/analytics';

// --- Video Demo Logic ---
const videoSrc = 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4';
const videoRef = ref<HTMLVideoElement | null>(null);
const isPlaying = ref(false);

function togglePlay() {
    if (!videoRef.value) return;
    if (videoRef.value.paused) {
        videoRef.value.play();
        isPlaying.value = true;
    } else {
        videoRef.value.pause();
        isPlaying.value = false;
    }
}

function onVideoEnded() {
    isPlaying.value = false;
}

// --- Analytics Logic ---


onMounted(() => {
    // Video autoplay
    if (videoRef.value) {
        videoRef.value.play().then(() => {
            isPlaying.value = !videoRef.value!.paused;
        }).catch(() => {});
    }

    // --- Analytics ---
    const ua = navigator.userAgent;
    const sessionId = getSessionId();

    const payload = {
        session_id: sessionId,
        user_agent: ua,
        referer: document.referrer || null,
        page_url: window.location.href,
        device_type: getDeviceType(ua),
        browser: getBrowser(ua),
        os: getOS(ua),
        is_bot: isBot(ua),
        is_unique: false,
        page_views: 1,
        visited_at: new Date().toISOString(),
    };

    window.localStorage.setItem('site_session_id', sessionId);

    axios.post('/analytics/site-visit', payload)

});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-white via-amber-50 to-amber-100 dark:from-[#070707] dark:via-[#0f0f0f] dark:to-[#0a0a0a]">
        <div class="mx-auto max-w-6xl px-6 py-10">
            <!-- Header / Nav -->
            <!-- Make header stack on small screens and keep nav right-aligned on larger screens -->
            <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <!-- Logo: using a public path so you can replace the file at public/images/jaytech-logo.png -->
                    <img
                        src="/logo.png"
                        alt="JayTech logo"
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover border-2 border-amber-600 bg-white/60 dark:bg-black/10"
z                    />

                    <div>
                        <h2 class="text-3xl font-semibold text-primary-gradient">JayTech</h2>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Simple store management app.</p>
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

            <!-- Full-bleed video section placed immediately after header -->
            <section class="mt-6">
                <div class="w-full">
                    <div class="relative w-full">
                        <div class="relative overflow-hidden bg-black">
                            <!-- <video
                                ref="videoRef"
                                :src="videoSrc"
                                class="w-full h-[85vh] sm:h-[80vh] lg:h-[90vh] object-cover"
                                poster="https://via.placeholder.com/1280x720.png?text=SimplePOS+Preview"
                                @ended="onVideoEnded"
                                @play="isPlaying = true"
                                @pause="isPlaying = false"
                                autoplay
                                muted
                                playsinline
                                controls
                            ></video> -->

                            <!-- Play overlay (hidden once playing) -->
                            <button
                                v-show="!isPlaying"
                                @click.stop="togglePlay"
                                class="absolute inset-0 m-auto w-20 h-20 rounded-full bg-amber-600/95 text-white flex items-center justify-center shadow-2xl hover:bg-amber-700 transition"
                                aria-label="Play demo video"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v18l15-9L5 3z" fill="currentColor" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Hero (centered, modern) -->
            <section class="mt-10 flex flex-col items-center text-center">
                <div class="w-full max-w-3xl">
                    <!-- Primary CTAs -->
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <Link
                            :href="register()"
                            class="inline-flex items-center gap-2 rounded-md bg-amber-600 px-5 py-3 text-white shadow-lg hover:bg-amber-700"
                        >
                            Get started — Create account
                        </Link>
                    </div>

                    <!-- Key selling points (icons + text) -->
                    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800 flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <CreditCard class="w-6 h-6 text-amber-600" />
                            </div>
                            <div class="text-left">
                                <h4 class="font-semibold">Sales & Payments</h4>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">Mabilis na checkout at malinaw na cash tracking para sa mga busy na counter.</p>
                            </div>
                        </div>

                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800 flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <Percent class="w-6 h-6 text-amber-600" />
                            </div>
                            <div class="text-left">
                                <h4 class="font-semibold">Utang & Interest</h4>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">Pahintulutan ang customers na bumili nang pa-utang. Automatic monthly interest calculation.</p>
                            </div>
                        </div>

                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800 flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <Package class="w-6 h-6 text-amber-600" />
                            </div>
                            <div class="text-left">
                                <h4 class="font-semibold">Products & Customers</h4>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">Madaling product management at customer profiles na may kasamang balance tracking.</p>
                            </div>
                        </div>

                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800 flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <BarChart3 class="w-6 h-6 text-amber-600" />
                            </div>
                            <div class="text-left">
                                <h4 class="font-semibold">Reports & Cash Flow</h4>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">Sales reports at cash flow monitoring para mas mapalago ang negosyo.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>


            <!-- Footer / small note -->
            <footer class="mt-8 text-center text-sm text-neutral-500">
                For inquiries, please call or text 0910-602-4370 / 0927-465-4155, or reach out via Facebook. 
                <a href="https://www.facebook.com/jayinclino" target="_blank" class="text-amber-600 hover:underline">Click here </a>    
            </footer>

        </div>
    </div>
</template>

<!-- Carousel styles removed -->
