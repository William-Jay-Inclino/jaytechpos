<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { computed, ref } from 'vue'
import { Search, Edit, Trash2, UserPlus, ChevronLeft, ChevronRight } from 'lucide-vue-next'

// UI Components
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { showConfirmDelete } from '@/lib/swal'
import { showErrorToast, showSuccessToast } from '@/lib/toast'

interface User {
    id: number
    name: string
    email: string
    role: string
    status: string
    created_at: string
}

interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
}

interface Props {
    users: {
        data: User[]
        links: any[]
        meta: PaginationMeta
    }
}

const props = defineProps<Props>()


// const breadcrumbs = [
//     { title: 'Users', href: '/admin/users' }
// ]

// Search state
const searchQuery = ref('')

// Filtered users based on search
const filteredUsers = computed(() => {
    if (!Array.isArray(props.users.data)) return []
    
    if (!searchQuery.value.trim()) {
        return props.users.data
    }
    
    const query = searchQuery.value.toLowerCase().trim()
    return props.users.data.filter(user => 
        user.name.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query) ||
        user.role.toLowerCase().includes(query)
    )
})

// Pagination helpers
const currentPage = computed(() => props.users?.meta?.current_page || 1)
const lastPage = computed(() => props.users?.meta?.last_page || 1)

const goToPage = (page: number) => {
    if (page >= 1 && page <= lastPage.value) {
        router.visit(`/admin/users?page=${page}`, {
            preserveState: true,
            preserveScroll: true,
        })
    }
}

const goToPreviousPage = () => {
    if (currentPage.value > 1) {
        goToPage(currentPage.value - 1)
    }
}

const goToNextPage = () => {
    if (currentPage.value < lastPage.value) {
        goToPage(currentPage.value + 1)
    }
}

const getVisiblePages = (): (number | string)[] => {
    const totalPages = lastPage.value
    const current = currentPage.value
    const pages: (number | string)[] = []
    
    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) {
            pages.push(i)
        }
    } else {
        pages.push(1)
        
        if (current > 3) {
            pages.push('...')
        }
        
        const start = Math.max(2, current - 1)
        const end = Math.min(totalPages - 1, current + 1)
        
        for (let i = start; i <= end; i++) {
            pages.push(i)
        }
        
        if (current < totalPages - 2) {
            pages.push('...')
        }
        
        pages.push(totalPages)
    }
    
    return pages
}

const deleteUser = async (userId: number) => {
    const result = await showConfirmDelete({
        title: 'Are you sure?',
        text: 'This action cannot be undone. The user will be permanently deleted.',
        confirmButtonText: 'Yes, delete user!',
    })

    if (result.isConfirmed) {
        try {
            const res = await axios.delete(`/admin/users/${userId}`)

            const data = res?.data || {}

            if (data.success) {
                showSuccessToast(data.msg || 'User deleted successfully!')
                // reload the current page so the users list updates
                router.reload()
            } else {
                // backend responded but indicated failure
                showErrorToast(data.msg || 'Failed to delete user')
            }
        } catch (error: any) {
            // try to show a useful message from the response when available
            const message = error?.response?.data?.msg || error?.response?.data?.message || 'Failed to delete user'
            showErrorToast(message)
        }
    }
}
</script>

<template>
    <Head title="User Management" />

    <AdminLayout>
        <div class="w-full px-3 py-4 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-7xl">
                <!-- Page Header -->
                <div class="mb-4 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">User Management</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage system users and their roles.</p>
                    </div>
                    
                    <Button as-child class="w-full sm:w-auto">
                        <Link href="/admin/users/create" class="flex items-center justify-center gap-2">
                            <UserPlus class="h-4 w-4" />
                            <span>Add User</span>
                        </Link>
                    </Button>
                </div>

                <!-- Search -->
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-4">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by name or email..."
                            class="pl-10"
                        />
                    </div>
                </div>

                <!-- Users Display -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <!-- Empty State - No Users -->
                    <div v-if="!Array.isArray(users.data) || users.data.length === 0" class="px-4 py-16 text-center sm:px-6 sm:py-20">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-3xl">👥</span>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                            No users yet
                        </h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                            Get started by adding your first user to the system.
                        </p>
                        <div class="mt-8">
                            <Button as-child size="lg">
                                <Link href="/admin/users/create" class="flex items-center gap-2">
                                    <UserPlus class="h-5 w-5" />
                                    Add Your First User
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Empty State - No Results -->
                    <div v-else-if="filteredUsers.length === 0 && searchQuery.trim()" class="px-4 py-16 text-center sm:px-6 sm:py-20">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <Search class="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                            No users found
                        </h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                            Try adjusting your search terms to find what you're looking for.
                        </p>
                        <div class="mt-8">
                            <Button variant="outline" @click="searchQuery = ''" size="lg">
                                Clear Search
                            </Button>
                        </div>
                    </div>

                    <!-- Users List -->
                    <div v-else>

                            <div class="overflow-x-auto">
                                <table class="min-w-[900px] w-full" aria-label="Users table">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="w-96 py-3 pl-6 pr-4 text-left text-sm font-medium text-gray-900 dark:text-white">
                                        User
                                    </th>
                                    <th class="w-32 px-4 py-3 text-left text-sm font-medium text-gray-900 dark:text-white">
                                        Status
                                    </th>
                                    <th class="w-40 px-4 py-3 text-left text-sm font-medium text-gray-900 dark:text-white">
                                        Created
                                    </th>
                                    <th class="w-48 py-3 pl-4 pr-6 text-right text-sm font-medium text-gray-900 dark:text-white">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="user in filteredUsers" 
                                    :key="user.id"
                                    class="border-b border-gray-200 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50"
                                >
                                    <td class="w-96 py-4 pl-6 pr-4">
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                                {{ user.name }}
                                            </h3>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {{ user.email }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="w-32 px-4 py-4">
                                        <Badge 
                                            :variant="user.status === 'active' ? 'default' : 'secondary'"
                                        >
                                            {{ user.status === 'active' ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </td>
                                    <td class="w-40 px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ user.created_at }}
                                    </td>
                                    <td class="w-48 py-4 pl-4 pr-6">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button size="sm" variant="outline" as-child>
                                                <Link :href="`/admin/users/${user.id}/edit`">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            
                                            <Button 
                                                size="sm" 
                                                variant="destructive"
                                                @click="deleteUser(user.id)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                            </div>

                        <!-- Pagination -->
                        <div v-if="lastPage > 1" class="flex items-center justify-between border-t border-gray-200 px-4 py-4 dark:border-gray-700 sm:px-6">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Showing {{ users.meta?.from || 0 }} to {{ users.meta?.to || 0 }} of {{ users.meta?.total || 0 }} results
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <!-- Previous Button -->
                                <Button 
                                    @click="goToPreviousPage"
                                    :disabled="currentPage <= 1"
                                    variant="outline" 
                                    size="sm"
                                    class="flex items-center gap-1"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                    Previous
                                </Button>

                                <!-- Page Numbers -->
                                <div class="hidden items-center gap-1 sm:flex">
                                    <template v-for="page in getVisiblePages()" :key="page">
                                        <Button
                                            v-if="page === '...'"
                                            variant="ghost"
                                            size="sm"
                                            disabled
                                            class="h-8 w-10 p-0"
                                        >
                                            ...
                                        </Button>
                                        <Button
                                            v-else
                                            @click="goToPage(page as number)"
                                            :variant="currentPage === page ? 'default' : 'outline'"
                                            size="sm"
                                            class="h-8 w-10 p-0"
                                        >
                                            {{ page }}
                                        </Button>
                                    </template>
                                </div>

                                <!-- Next Button -->
                                <Button 
                                    @click="goToNextPage"
                                    :disabled="currentPage >= lastPage"
                                    variant="outline" 
                                    size="sm"
                                    class="flex items-center gap-1"
                                >
                                    Next
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>