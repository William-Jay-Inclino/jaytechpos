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

const breadcrumbs = [
    { title: 'Activity Logs', href: '/admin/activity-logs' }
]

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
</script>

<template>
    <Head title="Activity Logs" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Activity Logs</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Monitor user activities and system events</p>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filters</CardTitle>
                    <CardDescription>Search and filter activity logs</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <!-- Search -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="flex-1 relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search by user or description..."
                                    class="pl-9"
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
                                class="w-fit"
                            >
                                Today
                            </Button>
                            <div class="flex flex-col sm:flex-row gap-4 flex-1">
                                <div class="flex-1">
                                    <Input
                                        v-model="startDate"
                                        type="date"
                                        placeholder="Start date"
                                    />
                                </div>
                                <div class="flex-1">
                                    <Input
                                        v-model="endDate"
                                        type="date"
                                        placeholder="End date"
                                    />
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Button 
                                    @click="applyFilters"
                                >
                                    Apply
                                </Button>
                                <Button 
                                    variant="outline" 
                                    @click="clearFilters"
                                    v-if="searchQuery || startDate || endDate"
                                >
                                    Clear
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Activity Logs Table -->
            <Card>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>User</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>IP Address</TableHead>
                            <TableHead>Device</TableHead>
                            <TableHead class="text-right">Time</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="!activities.data.length">
                            <TableCell colspan="5" class="text-center py-8 text-gray-500">
                                No activity logs found
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="activity in activities.data" :key="activity.id">
                            <TableCell>
                                <div>
                                    <div class="font-medium">{{ activity.causer.name }}</div>
                                    <div class="text-xs text-gray-500">{{ activity.causer.email }}</div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm">{{ activity.description || 'No description' }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm text-gray-600">{{ activity.ip_address }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm" :title="activity.user_agent">{{ activity.device_info }}</div>
                            </TableCell>
                            <TableCell class="text-right text-sm text-gray-500">
                                <span :title="activity.created_at">{{ activity.created_at_diff }}</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div class="flex items-center justify-between px-6 py-4 border-t" v-if="activities.data.length">
                    <div class="text-sm text-gray-600">
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
            </Card>
        </div>
    </AdminLayout>
</template>