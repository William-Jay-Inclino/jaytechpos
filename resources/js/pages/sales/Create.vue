<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { type BreadcrumbItem } from '@/types';
    import { Head, Link } from '@inertiajs/vue3';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
        SelectValue,
    } from '@/components/ui/select';
    import {
        Card,
        CardContent,
        CardHeader,
        CardTitle,
        CardDescription,
    } from '@/components/ui/card';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow,
    } from '@/components/ui/table';
    import { Trash2, Plus } from "lucide-vue-next";

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Sales', href: '/sales' },
        { title: 'New Sale', href: '/sales/create' },
    ];
</script>

<template>
    <Head title="Create Sale" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-8xl mx-auto p-6 md:p-8">
            <!-- Page Header -->
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-2xl font-semibold">🛒 New Sale</h1>
                <div class="flex gap-2">
                    <Link href="/sales">
                        <Button variant="outline" size="lg">Cancel</Button>
                    </Link>
                    <Button variant="default" size="lg">Checkout</Button>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Sale Section (1/3) -->
                <Card class="shadow-sm border rounded-2xl lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="text-lg">Sale Info</CardTitle>
                        <CardDescription>Enter sale details</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Customer -->
                        <div class="space-y-2">
                            <Label for="customer">Customer</Label>
                            <div class="flex gap-2">
                                <Select>
                                    <SelectTrigger class="flex-1">
                                        <SelectValue placeholder="Select customer" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Juan Dela Cruz</SelectItem>
                                        <SelectItem value="2">Maria Santos</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button variant="outline" size="icon" title="Add new customer">
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Sale Fields -->
                        <div class="grid gap-4">
                            <div class="space-y-2">
                                <Label for="total_amount">Total Amount</Label>
                                <Input type="number" id="total_amount" placeholder="0.00" />
                            </div>
                            <div class="space-y-2">
                                <Label for="discount_amount">Discount</Label>
                                <Input type="number" id="discount_amount" placeholder="0.00" />
                            </div>
                            <div class="space-y-2">
                                <Label for="vat_amount">VAT</Label>
                                <Input type="number" id="vat_amount" placeholder="0.00" />
                            </div>
                            <div class="space-y-2">
                                <Label for="net_amount">Net Amount</Label>
                                <Input type="number" id="net_amount" placeholder="0.00" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Particulars Section (2/3) -->
                <Card class="shadow-sm border rounded-2xl lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="text-lg">Particulars</CardTitle>
                        <CardDescription>Add items to this sale</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Add Item Row -->
                        <div class="grid md:grid-cols-5 gap-4 items-end">
                            <div class="space-y-2 md:col-span-2">
                                <Label for="product">Product</Label>
                                <Select>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select product" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Laptop</SelectItem>
                                        <SelectItem value="2">Mouse</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="quantity">Quantity</Label>
                                <Input type="number" id="quantity" placeholder="1" />
                            </div>

                            <div class="space-y-2">
                                <Label for="unit_price">Unit Price</Label>
                                <Input type="number" id="unit_price" placeholder="0.00" />
                            </div>

                            <div class="flex justify-end">
                                <Button variant="secondary" class="w-full text-primary font-bold">+ Add Item</Button>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="overflow-x-auto rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Product</TableHead>
                                        <TableHead class="text-right">Quantity</TableHead>
                                        <TableHead class="text-right">Unit Price</TableHead>
                                        <TableHead class="text-right">Discount</TableHead>
                                        <TableHead class="text-right">VAT</TableHead>
                                        <TableHead class="text-right">Total</TableHead>
                                        <TableHead></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell class="flex items-center gap-2">
                                            <Avatar class="h-16 w-16 rounded-md">
                                                <AvatarImage src="https://images.unsplash.com/photo-xyz" alt="Laptop" />
                                                <AvatarFallback>L</AvatarFallback>
                                            </Avatar>
                                            <span>Laptop</span>
                                        </TableCell>
                                        <TableCell class="text-right">1</TableCell>
                                        <TableCell class="text-right">50,000.00</TableCell>
                                        <TableCell class="text-right">0.00</TableCell>
                                        <TableCell class="text-right">6,000.00</TableCell>
                                        <TableCell class="text-right">56,000.00</TableCell>
                                        <TableCell>
                                            <Button variant="ghost" size="icon">
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell class="flex items-center gap-2">
                                            <Avatar class="h-16 w-16 rounded-md">
                                                <AvatarImage src="https://images.unsplash.com/photo-xyz" alt="Mouse" />
                                                <AvatarFallback>M</AvatarFallback>
                                            </Avatar>
                                            <span>Mouse</span>
                                        </TableCell>
                                        <TableCell class="text-right">2</TableCell>
                                        <TableCell class="text-right">500.00</TableCell>
                                        <TableCell class="text-right">0.00</TableCell>
                                        <TableCell class="text-right">120.00</TableCell>
                                        <TableCell class="text-right">1,120.00</TableCell>
                                        <TableCell>
                                            <Button variant="ghost" size="icon">
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
