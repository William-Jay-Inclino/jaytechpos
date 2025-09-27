import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

import Toast, { type PluginOptions, POSITION } from "vue-toastification"
import "vue-toastification/dist/index.css"

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
        const vueApp = createApp({ render: () => h(App, props) })
        vueApp.use(plugin)

        // Toast options (customize as needed)
        const options: PluginOptions = {
            position: POSITION.TOP_RIGHT,
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            showCloseButtonOnHover: false,
            hideProgressBar: false,
            closeButton: "button",
            icon: true,
            rtl: false,
        }

        vueApp.use(Toast, options)

        vueApp.mount(el)

    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
