<script setup lang="ts">
import { ref, watch, reactive } from 'vue';
import axios from 'axios';
import { showErrorToast, showSuccessToast } from '@/lib/toast';
import { formatCurrency } from '@/utils/currency';

// UI Components
import { Button } from '@/components/ui/button';
import { Input, InputCurrency } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    open: boolean;
    customerId: number;
    customerName: string;
    currentBalance: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'success': [];
}>();

// Form state
const form = reactive({
    balance: '',
    note: '',
    isLoading: false,
    errors: {} as Record<string, string>
});

// Reset form when modal opens
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        form.balance = props.currentBalance.toString();
        form.note = '';
        form.errors = {};
    }
});

const closeModal = () => {
    emit('update:open', false);
};

const handleSubmit = async () => {
    const newBalance = parseFloat(form.balance);
    
    // Validation
    if (isNaN(newBalance) || newBalance < 0) {
        showErrorToast('Please enter a valid balance amount');
        return;
    }

    if (newBalance === props.currentBalance) {
        showErrorToast('New balance must be different from current balance');
        return;
    }

    if (form.note.length > 100) {
        showErrorToast('Note must not exceed 100 characters');
        return;
    }

    form.isLoading = true;
    form.errors = {};

    try {
        const res = await axios.post(`/customers/${props.customerId}/balance`, {
            _method: 'PATCH',
            balance: parseFloat(String(newBalance)),
            note: form.note || null,
        });

        const data = res?.data || {};

        if (data.success) {
            showSuccessToast(data.msg || 'Customer balance updated successfully!');
            emit('success');
            closeModal();
        } else {
            showErrorToast(data.msg || 'Failed to update balance');
        }
    } catch (error: any) {
        const errors = error?.response?.data?.errors;
        if (errors) {
            form.errors = errors;
            if (errors.balance) {
                showErrorToast(errors.balance[0] || errors.balance);
            } else if (errors.note) {
                showErrorToast(errors.note[0] || errors.note);
            } else {
                showErrorToast('Failed to update balance. Please try again.');
            }
        } else {
            const message = error?.response?.data?.msg || error?.response?.data?.message || 'Failed to update balance';
            showErrorToast(message);
        }
    } finally {
        form.isLoading = false;
    }
};

</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="text-lg font-semibold">
                    Edit Balance
                </DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <!-- Current Balance Display -->
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                        Current Balance
                    </p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ formatCurrency(currentBalance) }}
                    </p>
                </div>

                <!-- New Balance Input -->
                <div class="space-y-2">
                    <Label for="balance" class="text-sm font-medium">
                        New Balance (₱)
                        <span class="text-red-500">*</span>
                    </Label>
                    <InputCurrency
                        id="balance"
                        v-model="form.balance"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="text-right"
                        :disabled="form.isLoading"
                        required
                    />
                </div>

                <!-- Note Input -->
                <div class="space-y-2">
                    <Label for="note" class="text-sm font-medium">
                        Note
                        <span class="text-gray-500">(Optional)</span>
                    </Label>
                    <Input
                        id="note"
                        v-model="form.note"
                        type="text"
                        maxlength="500"
                        placeholder="Reason for balance update..."
                        :disabled="form.isLoading"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ form.note.length }}/100 characters
                    </p>
                </div>
            </div>

            <DialogFooter class="flex gap-2 sm:gap-2">
                <Button
                    type="button"
                    variant="outline"
                    @click="closeModal"
                    :disabled="form.isLoading"
                    class="flex-1"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    @click="handleSubmit"
                    :disabled="form.isLoading || form.balance === '' || form.balance === null || typeof form.balance === 'undefined'"
                    class="flex-1"
                >
                    <span v-if="form.isLoading" class="flex items-center gap-2">
                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></div>
                        Updating...
                    </span>
                    <span v-else>
                        Update Balance
                    </span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>