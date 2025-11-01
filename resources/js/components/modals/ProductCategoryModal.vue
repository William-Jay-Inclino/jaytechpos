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

import { Checkbox } from '@/components/ui/checkbox';
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
        <DialogContent class="max-h-[85vh] max-w-4xl overflow-hidden">
            <DialogHeader class="pb-4">
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <FolderOpen class="h-5 w-5" />
                    Manage Product Categories
                </DialogTitle>
                <DialogDescription class="text-sm text-muted-foreground">
                    Create, edit, and manage your product categories
                </DialogDescription>
            </DialogHeader>

            <div class="flex h-[600px] gap-6">
                <!-- Categories List -->
                <div class="flex-1 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium">Categories</h3>
                        <Button @click="startCreate" size="sm" class="gap-2">
                            <Plus class="h-4 w-4" />
                            New Category
                        </Button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search categories..."
                            class="pl-9 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        />
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading" class="space-y-3">
                        <div v-for="i in 3" :key="i" class="h-16 animate-pulse rounded-lg bg-muted"></div>
                    </div>

                    <!-- Categories List -->
                    <div v-else-if="categories.length > 0" class="space-y-2 overflow-y-auto pr-2" style="max-height: 450px;">
                        <!-- No results message -->
                        <div 
                            v-if="filteredCategories.length === 0 && searchQuery.trim()"
                            class="flex flex-col items-center justify-center rounded-lg border border-dashed py-8"
                        >
                            <Search class="h-8 w-8 text-muted-foreground" />
                            <h4 class="mt-2 font-medium">No categories found</h4>
                            <p class="text-sm text-muted-foreground">Try adjusting your search terms</p>
                        </div>

                        <!-- Categories -->
                        <div
                            v-for="category in filteredCategories"
                            :key="category.id"
                            class="flex items-center justify-between rounded-lg border p-4 hover:bg-muted/50"
                        >
                            <div class="flex-1">
                                <h4 class="font-medium">{{ category.name }}</h4>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-muted-foreground">Default:</span>
                                    <div @click="toggleDefault(category)" class="cursor-pointer">
                                        <Checkbox
                                            v-model="category.is_default"
                                            :disabled="false"
                                            aria-label="Set as default category"
                                        />
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <Button @click="startEdit(category)" variant="ghost" size="sm" class="gap-1">
                                        <Edit class="h-3 w-3" />
                                        Edit
                                    </Button>
                                    <Button @click="deleteCategory(category)" variant="ghost" size="sm" class="gap-1 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/20">
                                        <Trash2 class="h-3 w-3" />
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!loading && categories.length === 0" class="flex h-96 flex-col items-center justify-center rounded-lg border border-dashed">
                        <FolderOpen class="h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-lg font-medium">No categories yet</h3>
                        <p class="mt-2 text-sm text-muted-foreground">Create your first product category to get started</p>
                        <Button @click="startCreate" class="mt-4 gap-2">
                            <Plus class="h-4 w-4" />
                            Create Category
                        </Button>
                    </div>
                </div>

                <!-- Form Panel -->
                <div v-if="showForm" class="w-96 border-l pl-6">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium">{{ formTitle }}</h3>
                            <p class="text-sm text-muted-foreground">
                                {{ isEditing ? 'Update the category details below' : 'Fill in the details to create a new category' }}
                            </p>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <!-- Name -->
                            <div class="space-y-2">
                                <Label for="category-name">Category Name</Label>
                                <Input
                                    id="category-name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                />
                                <InputError :message="errors.name" class="mt-1" />
                            </div>

                            <!-- Default Category -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between rounded-lg border p-4">
                                    <div class="space-y-0.5">
                                        <Label class="text-base">Default Category</Label>
                                        <p class="text-sm text-muted-foreground">
                                            Set as the default category for new products
                                        </p>
                                    </div>
                                    <Checkbox
                                        v-model:checked="form.is_default"
                                        aria-label="Set as default category"
                                    />
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3">
                                <Button type="submit" :disabled="formLoading" class="flex-1">
                                    {{ submitButtonText }}
                                </Button>
                                <Button type="button" variant="outline" @click="resetForm">
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end border-t pt-4">
                <Button variant="outline" @click="isOpen = false">
                    Close
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>