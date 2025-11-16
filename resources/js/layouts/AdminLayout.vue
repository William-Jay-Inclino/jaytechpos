<script setup lang="ts">
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
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
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'
import { ChevronDown, User, LogOut, CheckCircle, XCircle, Moon, Sun } from 'lucide-vue-next'
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
    { name: 'Dashboard', href: '/admin' },
    { name: 'Users', href: '/admin/users' },
    { name: 'Analytics', href: '/admin/analytics' },
    { name: 'Activity Logs', href: '/admin/activity-logs' },
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
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Admin Panel</h1>
                    
                    <div class="flex items-center space-x-6">
                        <!-- Navigation -->
                        <nav class="flex space-x-6">
                            <Button 
                                v-for="item in navigation" 
                                :key="item.name"
                                variant="ghost"
                                size="sm"
                                as-child
                            >
                                <a
                                    :href="item.href"
                                    :class="[
                                        $page.url.startsWith(item.href)
                                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
                                            : 'text-gray-600 dark:text-gray-300'
                                    ]"
                                >
                                    {{ item.name }}
                                </a>
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
                                <Button variant="outline" size="sm" class="flex items-center gap-2">
                                    <Avatar class="h-6 w-6">
                                        <AvatarFallback class="text-xs">
                                            {{ user?.name?.charAt(0).toUpperCase() }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span>{{ user?.name }}</span>
                                    <ChevronDown class="h-4 w-4" />
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
            <!-- Flash Messages -->
            <Alert v-if="flash.success" class="mb-6 border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/50">
                <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-400" />
                <AlertDescription class="text-green-800 dark:text-green-200">
                    {{ flash.success }}
                </AlertDescription>
            </Alert>

            <Alert v-if="flash.error" variant="destructive" class="mb-6">
                <XCircle class="h-4 w-4" />
                <AlertDescription>
                    {{ flash.error }}
                </AlertDescription>
            </Alert>

            <!-- Breadcrumbs -->
            <Breadcrumb v-if="breadcrumbs.length > 0" class="mb-6">
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/admin">Admin</BreadcrumbLink>
                    </BreadcrumbItem>
                    <template v-for="(breadcrumb, index) in breadcrumbs" :key="breadcrumb.title">
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbLink 
                                v-if="breadcrumb.href && index < breadcrumbs.length - 1"
                                :href="breadcrumb.href"
                            >
                                {{ breadcrumb.title }}
                            </BreadcrumbLink>
                            <BreadcrumbPage v-else>
                                {{ breadcrumb.title }}
                            </BreadcrumbPage>
                        </BreadcrumbItem>
                    </template>
                </BreadcrumbList>
            </Breadcrumb>

            <!-- Page Content -->
            <slot />
        </main>
    </div>
</template>