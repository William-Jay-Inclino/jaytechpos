<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Html5Qrcode } from 'html5-qrcode';
import { Camera, XCircle } from 'lucide-vue-next';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    scanned: [barcode: string];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const scanner = ref<Html5Qrcode | null>(null);
const isScanning = ref(false);
const errorMessage = ref('');
const scannedCode = ref('');
const scannerContainerId = 'barcode-scanner-container';

async function startScanning(): Promise<void> {
    errorMessage.value = '';
    scannedCode.value = '';

    try {
        scanner.value = new Html5Qrcode(scannerContainerId);
        isScanning.value = true;

        await scanner.value.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 280, height: 150 },
                aspectRatio: 1.0,
            },
            (decodedText: string) => {
                scannedCode.value = decodedText;
                stopScanning();
                emit('scanned', decodedText);
                isOpen.value = false;
            },
            () => {
                // Ignore scan failures (no barcode found yet)
            },
        );
    } catch (err: unknown) {
        isScanning.value = false;
        if (err instanceof Error) {
            if (err.message.includes('Permission')) {
                errorMessage.value = 'Camera permission denied. Please allow camera access and try again.';
            } else {
                errorMessage.value = `Unable to start camera: ${err.message}`;
            }
        } else {
            errorMessage.value = 'Unable to start camera. Please make sure your device has a camera and you have granted permission.';
        }
    }
}

async function stopScanning(): Promise<void> {
    if (scanner.value && isScanning.value) {
        try {
            await scanner.value.stop();
            scanner.value.clear();
        } catch {
            // Ignore stop errors
        }
        isScanning.value = false;
    }
    scanner.value = null;
}

// Start scanning when dialog opens, stop when it closes
watch(isOpen, async (newValue) => {
    if (newValue) {
        await nextTick();
        startScanning();
    } else {
        stopScanning();
    }
});

onUnmounted(() => {
    stopScanning();
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-md">
            <DialogHeader class="pb-2 text-center">
                <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/20"
                >
                    <Camera class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <DialogTitle>Scan Barcode</DialogTitle>
                <DialogDescription class="text-sm text-muted-foreground">
                    Point your camera at a barcode to scan it
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Scanner viewport -->
                <div
                    class="relative overflow-hidden rounded-lg border border-gray-200 bg-black dark:border-gray-700"
                >
                    <div :id="scannerContainerId" class="min-h-[280px] w-full"></div>

                    <!-- Loading overlay -->
                    <div
                        v-if="!isScanning && !errorMessage"
                        class="absolute inset-0 flex items-center justify-center bg-black/80"
                    >
                        <div class="text-center text-white">
                            <div
                                class="mx-auto mb-2 h-8 w-8 animate-spin rounded-full border-4 border-white/30 border-t-white"
                            ></div>
                            <p class="text-sm">Starting camera...</p>
                        </div>
                    </div>
                </div>

                <!-- Error state -->
                <div
                    v-if="errorMessage"
                    class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20"
                >
                    <XCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-500" />
                    <div>
                        <p class="text-sm font-medium text-red-700 dark:text-red-400">
                            {{ errorMessage }}
                        </p>
                        <Button variant="outline" size="sm" class="mt-2" @click="startScanning">
                            Try Again
                        </Button>
                    </div>
                </div>

                <!-- Hint -->
                <p class="text-center text-xs text-muted-foreground">
                    Ensure the barcode is well-lit and within the scanning area
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
