<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome to SimplePOS">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-gradient-to-b from-white via-rose-50 to-rose-100 dark:from-[#070707] dark:via-[#0f0f0f] dark:to-[#0a0a0a]">
        <div class="mx-auto max-w-6xl px-6 py-10">
            <!-- Header / Nav -->
            <header class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-rose-600 p-2 text-white font-bold">SP</div>
                    <div>
                        <h2 class="text-lg font-semibold">SimplePOS</h2>
                        <p class="text-xs text-neutral-500">Simple. Fast. Made for PH sari-sari stores.</p>
                    </div>
                </div>

                <nav class="flex items-center gap-3 text-sm">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md bg-white/60 px-4 py-2 text-neutral-800 shadow-sm hover:bg-white"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-4 py-2 text-neutral-800 hover:underline"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-md bg-rose-600 px-4 py-2 text-white shadow hover:bg-rose-700"
                        >
                            Create account
                        </Link>
                    </template>
                </nav>
            </header>

            <!-- Hero -->
            <section class="mt-10 grid gap-8 lg:grid-cols-2 lg:items-center">
                <div>
                    <h1 class="text-3xl font-extrabold leading-tight text-neutral-900 dark:text-white">
                        SimplePOS — A simple POS for small Philippine stores
                    </h1>
                    <p class="mt-4 text-neutral-600 dark:text-neutral-300 max-w-xl">
                        Run sales, manage products and customers, track expenses, and
                        enable "utang" with automatic monthly interest — all from any
                        device. Win back time and earn extra income from utang interest.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            :href="register()"
                            class="inline-flex items-center gap-2 rounded-md bg-rose-600 px-5 py-3 text-white shadow-lg hover:bg-rose-700"
                        >
                            Get started — Create account
                        </Link>
                        <Link
                            :href="login()"
                            class="inline-flex items-center gap-2 rounded-md px-5 py-3 text-neutral-700 bg-white/60 hover:underline"
                        >
                            Already have an account?
                        </Link>
                    </div>

                    <!-- Key selling points -->
                    <div class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f]">
                            <h4 class="font-semibold">Sales & Payments</h4>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Fast checkout and cash tracking for busy counters.</p>
                        </div>
                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f]">
                            <h4 class="font-semibold">Utang & Interest</h4>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Allow customers to buy on credit. Automatic monthly interest calculation helps you earn more.</p>
                        </div>
                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f]">
                            <h4 class="font-semibold">Products & Customers</h4>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Easy product management and customer profiles with balances.</p>
                        </div>
                        <div class="rounded-lg border bg-white p-4 shadow-sm dark:bg-[#0f0f0f]">
                            <h4 class="font-semibold">Reports & Cash Flow</h4>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Sales reports and cash flow tracking to help grow your business.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-center">
                    <!-- Illustration card -->
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-[#0b0b0b]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-neutral-500">Today</p>
                                <h3 class="mt-1 text-lg font-semibold text-neutral-900 dark:text-white">Sales overview</h3>
                            </div>
                            <div class="rounded-full bg-rose-50 p-2 text-rose-600 font-bold">₱</div>
                        </div>

                        <div class="mt-6">
                            <div class="h-36 w-full rounded-lg bg-gradient-to-tr from-rose-100 to-rose-50 dark:from-[#1a0003] dark:to-[#120205]"></div>
                            <div class="mt-4 flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-neutral-500">Total sales</p>
                                    <p class="text-xl font-semibold">₱12,540</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-neutral-500">Utang outstanding</p>
                                    <p class="text-xl font-semibold text-amber-600">₱2,300</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Feature highlights -->
            <section class="mt-12 rounded-xl bg-white/70 p-6 shadow-sm dark:bg-[#0b0b0b]/60">
                <h3 class="text-lg font-semibold">Built for small retail stores in the Philippines</h3>
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                    SimplePOS works on desktop, tablet, or phone. No complicated setup — just sign up and start selling.
                </p>

                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 rounded-full bg-rose-600 p-2 text-white">✓</span>
                        <div>
                            <div class="font-medium">Sales transaction</div>
                            <div class="text-sm text-neutral-500">Quick POS for faster checkout.</div>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 rounded-full bg-rose-600 p-2 text-white">✓</span>
                        <div>
                            <div class="font-medium">Utang Payment</div>
                            <div class="text-sm text-neutral-500">Record payments and balances easily.</div>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 rounded-full bg-rose-600 p-2 text-white">✓</span>
                        <div>
                            <div class="font-medium">Products & Customers</div>
                            <div class="text-sm text-neutral-500">Manage inventory and customer history.</div>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 rounded-full bg-rose-600 p-2 text-white">✓</span>
                        <div>
                            <div class="font-medium">Reports & Cash flow</div>
                            <div class="text-sm text-neutral-500">Understand your best sellers and money flow.</div>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Footer / small note -->
            <footer class="mt-8 text-center text-sm text-neutral-500">
                <p>
                    SimplePOS — simple point-of-sale, with utang support and automatic interest calculation. Accessible on phones and tablets.
                </p>
                <p class="mt-2">Made for sari-sari stores and micro-retailers in the Philippines.</p>
            </footer>
        </div>
    </div>
</template>
