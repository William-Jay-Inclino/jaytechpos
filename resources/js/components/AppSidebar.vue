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
    LayoutGrid,
    Package,
    ShoppingCart,
    TrendingUp,
    UserCircle,
    Users,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

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
    {
        title: 'Meet the Developer',
        href: 'https://www.facebook.com/jewell.inclino/',
        icon: UserCircle,
    },
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
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
