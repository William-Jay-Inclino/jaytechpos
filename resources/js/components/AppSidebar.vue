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
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { 
    LayoutGrid, 
    ShoppingCart, 
    Package, 
    Users, 
    Activity,
    Banknote,
    CircleDollarSign,
    BookOpen,
    UserCircle,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Sales',
        icon: ShoppingCart,
        items: [
            {
                title: 'View Sales',
                href: '/sales',
            },
            {
                title: 'New Sale',
                href: '/sales/create',
            },
        ],
    },
    {
        title: 'Utang Tracker',
        icon: Banknote,
        items: [
            {
                title: 'Utang List',
                href: '/utang',
            },
            {
                title: 'Payment',
                href: '/utang/payment',
            },
            {
                title: 'Interest Rate',
                href: '/utang/interest-rate',
            },
        ],
    },
    {
        title: 'Products',
        icon: Package,
        items: [
            {
                title: 'Product List',
                href: '/products',
            },
            {
                title: 'Categories',
                href: '/product-categories',
            },
            {
                title: 'New Product',
                href: '/products/create',
            },
        ],
    },
    {
        title: 'Expenses',
        icon: CircleDollarSign,
        items: [
            {
                title: 'Expense List',
                href: '/expenses',
            },
            {
                title: 'Categories',
                href: '/expense-categories',
            },
            {
                title: 'New Expense',
                href: '/expenses/create',
            },
        ],
    },
    {
        title: 'Customers',
        href: '',
        icon: Users,
    },
    {
        title: 'Activity Logs',
        href: '',
        icon: Activity,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Developer',
        href: 'https://www.facebook.com/jewell.inclino/',
        icon: UserCircle,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
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
