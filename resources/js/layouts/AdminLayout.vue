<script setup lang="ts">
import { computed } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu'
import { Sun, Moon, ChevronDown, User, LogOut, BarChart2, Users, List } from 'lucide-vue-next'
// import { useTheme } from '@/composables/useTheme'
import { useAppearance } from '@/composables/useAppearance';

type BreadcrumbItemType = { title: string; href?: string }

withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItemType[] }>(), { breadcrumbs: () => [] })

const page = usePage()
const user = computed(() => page.props.auth?.user)
// const { isDark, toggleTheme } = useTheme()
const { appearance, updateAppearance } = useAppearance();


const navigation = [
    { name: 'Analytics', href: '/admin/analytics', icon: BarChart2 },
    { name: 'Users', href: '/admin/users', icon: Users },
    { name: 'Activity Logs', href: '/admin/activity-logs', icon: List },
]

function logout(): void {
    router.post('/logout')
}

function goToProfile(): void {
    router.get('/admin/profile')
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
        <header class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-4">

                        <nav class="flex items-center gap-2">
                            <Link
                                v-for="item in navigation"
                                :key="item.name"
                                :href="item.href"
                                class="flex items-center gap-2 px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                                :class="$page.url.startsWith(item.href)
                                    ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
                                    : 'text-gray-600 dark:text-gray-300'"
                            >
                                <component :is="item.icon" class="h-4 w-4" />
                                <span class="hidden sm:inline text-sm">{{ item.name }}</span>
                            </Link>
                        </nav>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            @click="updateAppearance(appearance === 'dark' ? 'light' : 'dark')"
                            type="button"
                            class="p-1 rounded border border-transparent hover:bg-gray-100 dark:hover:bg-gray-700"
                            aria-label="Toggle theme"
                        >
                            <Sun v-if="appearance === 'dark'" class="h-4 w-4" />
                            <Moon v-else class="h-4 w-4" />
                        </button>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="flex items-center gap-2 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <Avatar class="h-8 w-8"><AvatarFallback class="text-xs">{{ user?.name?.charAt(0)?.toUpperCase() }}</AvatarFallback></Avatar>
                                    <span class="hidden sm:inline text-sm">{{ user?.name }}</span>
                                    <ChevronDown class="h-4 w-4 hidden sm:inline" />
                                </button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent align="end" class="w-56">
                                <div class="p-3">
                                    <p class="text-sm font-medium">{{ user?.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ user?.email }}</p>
                                </div>

                                <div class="border-t dark:border-gray-700"></div>

                                <DropdownMenuItem @click="goToProfile" class="cursor-pointer flex items-center gap-2">
                                    <User class="h-4 w-4" />
                                    Profile
                                </DropdownMenuItem>

                                <DropdownMenuItem @click="logout" class="cursor-pointer text-red-600 flex items-center gap-2">
                                    <LogOut class="h-4 w-4" />
                                    Logout
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>