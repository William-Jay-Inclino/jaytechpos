<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import InputError from '@/components/InputError.vue';
import { showSuccessToast, showErrorToast } from '@/lib/toast';
import { FolderOpen, Plus, Edit, Trash2, Search } from 'lucide-vue-next';
import { computed, ref, watch, nextTick } from 'vue';
import axios from 'axios';

interface Category {
    id: number;
    name: string;
    description?: string;
    status: string;
    is_default: boolean;
    user_id: number;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'categoryUpdated': [];
}>();

// Reactive state
const categories = ref<Category[]>([]);
const loading = ref(false);
const isEditing = ref(false);
const editingCategory = ref<Category | null>(null);
const showForm = ref(false);
const searchQuery = ref('');

// Form data
const form = ref({
    name: '',
    is_default: false,
});

const errors = ref<Record<string, string>>({});
const formLoading = ref(false);

// Computed properties
const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const formTitle = computed(() => {
    return isEditing.value ? 'Edit Category' : 'Create New Category';
});

const submitButtonText = computed(() => {
    if (formLoading.value) {
        return isEditing.value ? 'Updating...' : 'Creating...';
    }
    return isEditing.value ? 'Update Category' : 'Create Category';
});

const filteredCategories = computed(() => {
    if (!searchQuery.value.trim()) {
        return categories.value;
    }
    
    const query = searchQuery.value.toLowerCase().trim();
    return categories.value.filter(category => 
        category.name.toLowerCase().includes(query)
    );
});

// Methods
async function loadCategories() {
    loading.value = true;
    try {
        const response = await axios.get('/api/product-categories');
        if (response.data.success) {
            categories.value = response.data.categories;
        }
    } catch (error: any) {
        console.error('Error loading categories:', error);
        showErrorToast('Failed to load categories');
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    form.value = {
        name: '',
        is_default: false,
    };
    errors.value = {};
    isEditing.value = false;
    editingCategory.value = null;
    showForm.value = false;
}

function clearSearch() {
    searchQuery.value = '';
}

function startCreate() {
    resetForm();
    showForm.value = true;
    nextTick(() => {
        const nameInput = document.getElementById('category-name');
        if (nameInput) nameInput.focus();
    });
}

function startEdit(category: Category) {
    resetForm();
    isEditing.value = true;
    editingCategory.value = category;
    form.value = {
        name: category.name,
        is_default: category.is_default || false,
    };
    showForm.value = true;
    nextTick(() => {
        const nameInput = document.getElementById('category-name');
        if (nameInput) nameInput.focus();
    });
}

async function submitForm() {
    if (formLoading.value) return;

    errors.value = {};
    formLoading.value = true;

    try {
        const url = isEditing.value 
            ? `/api/product-categories/${editingCategory.value!.id}` 
            : '/api/product-categories';
        
        const method = isEditing.value ? 'put' : 'post';
        
        const formData = {
            ...form.value,
            description: '',
            status: 'active'
        };
        
        const response = await axios[method](url, formData);
        
        if (response.data.success) {
            showSuccessToast(response.data.message);
            resetForm();
            await loadCategories();
            emit('categoryUpdated');
        }
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            showErrorToast('An error occurred while saving the category');
        }
    } finally {
        formLoading.value = false;
    }
}

async function deleteCategory(category: Category) {
    try {
        const response = await axios.delete(`/api/product-categories/${category.id}`);
        
        if (response.data.success) {
            showSuccessToast(response.data.message);
            await loadCategories();
            emit('categoryUpdated');
        }
    } catch (error: any) {
        if (error.response?.status === 422) {
            showErrorToast(error.response.data.message);
        } else {
            showErrorToast('An error occurred while deleting the category');
        }
    }
}

async function toggleDefault(category: Category) {
    try {
        const newDefaultState = !category.is_default;
        
        // Optimistically update the UI - ensure only ONE category can be default
        categories.value = categories.value.map(cat => ({
            ...cat,
            is_default: cat.id === category.id ? newDefaultState : false
        }));
        
        const response = await axios.put(`/api/product-categories/${category.id}`, {
            name: category.name,
            description: category.description || '',
            status: category.status || 'active',
            is_default: newDefaultState,
        });
        
        if (response.data.success) {
            showSuccessToast(response.data.message);
            emit('categoryUpdated');
        }
    } catch (error: any) {
        // Reload categories to revert the optimistic update on error
        await loadCategories();
        if (error.response?.status === 422) {
            showErrorToast(error.response.data.message || 'Failed to update default category');
        } else {
            showErrorToast('An error occurred while updating the category');
        }
    }
}



// Watchers
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        loadCategories();
        clearSearch();
    } else {
        resetForm();
        clearSearch();
    }
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[95vh] w-[95vw] max-w-2xl p-4 sm:p-6 m-2 sm:m-4 overflow-hidden">
            <DialogHeader class="space-y-2 pb-4">
                <DialogTitle class="flex items-center gap-2 text-lg sm:text-xl font-semibold">
                    <FolderOpen class="h-4 w-4 sm:h-5 sm:w-5" />
                    Manage Categories
                </DialogTitle>
            </DialogHeader>

            <div class="space-y-6">
                <!-- Categories List View -->
                <div v-if="!showForm">
                    <!-- Header with New Button -->
                    <div class="flex items-center justify-center sm:justify-between mb-4">
                        <Button @click="startCreate" class="gap-2 w-full sm:w-auto">
                            <Plus class="h-4 w-4" />
                            <span class="hidden xs:inline">New Category</span>
                            <span class="xs:hidden">New</span>
                        </Button>
                    </div>

                    <!-- Search -->
                    <div class="relative mb-4">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search categories..."
                            class="pl-9"
                        />
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading" class="space-y-3">
                        <div v-for="i in 3" :key="i" class="h-16 animate-pulse rounded-lg bg-muted"></div>
                    </div>

                    <!-- Categories Grid -->
                    <div v-else-if="categories.length > 0" class="space-y-3 max-h-[60vh] sm:max-h-96 overflow-y-auto">
                        <!-- No results -->
                        <div 
                            v-if="filteredCategories.length === 0 && searchQuery.trim()"
                            class="flex flex-col items-center justify-center py-8 sm:py-12 text-center"
                        >
                            <Search class="h-8 w-8 sm:h-12 sm:w-12 text-muted-foreground mb-4" />
                            <h4 class="font-medium mb-2 text-sm sm:text-base">No categories found</h4>
                            <p class="text-xs sm:text-sm text-muted-foreground">Try adjusting your search terms</p>
                        </div>

                        <!-- Category Cards -->
                        <div
                            v-for="category in filteredCategories"
                            :key="category.id"
                            class="border rounded-lg hover:bg-muted/50 transition-colors"
                        >
                            <!-- Mobile Layout -->
                            <div class="block sm:hidden p-3 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-medium truncate text-sm">{{ category.name }}</h4>
                                    <div class="flex items-center gap-1">
                                        <Button @click="startEdit(category)" variant="ghost" size="sm">
                                            <Edit class="h-3 w-3" />
                                        </Button>
                                        <Button @click="deleteCategory(category)" variant="ghost" size="sm" class="text-destructive hover:text-destructive">
                                            <Trash2 class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <label class="text-xs text-muted-foreground">Default Category:</label>
                                    <button
                                        @click="toggleDefault(category)"
                                        :class="[
                                            'relative inline-flex h-4 w-7 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1',
                                            category.is_default 
                                                ? 'bg-blue-600' 
                                                : 'bg-gray-200 dark:bg-gray-700'
                                        ]"
                                        :aria-pressed="category.is_default"
                                        aria-label="Toggle default category"
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-2.5 w-2.5 transform rounded-full bg-white transition-transform duration-200',
                                                category.is_default ? 'translate-x-3.5' : 'translate-x-0.5'
                                            ]"
                                        ></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Desktop Layout -->
                            <div class="hidden sm:flex items-center justify-between p-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium truncate">{{ category.name }}</h4>
                                        <div class="flex items-center gap-2">
                                            <label class="text-sm text-muted-foreground">Default:</label>
                                            <button
                                                @click="toggleDefault(category)"
                                                :class="[
                                                    'relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                                                    category.is_default 
                                                        ? 'bg-blue-600' 
                                                        : 'bg-gray-200 dark:bg-gray-700'
                                                ]"
                                                :aria-pressed="category.is_default"
                                                aria-label="Toggle default category"
                                            >
                                                <span
                                                    :class="[
                                                        'inline-block h-3 w-3 transform rounded-full bg-white transition-transform duration-200',
                                                        category.is_default ? 'translate-x-5' : 'translate-x-1'
                                                    ]"
                                                ></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 ml-4">
                                    <Button @click="startEdit(category)" variant="ghost" size="sm">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button @click="deleteCategory(category)" variant="ghost" size="sm" class="text-destructive hover:text-destructive">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!loading && categories.length === 0" class="flex flex-col items-center justify-center py-8 sm:py-12 text-center">
                        <FolderOpen class="h-12 w-12 sm:h-16 sm:w-16 text-muted-foreground mb-4" />
                        <h3 class="font-medium text-base sm:text-lg mb-2">No categories yet</h3>
                        <p class="text-xs sm:text-sm text-muted-foreground mb-6 px-4">Create your first product category to get started</p>
                        <Button @click="startCreate" class="gap-2 w-full sm:w-auto">
                            <Plus class="h-4 w-4" />
                            Create Category
                        </Button>
                    </div>
                </div>

                <!-- Form View -->
                <div v-if="showForm">
                    <div class="text-center mb-4 sm:mb-6">
                        <h3 class="text-lg sm:text-xl font-semibold mb-2">{{ formTitle }}</h3>
                        <p class="text-xs sm:text-sm text-muted-foreground px-2">
                            {{ isEditing ? 'Update the category details below' : 'Fill in the details to create a new category' }}
                        </p>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4 sm:space-y-6">
                        <!-- Name Field -->
                        <div class="space-y-2">
                            <Label for="category-name" class="text-sm sm:text-base">Category Name</Label>
                            <Input
                                id="category-name"
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Enter category name..."
                                class="text-sm sm:text-base"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <!-- Default Category Setting -->
                        <div class="space-y-3">
                            <Label class="text-sm sm:text-base">Default Settings</Label>
                            <div class="border rounded-lg p-3 sm:p-4 bg-muted/30">
                                <!-- Mobile Layout -->
                                <div class="block sm:hidden space-y-3">
                                    <div>
                                        <h4 class="font-medium text-sm">Default Category</h4>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            Set as the default category for new products
                                        </p>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-muted-foreground">Make Default:</span>
                                        <button
                                            type="button"
                                            @click="form.is_default = !form.is_default"
                                            :class="[
                                                'relative inline-flex h-4 w-7 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1',
                                                form.is_default 
                                                    ? 'bg-blue-600' 
                                                    : 'bg-gray-200 dark:bg-gray-700'
                                            ]"
                                            :aria-pressed="form.is_default"
                                            aria-label="Toggle default category"
                                        >
                                            <span
                                                :class="[
                                                    'inline-block h-2.5 w-2.5 transform rounded-full bg-white transition-transform duration-200',
                                                    form.is_default ? 'translate-x-3.5' : 'translate-x-0.5'
                                                ]"
                                            ></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Desktop Layout -->
                                <div class="hidden sm:flex items-start justify-between">
                                    <div>
                                        <h4 class="font-medium">Default Category</h4>
                                        <p class="text-sm text-muted-foreground mt-1">
                                            Set as the default category for new products
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="form.is_default = !form.is_default"
                                        :class="[
                                            'relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                                            form.is_default 
                                                ? 'bg-blue-600' 
                                                : 'bg-gray-200 dark:bg-gray-700'
                                        ]"
                                        :aria-pressed="form.is_default"
                                        aria-label="Toggle default category"
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-3 w-3 transform rounded-full bg-white transition-transform duration-200',
                                                form.is_default ? 'translate-x-5' : 'translate-x-1'
                                            ]"
                                        ></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 sm:pt-6">
                            <Button type="button" variant="outline" @click="resetForm" class="flex-1 text-sm sm:text-base">
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="formLoading" class="flex-1 text-sm sm:text-base">
                                {{ submitButtonText }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-center sm:justify-end pt-4 sm:pt-6 border-t">
                <Button variant="outline" @click="isOpen = false" class="w-full sm:w-auto text-sm sm:text-base">
                    Close
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>