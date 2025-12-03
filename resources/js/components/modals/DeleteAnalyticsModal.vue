<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { showSuccessAlert, showErrorAlert } from '@/lib/swal'

interface Props {
    open: boolean
    type: 'daily_stats' | 'site_visits'
}

interface Emits {
    (e: 'update:open', value: boolean): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const startDate = ref('')
const endDate = ref('')
const isDeleting = ref(false)

const modalTitle = computed(() => {
    return props.type === 'daily_stats' ? 'Bulk Delete Daily Visit Stats' : 'Bulk Delete Site Visits'
})

const modalDescription = computed(() => {
    return props.type === 'daily_stats' 
        ? 'Delete all daily visit statistics records within the specified date range. This action cannot be undone.'
        : 'Delete all site visit records within the specified date range. This action cannot be undone.'
})

const isFormValid = computed(() => {
    return startDate.value && endDate.value && new Date(startDate.value) <= new Date(endDate.value)
})

const closeModal = () => {
    emit('update:open', false)
    startDate.value = ''
    endDate.value = ''
    isDeleting.value = false
}

const handleDelete = () => {
    if (!isFormValid.value) {
        showErrorAlert({
            title: 'Invalid Date Range',
            text: 'Please select valid start and end dates.'
        })
        return
    }

    isDeleting.value = true

    const endpoint = props.type === 'daily_stats' 
        ? '/admin/analytics/daily-stats/bulk-delete'
        : '/admin/analytics/site-visits/bulk-delete'

    router.post(endpoint, {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showSuccessAlert({
                title: 'Deleted Successfully',
                text: `Records from ${startDate.value} to ${endDate.value} have been deleted.`
            })
            closeModal()
        },
        onError: (errors) => {
            isDeleting.value = false
            const errorMessage = errors.start_date || errors.end_date || 'Failed to delete records. Please try again.'
            showErrorAlert({
                title: 'Delete Failed',
                text: errorMessage
            })
        },
        onFinish: () => {
            isDeleting.value = false
        }
    })
}

const setToday = () => {
    const today = new Date().toISOString().split('T')[0]
    startDate.value = today
    endDate.value = today
}

const setThisWeek = () => {
    const today = new Date()
    const weekAgo = new Date(today)
    weekAgo.setDate(today.getDate() - 7)
    startDate.value = weekAgo.toISOString().split('T')[0]
    endDate.value = today.toISOString().split('T')[0]
}

const setThisMonth = () => {
    const today = new Date()
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
    startDate.value = firstDay.toISOString().split('T')[0]
    endDate.value = today.toISOString().split('T')[0]
}
</script>

<template>
    <Dialog :open="open" @update:open="closeModal">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ modalTitle }}</DialogTitle>
                <DialogDescription>
                    {{ modalDescription }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <!-- Quick Date Selection -->
                <div class="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="setToday"
                    >
                        Today
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="setThisWeek"
                    >
                        Last 7 Days
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="setThisMonth"
                    >
                        This Month
                    </Button>
                </div>

                <!-- Start Date -->
                <div class="space-y-2">
                    <Label for="start_date">Start Date</Label>
                    <Input
                        id="start_date"
                        v-model="startDate"
                        type="date"
                        :disabled="isDeleting"
                    />
                </div>

                <!-- End Date -->
                <div class="space-y-2">
                    <Label for="end_date">End Date</Label>
                    <Input
                        id="end_date"
                        v-model="endDate"
                        type="date"
                        :disabled="isDeleting"
                    />
                </div>

                <div v-if="startDate && endDate && new Date(startDate) > new Date(endDate)" class="text-sm text-red-600 dark:text-red-400">
                    End date must be greater than or equal to start date.
                </div>
            </div>

            <DialogFooter class="flex-col sm:flex-row gap-2">
                <Button
                    type="button"
                    variant="outline"
                    @click="closeModal"
                    :disabled="isDeleting"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    @click="handleDelete"
                    :disabled="!isFormValid || isDeleting"
                >
                    {{ isDeleting ? 'Deleting...' : 'Delete Records' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
