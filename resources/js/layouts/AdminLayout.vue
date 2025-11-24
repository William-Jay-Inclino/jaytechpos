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
import { ChevronDown, User, LogOut, Moon, Sun, Users as UsersIcon, List as ListIcon } from 'lucide-vue-next'
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
    { name: 'Users', href: '/admin/users', icon: UsersIcon },
    { name: 'Activity Logs', href: '/admin/activity-logs', icon: ListIcon },
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
                <div class="flex justify-between items-center py-4">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">JayTech</h1>
                    
                    <div class="flex items-center space-x-6">
                        <!-- Navigation -->
                        <nav class="flex space-x-2 items-center">
                            <Button 
                                v-for="item in navigation" 
                                :key="item.name"
                                variant="ghost"
                                size="sm"
                                as-child
                            >
                                <Link
                                    :href="item.href"
                                    :class="[
                                        $page.url.startsWith(item.href)
                                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
                                            : 'text-gray-600 dark:text-gray-300'
                                    ]"
                                    :aria-label="item.name"
                                >
                                    <!-- Icon on mobile, text on larger screens -->
                                    <component :is="item.icon" class="h-4 w-4 sm:hidden" />
                                    <span class="hidden sm:inline">{{ item.name }}</span>
                                </Link>
                            </Button>
                        </nav>

                        <!-- Theme Toggle -->
                        <Button
                            @click="toggleTheme"
                            variant="outline"
                            size="sm"
                            class="flex items-center gap-2"
                        >
                            <Sun v-if="isDark" class="h-4 w-4" />
                            <Moon v-else class="h-4 w-4" />
                        </Button>

                        <!-- User Dropdown -->
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
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Page Content -->
            <slot />
        </main>
    </div>
</template>