<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Button } from '@/components/ui/button'
import { Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import DeleteAnalyticsModal from '@/components/modals/DeleteAnalyticsModal.vue'
import { showConfirmDelete, showSuccessAlert } from '@/lib/swal'
import { showErrorToast } from '@/lib/toast'
import axios from 'axios'

interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
}

interface SiteVisit {
    id: number
    session_id?: string
    ip_address: string
    user_agent?: string | null
    referer: string
    page_url?: string | null
    page_display: string
    country?: string | null
    region?: string | null
    city?: string | null
    location_formatted: string
    device_type?: string | null
    browser?: string | null
    os?: string | null
    device_formatted: string
    is_bot?: boolean
    is_unique?: boolean
    flags_formatted: string
    page_views: number
    visited_at: string
    visited_at_formatted: string
}

interface DailyVisitStat {
    id: number
    date: string
    date_formatted: string
    total_visits: number
    unique_visits: number
    page_views: number
    top_page: string
    top_referer: string
    mobile_visits: number
    desktop_visits: number
    tablet_visits: number
}

interface Props {
    daily_visit_stats: {
        data: DailyVisitStat[]
        links: any[]
        meta: PaginationMeta
    },
    site_visits: {
        data: SiteVisit[]
        links: any[]
        meta: PaginationMeta
    },
}

const props = defineProps<Props>()

const showDailyStatsBulkDeleteModal = ref(false)
const showSiteVisitsBulkDeleteModal = ref(false)

const handleDelete = async ({
    id,
    url,
    title,
    reloadKey,
    successMessage,
    errorMessage
}: {
    id: number
    url: string
    title: string
    reloadKey: string
    successMessage: string
    errorMessage: string
}) => {
    const result = await showConfirmDelete({
        title,
        text: 'This action cannot be undone.',
    })

    if (result.isConfirmed) {
        try {
            const res = await axios.delete(url)
            const data = res?.data || {}

            if (data.success) {
                showSuccessAlert({ text: data.msg || successMessage })
                router.reload({ only: [reloadKey] })
            } else {
                showErrorToast(data.msg || errorMessage)
            }
        } catch (error: any) {
            const message = error?.response?.data?.msg || error?.response?.data?.message || errorMessage
            showErrorToast(message)
        }
    }
}

const handleDeleteDailyStat = (id: number) => handleDelete({
    id,
    url: `/admin/analytics/daily-stats/${id}`,
    title: 'Delete Daily Visit Stat?',
    reloadKey: 'daily_visit_stats',
    successMessage: 'Daily visit stat deleted successfully.',
    errorMessage: 'Failed to delete daily visit stat'
})

const handleDeleteSiteVisit = (id: number) => handleDelete({
    id,
    url: `/admin/analytics/site-visits/${id}`,
    title: 'Delete Site Visit?',
    reloadKey: 'site_visits',
    successMessage: 'Site visit deleted successfully.',
    errorMessage: 'Failed to delete site visit'
})

const formatDate = (dateStr: string, includeTime = false): string => {
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return dateStr
    
    const options: Intl.DateTimeFormatOptions = includeTime
        ? { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }
        : { month: 'short', day: 'numeric', year: 'numeric' }
    
    const formatted = date.toLocaleString('en-US', options)
    return includeTime ? formatted.replace(/,\s(\d{1,2}:\d{2}\s[AP]M)/, ', $1') : formatted
}

const formatVisitDate = (dateStr: string) => formatDate(dateStr, true)
const formatStatsDate = (dateStr: string) => formatDate(dateStr, false)

function formatLocation(city: string | null | undefined, region: string | null | undefined, country: string | null | undefined): string {
    const parts: string[] = []
    if (city) parts.push(city)
    if (region) parts.push(region)
    if (country) parts.push(country)
    return parts.join(', ') || '—'
}

function formatDevice(deviceType: string | null | undefined, browser: string | null | undefined, os: string | null | undefined): string {
    const parts: string[] = []
    if (deviceType) parts.push(deviceType)
    if (browser) parts.push(browser)
    if (os) parts.push(os)
    return parts.filter(Boolean).join(' · ') || '—'
}

function extractSubdirectory(urlStr: string | null | undefined): string {
    if (!urlStr) return '—'
    try {
        // If urlStr is relative path already, return it (ensure it starts with /)
        if (/^\//.test(urlStr)) {
            // root or empty subdirectory -> show Welcome Page
            if (urlStr === '/' || urlStr === '') return 'Welcome Page'
            // remove trailing slash
            return urlStr.replace(/\/?$/, '')
        }
        const url = new URL(urlStr, window.location.origin)
        // include pathname and optional hash or search? User asked subdirectory only — return pathname
        if (url.pathname === '' || url.pathname === '/') return 'Welcome Page'
        return url.pathname.replace(/\/?$/, '')
    } catch (e) {
        // If parsing fails, attempt basic extraction
        const match = urlStr.match(/https?:\/\/[^/]+(\/.*)/)
        if (match && match[1]) {
            const path = match[1].replace(/\/?$/, '')
            return path === '' || path === '/' ? 'Welcome Page' : path
        }
        return urlStr
    }
}
</script>

<template>
    <Head title="Analytics" />

    <AdminLayout>
        <div class="w-full px-3 py-4 lg:px-8 lg:py-10">
            <div class="mx-auto max-w-7xl">
                <!-- Page Header -->
                <div class="mb-4 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Analytics</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Monitor site visits</p>
                    </div>
                </div>

                <!-- Daily Visit Stats Table -->
                <div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Visit Stats</h3>
                            <Button 
                                variant="destructive" 
                                size="sm"
                                @click="showDailyStatsBulkDeleteModal = true"
                            >
                                <Trash2 class="h-4 w-4 mr-2" />
                                Bulk Delete
                            </Button>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Summary of daily visits and top pages.</p>
                        <div v-if="!daily_visit_stats.data.length" class="py-12 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                <span class="text-3xl">📊</span>
                            </div>
                            <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                                No daily stats found
                            </h3>
                            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                                No data available for the selected period.
                            </p>
                        </div>
                        <div v-else>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Date</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Total Visits</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Unique Visits</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Page Views</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Top Page</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Top Referer</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Mobile</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Desktop</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Tablet</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800">
                                        <tr v-for="stat in daily_visit_stats.data" :key="stat.id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.date_formatted }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.total_visits }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.unique_visits }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.page_views }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400" :title="stat.top_page">{{ stat.top_page }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400" :title="stat.top_referer">{{ stat.top_referer }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.mobile_visits }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.desktop_visits }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.tablet_visits }}</td>
                                            <td class="px-3 py-2 text-sm">
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="handleDeleteDailyStat(stat.id)"
                                                    class="h-8 w-8 p-0"
                                                >
                                                    <Trash2 class="h-4 w-4 text-red-600 dark:text-red-400" />
                                                </Button>
                                            </td>
                                        </tr>
                                </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-gray-700" v-if="daily_visit_stats.data.length">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Showing {{ daily_visit_stats.meta.from }} to {{ daily_visit_stats.meta.to }} of {{ daily_visit_stats.meta.total }} results
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="daily_visit_stats.meta.current_page === 1"
                                    @click="router.visit('/admin/analytics', { data: { daily_page: daily_visit_stats.meta.current_page - 1, site_page: site_visits.meta?.current_page || 1 }, preserveState: true, preserveScroll: true })"
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="daily_visit_stats.meta.current_page === daily_visit_stats.meta.last_page"
                                    @click="router.visit('/admin/analytics', { data: { daily_page: daily_visit_stats.meta.current_page + 1, site_page: site_visits.meta?.current_page || 1 }, preserveState: true, preserveScroll: true })"
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Site Visits Table -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Site Visits</h3>
                            <Button 
                                variant="destructive" 
                                size="sm"
                                @click="showSiteVisitsBulkDeleteModal = true"
                            >
                                <Trash2 class="h-4 w-4 mr-2" />
                                Bulk Delete
                            </Button>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Detailed list of site visits.</p>
                        <div v-if="!site_visits.data.length" class="py-12 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                <span class="text-3xl">🖥️</span>
                            </div>
                            <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                                No site visits found
                            </h3>
                            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                                No data available for the selected period.
                            </p>
                        </div>
                        <div v-else>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Visited At</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Page</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Location</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">IP</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Device</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Page Views</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Referer</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Flags</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800">
                                        <tr v-for="visit in site_visits.data" :key="visit.id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ visit.visited_at_formatted }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ visit.page_display }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ visit.location_formatted }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ visit.ip_address }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ visit.device_formatted }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ visit.page_views }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 truncate" :title="visit.referer">{{ visit.referer }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ visit.flags_formatted }}</td>
                                            <td class="px-3 py-2 text-sm">
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    @click="handleDeleteSiteVisit(visit.id)"
                                                    class="h-8 w-8 p-0"
                                                >
                                                    <Trash2 class="h-4 w-4 text-red-600 dark:text-red-400" />
                                                </Button>
                                            </td>
                                        </tr>
                                </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-gray-700" v-if="site_visits.data.length">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Showing {{ site_visits.meta.from }} to {{ site_visits.meta.to }} of {{ site_visits.meta.total }} results
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="site_visits.meta.current_page === 1"
                                    @click="router.visit('/admin/analytics', { data: { daily_page: daily_visit_stats.meta?.current_page || 1, site_page: site_visits.meta.current_page - 1 }, preserveState: true, preserveScroll: true })"
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="site_visits.meta.current_page === site_visits.meta.last_page"
                                    @click="router.visit('/admin/analytics', { data: { daily_page: daily_visit_stats.meta?.current_page || 1, site_page: site_visits.meta.current_page + 1 }, preserveState: true, preserveScroll: true })"
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

    <!-- Bulk Delete Modals -->
    <DeleteAnalyticsModal
        v-model:open="showDailyStatsBulkDeleteModal"
        type="daily_stats"
    />
    <DeleteAnalyticsModal
        v-model:open="showSiteVisitsBulkDeleteModal"
        type="site_visits"
    />
</template>