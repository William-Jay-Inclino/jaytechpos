import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

import Toast, { type PluginOptions, POSITION } from 'vue-toastification';
import 'vue-toastification/dist/index.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // createApp({ render: () => h(App, props) })
        //     .use(plugin)
        //     .mount(el);
        const vueApp = createApp({ render: () => h(App, props) });
        vueApp.use(plugin);

        // Toast options (customize as needed)
        const options: PluginOptions = {
            position: POSITION.TOP_RIGHT,
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            showCloseButtonOnHover: false,
            hideProgressBar: false,
            closeButton: 'button',
            icon: true,
            rtl: false,
        };

        vueApp.use(Toast, options);

        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();


// Added: Service Worker registration + PWA install handling
declare global {
    interface Window {
        promptPWAInstall?: () => Promise<'accepted' | 'dismissed' | null>;
    }
}

interface BeforeInstallPromptEvent extends Event {
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed'; platform?: string }>;
}

let deferredPrompt: BeforeInstallPromptEvent | null = null;

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/service-worker.js')
            .then((reg) => {
                // eslint-disable-next-line no-console
                console.log('ServiceWorker registered:', reg.scope);
            })
            .catch((err) => {
                // eslint-disable-next-line no-console
                console.error('ServiceWorker registration failed:', err);
            });
    });
}

window.addEventListener('beforeinstallprompt', (e: Event) => {
    e.preventDefault();
    deferredPrompt = e as BeforeInstallPromptEvent;
    // notify the app UI to show an "Install" action (listen to 'pwa:beforeinstallprompt')
    window.dispatchEvent(new CustomEvent('pwa:beforeinstallprompt'));
});

window.promptPWAInstall = async (): Promise<'accepted' | 'dismissed' | null> => {
    if (!deferredPrompt) {
        return null;
    }
    try {
        await deferredPrompt.prompt();
        const choice = await deferredPrompt.userChoice;
        deferredPrompt = null;
        return choice.outcome as 'accepted' | 'dismissed';
    } catch {
        deferredPrompt = null;
        return null;
    }
};

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    window.dispatchEvent(new CustomEvent('pwa:installed'));
});