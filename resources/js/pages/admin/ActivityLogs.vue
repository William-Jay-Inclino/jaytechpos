<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { Search } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

interface Activity {
    id: number
    description: string
    event: string
    subject_type: string
    subject_id: number
    causer: {
        id: number
        name: string
        email: string
    }
    ip_address: string
    user_agent: string
    device_info: string
    properties: any
    created_at: string
    created_at_diff: string
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
    activities: {
        data: Activity[]
        links: any[]
        meta: PaginationMeta
    }
    filters: {
        search?: string
        start_date?: string
        end_date?: string
    }
}

const props = defineProps<Props>()

// const breadcrumbs = [
//     { title: 'Activity Logs', href: '/admin/activity-logs' }
// ]

// Filter state
const searchQuery = ref(props.filters.search || '')
const startDate = ref(props.filters.start_date || '')
const endDate = ref(props.filters.end_date || '')

const applyFilters = () => {
    router.get('/admin/activity-logs', {
        search: searchQuery.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const setToday = () => {
    const today = new Date().toISOString().split('T')[0]
    startDate.value = today
    endDate.value = today
}

const clearFilters = () => {
    searchQuery.value = ''
    startDate.value = ''
    endDate.value = ''
    applyFilters()
}

// Try to trigger the native date picker on focus for browsers that support showPicker()
function openDatePicker(event: FocusEvent) {
    const target = event.target as HTMLInputElement
    try {
        if (target && typeof (target as any).showPicker === 'function') {
            ;(target as any).showPicker()
        }
    } catch (e) {
        // ignore — not all browsers support showPicker
    }
}
</script>

<template>
    <Head title="Activity Logs" />

    <AdminLayout>
        <div class="w-full px-3 py-4 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-7xl">
                <!-- Page Header -->
                <div class="mb-4 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Activity Logs</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Monitor user activities and system events.</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-4">
                    <div class="space-y-4">
                        <!-- Search -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="flex-1 relative min-w-0">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search by user or description..."
                                    class="pl-9 w-full"
                                    @keydown.enter="applyFilters"
                                />
                            </div>
                        </div>

                        <!-- Date Filter -->
                        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                            <Button 
                                variant="outline" 
                                size="sm"
                                @click="setToday"
                                class="w-full sm:w-auto"
                            >
                                Today
                            </Button>
                            <div class="flex flex-col sm:flex-row gap-4 flex-1 min-w-0">
                                <div class="flex-1 min-w-0">
                                    <Input
                                        id="start_date"
                                        v-model="startDate"
                                        type="date"
                                        placeholder="Start date"
                                        class="w-full"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <Input
                                        id="end_date"
                                        v-model="endDate"
                                        type="date"
                                        placeholder="End date"
                                        class="w-full"
                                    />
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                <Button 
                                    @click="applyFilters"
                                    class="w-full sm:w-auto"
                                >
                                    Apply
                                </Button>
                                <Button 
                                    variant="outline" 
                                    @click="clearFilters"
                                    v-if="searchQuery || startDate || endDate"
                                    class="w-full sm:w-auto"
                                >
                                    Clear
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Logs Table -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <!-- Empty State - No Logs -->
                    <div v-if="!activities.data.length" class="px-4 py-16 text-center sm:px-6 sm:py-20">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <span class="text-3xl">📋</span>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                            No activity logs found
                        </h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                            Try adjusting your search terms or date filters.
                        </p>
                    </div>

                    <!-- Table -->
                    <div v-else>
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                    <TableHead class="text-left text-sm font-medium text-gray-900 dark:text-white">User</TableHead>
                                    <TableHead class="text-left text-sm font-medium text-gray-900 dark:text-white">Description</TableHead>
                                    <TableHead class="text-left text-sm font-medium text-gray-900 dark:text-white">IP Address</TableHead>
                                    <TableHead class="text-left text-sm font-medium text-gray-900 dark:text-white">Device</TableHead>
                                    <TableHead class="text-right text-sm font-medium text-gray-900 dark:text-white">Time</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="activity in activities.data" :key="activity.id" class="border-b border-gray-200 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                                    <TableCell>
                                        <!-- <div> -->
                                            <div class="font-medium text-gray-900 dark:text-white">{{ activity.causer.name }}</div>
                                            <!-- <div class="text-xs text-gray-500 dark:text-gray-400">{{ activity.causer.email }}</div> -->
                                        <!-- </div> -->
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm text-gray-900 dark:text-white">{{ activity.description || 'No description' }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ activity.ip_address }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm text-gray-900 dark:text-white" :title="activity.user_agent">{{ activity.device_info }}</div>
                                    </TableCell>
                                    <TableCell class="text-right text-sm text-gray-500 dark:text-gray-400">
                                        <span :title="activity.created_at">{{ activity.created_at_diff }}</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Pagination -->
                        <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-gray-700" v-if="activities.data.length">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Showing {{ activities.meta.from }} to {{ activities.meta.to }} of {{ activities.meta.total }} results
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="activities.meta.current_page === 1"
                                    @click="router.visit(`/admin/activity-logs?page=${activities.meta.current_page - 1}`)"
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="activities.meta.current_page === activities.meta.last_page"
                                    @click="router.visit(`/admin/activity-logs?page=${activities.meta.current_page + 1}`)"
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>