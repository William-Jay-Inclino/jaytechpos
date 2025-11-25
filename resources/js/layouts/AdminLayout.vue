<script setup lang="ts">
import { computed } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ChevronDown, User, LogOut, Moon, Sun, Users, List, BarChart2 } from 'lucide-vue-next'
import type { BreadcrumbItemType } from '@/types'
import { useTheme } from '@/composables/useTheme'

interface Props {
    breadcrumbs?: BreadcrumbItemType[]
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})

const page = usePage()
const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash as any)
const { isDark, toggleTheme } = useTheme()

const navigation = [
    { name: 'Analytics', href: '/admin/analytics', icon: BarChart2 },
    { name: 'Users', href: '/admin/users', icon: Users },
    { name: 'Activity Logs', href: '/admin/activity-logs', icon: List },
]

const logout = () => {
    router.post('/logout')
}

const goToProfile = () => {
    router.get('/admin/profile')
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Admin Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="py-4">
                    <div
                        class="flex flex-row w-full items-center gap-2 sm:gap-0 sm:flex-row sm:justify-between sm:items-center"
                    >
                        <div class="flex flex-row w-full items-center gap-2 sm:gap-0 flex-nowrap">
                            <!-- Navigation -->
                            <nav class="flex flex-row flex-1 items-center gap-2 flex-nowrap">
                                <Button
                                    v-for="item in navigation"
                                    :key="item.name"
                                    variant="ghost"
                                    size="sm"
                                    as-child
                                    class="flex-1 min-w-0"
                                >
                                    <Link
                                        :href="item.href"
                                        :class="[
                                            $page.url.startsWith(item.href)
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
                                                : 'text-gray-600 dark:text-gray-300'
                                        ]"
                                        :aria-label="item.name"
                                        class="flex flex-col sm:flex-row items-center justify-center px-2 py-2 rounded w-full min-w-0"
                                    >
                                        <component :is="item.icon" class="h-6 w-6 sm:h-4 sm:w-4" />
                                        <span class="block text-[10px] mt-1 sm:hidden truncate w-full text-center">{{ item.name }}</span>
                                        <span class="hidden sm:inline text-base ml-2">{{ item.name }}</span>
                                    </Link>
                                </Button>
                            </nav>

                            <!-- Theme Toggle -->
                            <div class="flex-1 min-w-0 flex justify-center flex-nowrap">
                                <Button
                                    @click="toggleTheme"
                                    variant="outline"
                                    size="sm"
                                    class="flex items-center gap-2"
                                >
                                    <Sun v-if="isDark" class="h-4 w-4" />
                                    <Moon v-else class="h-4 w-4" />
                                </Button>
                            </div>

                            <!-- User Dropdown -->
                            <div class="flex-1 min-w-0 flex justify-center flex-nowrap">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" size="sm" class="flex items-center gap-2" aria-label="User menu">
                                            <Avatar class="h-6 w-6">
                                                <AvatarFallback class="text-xs">
                                                    {{ user?.name?.charAt(0).toUpperCase() }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <!-- only show username and chevron on larger screens -->
                                            <span class="hidden sm:inline">{{ user?.name }}</span>
                                            <ChevronDown class="hidden sm:inline h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-56">
                                        <DropdownMenuLabel>
                                            <div class="space-y-1">
                                                <p class="text-sm font-medium">{{ user?.name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ user?.email }}</p>
                                                <Badge variant="secondary" class="text-xs">Administrator</Badge>
                                            </div>
                                        </DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem @click="goToProfile" class="cursor-pointer">
                                            <User class="mr-2 h-4 w-4" />
                                            Profile Settings
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="logout" class="cursor-pointer text-red-600">
                                            <LogOut class="mr-2 h-4 w-4" />
                                            Logout
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Page Content -->
            <slot />
        </main>
    </div>
</template>