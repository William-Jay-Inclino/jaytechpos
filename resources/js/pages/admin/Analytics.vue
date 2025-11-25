<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
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
                            <!-- Desktop / Table view -->
                            <div class="hidden md:block overflow-x-auto">
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

                            <!-- Mobile / Card view -->
                            <div class="md:hidden grid gap-3">
                                <div v-for="stat in daily_visit_stats.data" :key="stat.date" class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ formatStatsDate(stat.date) }}</div>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Top: <span class="text-gray-700 dark:text-gray-200">{{ stat.top_page || '—' }}</span></div>
                                        </div>
                                        <div class="text-right text-sm text-gray-600 dark:text-gray-300">
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ stat.total_visits }}</div>
                                            <div class="text-xs">visits</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 grid grid-cols-3 gap-3 text-sm text-gray-600 dark:text-gray-300">
                                        <div class="text-center">Unique<br/><span class="font-semibold text-gray-900 dark:text-white">{{ stat.unique_visits }}</span></div>
                                        <div class="text-center">Page Views<br/><span class="font-semibold text-gray-900 dark:text-white">{{ stat.page_views }}</span></div>
                                        <div class="text-center">Mobile<br/><span class="font-semibold text-gray-900 dark:text-white">{{ stat.mobile_visits }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="daily_visit_stats.links && daily_visit_stats.links.length" class="px-4 py-3 sm:px-6">
                            <nav class="flex items-center justify-between">
                                <div class="flex -space-x-px">
                                    <template v-for="(link, idx) in daily_visit_stats.links" :key="idx">
                                        <Link v-if="link.url" :href="link.url" class="px-3 py-1 border border-gray-200 bg-white dark:bg-gray-700 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50" :class="{'bg-gray-100 dark:bg-gray-600 font-semibold': link.active}">
                                            <span v-html="link.label"></span>
                                        </Link>
                                        <span v-else class="px-3 py-1 border border-gray-200 bg-gray-100 text-sm text-gray-500" v-html="link.label"></span>
                                    </template>
                                </div>
                            </nav>
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
                            <!-- Desktop / Table view -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Visited At</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Page URL</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Location</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">IP Address</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Device</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Referer</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 dark:text-white">Page Views</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800">
                                        <tr v-for="visit in site_visits.data" :key="visit.session_id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">
                                                {{ formatVisitDate(visit.visited_at) }}
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ extractSubdirectory(visit.page_url) }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ formatLocation(visit.city, visit.region, visit.country) }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ visit.ip_address }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ formatDevice(visit.device_type, visit.browser, visit.os) }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">{{ visit.referer }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">{{ visit.page_views }}</td>
                                        </tr>
                                </tbody>
                                </table>
                            </div>

                            <!-- Mobile / Card view for site visits -->
                            <div class="md:hidden grid gap-3">
                                <div v-for="visit in site_visits.data" :key="visit.session_id" class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ extractSubdirectory(visit.page_url) || 'Unknown page' }}</div>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">{{ formatLocation(visit.city, visit.region, visit.country) }}</div>
                                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">Referer: <span class="text-gray-800 dark:text-gray-200">{{ visit.referer || '—' }}</span></div>
                                        </div>
                                        <div class="ml-3 text-right text-sm">
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ visit.page_views }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatVisitDate(visit.visited_at) }}</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-300">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-2 py-1 dark:bg-gray-700">IP: {{ visit.ip_address }}</span>
                                        <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-2 py-1 dark:bg-gray-700">Device: {{ formatDevice(visit.device_type, visit.browser, visit.os) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Site visits pagination (moved here so it appears under the Site Visits table) -->
                        <div v-if="site_visits.links && site_visits.links.length" class="px-4 py-3 sm:px-6">
                            <nav class="flex items-center justify-between">
                                <div class="flex -space-x-px">
                                    <template v-for="(link, idx) in site_visits.links" :key="idx">
                                        <Link v-if="link.url" :href="link.url" class="px-3 py-1 border border-gray-200 bg-white dark:bg-gray-700 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50" :class="{'bg-gray-100 dark:bg-gray-600 font-semibold': link.active}">
                                            <span v-html="link.label"></span>
                                        </Link>
                                        <span v-else class="px-3 py-1 border border-gray-200 bg-gray-100 text-sm text-gray-500" v-html="link.label"></span>
                                    </template>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>