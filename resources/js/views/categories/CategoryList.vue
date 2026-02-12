<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Product Categories</h1>
      </v-col>
    </v-row>

    <!-- Filters and Actions -->
    <v-row>
      <v-col cols="12" md="8">
        <v-text-field
          v-model="search"
          label="Search categories..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadCategories"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="4" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" v-if="authStore.hasPermission('create_product_categories')">
          <v-icon left>mdi-plus</v-icon>
          Add Category
        </v-btn>
      </v-col>
    </v-row>

    <!-- Categories Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="categories"
        :loading="loading"
      >
        <template v-slot:item.name="{ item }">
          <div>
            <div class="font-weight-bold">{{ item.name }}</div>
            <div class="text-caption text-grey" v-if="item.description">{{ item.description }}</div>
          </div>
        </template>

        <template v-slot:item.parent="{ item }">
          {{ item.parent?.name || 'Root' }}
        </template>

        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn
            icon
            size="small"
            @click="editCategory(item)"
            title="Edit"
            v-if="authStore.hasPermission('update_product_categories')"
          >
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="deleteCategory(item)"
            title="Delete"
            color="error"
            v-if="authStore.hasPermission('delete_product_categories')"
          >
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="600px" persistent>
      <v-card>
        <v-card-title>{{ editMode ? 'Edit Category' : 'New Category' }}</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.name"
                  label="Category Name *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.code"
                  label="Category Code *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                  hint="Unique code for this category"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <CategorySelect
                  v-model="formData.parent_id"
                  :items="parentCategories"
                  item-title="name"
                  item-value="id"
                  label="Parent Category"
                  variant="outlined"
                  clearable
                  @created="category => categories.push(category)"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.description"
                  label="Description"
                  variant="outlined"
                  rows="2"
                ></v-textarea>
              </v-col>
              <v-col cols="12">
                <v-switch
                  v-model="formData.is_active"
                  label="Active"
                  color="primary"
                ></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" @click="saveCategory" :loading="saving">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import axios from 'axios';
import CategorySelect from '@/components/selects/CategorySelect.vue';
import { useDialog } from '@/composables/useDialog';

const authStore = useAuthStore();
const { success, handleError } = useToast();
const { confirm } = useDialog();
const loading = ref(false);
const saving = ref(false);
const dialog = ref(false);
const editMode = ref(false);
const search = ref('');
const categories = ref([]);

const headers = [
  { title: 'Category', key: 'name' },
  { title: 'Parent', key: 'parent' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formData = ref({
  name: '',
  code: '',
  parent_id: null,
  description: '',
  is_active: true,
});

const parentCategories = computed(() => {
  return categories.value.filter(cat => !cat.parent_id);
});

const loadCategories = async () => {
  loading.value = true;
  try {
    const params = { search: search.value };
    const response = await axios.get('/api/categories', { params });
    categories.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load categories:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateDialog = () => {
  editMode.value = false;
  formData.value = {
    name: '',
    code: '',
    parent_id: null,
    description: '',
    is_active: true,
  };
  dialog.value = true;
};

const editCategory = (category) => {
  editMode.value = true;
  formData.value = { ...category };
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
};

const saveCategory = async () => {
  saving.value = true;
  try {
    if (editMode.value) {
      await axios.put(`/api/categories/${formData.value.id}`, formData.value);
      success('Category updated successfully');
    } else {
      await axios.post('/api/categories', formData.value);
      success('Category created successfully');
    }
    closeDialog();
    loadCategories();
  } catch (error) {
    handleError(error, 'Failed to save category');
  } finally {
    saving.value = false;
  }
};

const deleteCategory = async (category) => {
  const confirmed = await confirm(`Are you sure you want to delete ${category.name}?`);
  if (!confirmed) return;
  try {
    await axios.delete(`/api/categories/${category.id}`);
    success('Category deleted successfully');
    loadCategories();
  } catch (error) {
    handleError(error, 'Failed to delete category');
  }
};

onMounted(() => {
  loadCategories();
});
</script>
