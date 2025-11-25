<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'

interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
}

interface SiteVisit {
    session_id: string
    ip_address: string
    user_agent: string
    referer: string
    page_url: string
    country: string
    region: string
    city: string
    device_type: string
    browser: string
    os: string
    is_bot: boolean
    is_unique: boolean
    page_views: number
    visited_at: string
}

interface DailyVisitStat {
    date: string
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

function formatVisitDate(dateStr: string): string {
    // Format as 'Nov 4, 1:30 PM'
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return dateStr
    const options: Intl.DateTimeFormatOptions = {
        month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true
    }
    let formatted = date.toLocaleString('en-US', options)
    formatted = formatted.replace(/,\s(\d{1,2}:\d{2}\s[AP]M)/, ', $1')
    return formatted
}

function formatStatsDate(dateStr: string): string {
    // Format as 'Nov 4, 2025'
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return dateStr
    const options: Intl.DateTimeFormatOptions = {
        month: 'short', day: 'numeric', year: 'numeric'
    }
    return date.toLocaleDateString('en-US', options)
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
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Analytics</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Monitor site visits</p>
                    </div>
                </div>

                <!-- Daily Visit Stats Table -->
                <div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="px-4 py-4 sm:px-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Daily Visit Stats</h3>
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
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800">
                                    <tr v-for="stat in daily_visit_stats.data" :key="stat.date" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ formatStatsDate(stat.date) }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.total_visits }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.unique_visits }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.page_views }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400" :title="stat.top_page">{{ stat.top_page }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400" :title="stat.top_referer">{{ stat.top_referer }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.mobile_visits }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.desktop_visits }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ stat.tablet_visits }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Site Visits Table -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="px-4 py-4 sm:px-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Site Visits</h3>
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
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">IP Address</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Country</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Region</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">City</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Device</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Browser</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">OS</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Referer</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Page URL</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Page Views</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Visited At</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800">
                                    <tr v-for="visit in site_visits.data" :key="visit.session_id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ visit.ip_address }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ visit.country }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ visit.region }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ visit.city }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ visit.device_type }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ visit.browser }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ visit.os }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400" :title="visit.referer">{{ visit.referer }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400" :title="visit.page_url">{{ visit.page_url }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ visit.page_views }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span :title="visit.visited_at">{{ formatVisitDate(visit.visited_at) }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>