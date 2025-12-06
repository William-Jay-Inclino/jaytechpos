<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { CreditCard, Percent, Package, BarChart3 } from 'lucide-vue-next';
import { getDeviceType, sendAnalytics } from '@/utils/analytics';



// --- Analytics Logic ---


onMounted( async() => {

    await sendAnalytics({ 
        referer: document.referrer || null, 
        page_url: window.location.href, 
        ua: navigator.userAgent 
    })

});

// PWA install button (mobile only)
const showInstall = ref(false);

function onBeforeInstallPrompt() {
    const ua = navigator.userAgent;
    const deviceType = getDeviceType(ua);
    // show only for mobile devices
    if (deviceType === 'mobile' || deviceType === 'tablet') {
        showInstall.value = true;
    }
}

function onInstalled() {
    showInstall.value = false;
}

async function handleInstallClick() {
    try {
        const result = await window.promptPWAInstall?.();
        // Optionally track the result
        // console.log('PWA install result', result);
    } finally {
        showInstall.value = false;
    }
}

onMounted(() => {
    window.addEventListener('pwa:beforeinstallprompt', onBeforeInstallPrompt as EventListener);
    window.addEventListener('pwa:installed', onInstalled as EventListener);
});

onBeforeUnmount(() => {
    window.removeEventListener('pwa:beforeinstallprompt', onBeforeInstallPrompt as EventListener);
    window.removeEventListener('pwa:installed', onInstalled as EventListener);
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
                        class="rounded-md bg-amber-600 px-4 py-2 text-white shadow hover:bg-amber-700 dark:shadow-lg"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md bg-white px-4 py-2 text-primary shadow hover:bg-gray-100 dark:shadow-lg"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-md bg-amber-600 px-4 py-2 text-white shadow hover:bg-amber-700 dark:shadow-lg"
                        >
                            Sign up
                        </Link>
                    </template>
                    <button
                        v-if="showInstall"
                        @click.prevent="handleInstallClick"
                        class="rounded-md bg-amber-500 px-3 py-2 text-white shadow hover:bg-amber-600"
                    >
                        Install
                    </button>
                </nav>
            </header>

            <!-- Hero (centered, modern) -->
            <section class="mt-10 flex flex-col items-center text-center">
                <div class="w-full max-w-3xl">

                    <!-- Key selling points (icons + text) -->
                    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800 flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <CreditCard class="w-6 h-6 text-amber-600" />
                            </div>
                            <div class="text-left">
                                <h4 class="font-semibold">Sales & Payments</h4>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">Faster checkout & accurate cash tracking para sa mga busy na counter.</p>
                            </div>
                        </div>

                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800 flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <Percent class="w-6 h-6 text-amber-600" />
                            </div>
                            <div class="text-left">
                                <h4 class="font-semibold">Utang & Interest</h4>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">Allow customers na bumili nang pa-utang. Automatic monthly interest calculation.</p>
                            </div>
                        </div>

                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f] dark:border-neutral-800 flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <Package class="w-6 h-6 text-amber-600" />
                            </div>
                            <div class="text-left">
                                <h4 class="font-semibold">Products & Customers</h4>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">Simple product management & customer profiles na may kasamang balance tracking.</p>
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
            <footer class="mt-8 text-center text-xs text-neutral-500 space-y-1">
                <p>
                    For inquiries, please call or text 
                    <span class="font-medium">0910-602-4370</span>,  
                    or reach out via Messenger:
                    <span 
                        class="text-amber-600 hover:underline"
                    >
                        Jay Inclino
                    </span>
                </p>

                <p class="pt-2 text-neutral-600 text-[10px]">
                    Developed by <span class="font-semibold">William Jay Inclino</span> © {{ new Date().getFullYear() }}
                </p>
            </footer>


        </div>
    </div>
</template>

<!-- Carousel styles removed -->
