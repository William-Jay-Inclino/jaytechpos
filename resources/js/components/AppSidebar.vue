<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, salesReport } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    Activity,
    Banknote,
    BookOpen,
    CircleDollarSign,
    Home,
    LayoutGrid,
    Package,
    ShoppingCart,
    TrendingUp,
    UserCircle,
    Users,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { getDeviceType } from '@/utils/analytics';

// PWA install button state (mobile only)
const showInstall = ref(false);
const isInstalled = ref(false);

function checkInstalled(): void {
    try {
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
            isInstalled.value = true;
            return;
        }
    } catch {}

    try {
        if ((navigator as any).standalone === true) {
            isInstalled.value = true;
            return;
        }
    } catch {}

    try {
        if (document.referrer && document.referrer.startsWith('android-app://')) {
            isInstalled.value = true;
            return;
        }
    } catch {}
}

function onBeforeInstallPrompt() {
    const ua = navigator.userAgent;
    const deviceType = getDeviceType(ua);
    if (deviceType === 'mobile' && !isInstalled.value) {
        showInstall.value = true;
    }
}

function onInstalled() {
    showInstall.value = false;
    isInstalled.value = true;
}

async function handleInstallClick() {
    try {
        await window.promptPWAInstall?.();
    } finally {
        showInstall.value = false;
    }
}

onMounted(() => {
    checkInstalled();
    window.addEventListener('pwa:beforeinstallprompt', onBeforeInstallPrompt as EventListener);
    window.addEventListener('appinstalled', onInstalled as EventListener);
});

onBeforeUnmount(() => {
    window.removeEventListener('pwa:beforeinstallprompt', onBeforeInstallPrompt as EventListener);
    window.removeEventListener('appinstalled', onInstalled as EventListener);
});

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'New Sale',
        href: '/sales',
        icon: ShoppingCart,
    },
    {
        title: 'Utang Payment',
        href: '/utang-payments',
        icon: Banknote,
    },
    {
        title: 'Expenses',
        href: '/expenses',
        icon: CircleDollarSign,
    },
    {
        title: 'Products',
        href: '/products',
        icon: Package,
    },
    {
        title: 'Customers',
        href: '/customers',
        icon: Users,
    },
    {
        title: 'Sales Report',
        href: salesReport(),
        icon: TrendingUp,
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Meet the Developer',
    //     href: 'https://www.facebook.com/jewell.inclino/',
    //     icon: UserCircle,
    // },
    // {
    //     title: 'Read the Documentation',
    //     href: '',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
            <!-- Mobile-only Install CTA: shown when install prompt available and app not installed -->
            <div class="w-full px-4 py-3 sm:hidden">
                <button
                    v-if="showInstall && !isInstalled"
                    @click.prevent="handleInstallClick"
                    class="pwa-install-btn w-full justify-center bg-white px-3 py-2 rounded-md shadow flex items-center gap-2 hover:shadow-md focus:outline-none focus:ring-2"
                    title="Install JayTech for quick access"
                    aria-label="Install JayTech app"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 11l4 4 4-4" />
                        <rect x="3" y="18" width="18" height="2" rx="1" fill="currentColor" />
                    </svg>
                    <div class="text-left">
                        <div class="text-sm font-medium">Install App</div>
                        <div class="text-xs text-neutral-500">Save JayTech to your home screen</div>
                    </div>
                </button>
            </div>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
