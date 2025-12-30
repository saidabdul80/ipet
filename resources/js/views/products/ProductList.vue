<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Products</h1>
      </v-col>
    </v-row>

    <!-- Filters and Actions -->
    <v-row>
      <v-col cols="12" md="4">
        <v-text-field
          v-model="search"
          label="Search products..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadProducts"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filterCategory"
          :items="categories"
          item-title="name"
          item-value="id"
          label="Category"
          variant="outlined"
          density="compact"
          clearable
          @update:model-value="loadProducts"
        ></v-select>
      </v-col>
      <v-col cols="12" md="2">
        <v-select
          v-model="filterStatus"
          :items="statusOptions"
          label="Status"
          variant="outlined"
          density="compact"
          clearable
          @update:model-value="loadProducts"
        ></v-select>
      </v-col>
      <v-col cols="12" md="3" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" v-if="authStore.hasPermission('create_products')">
          <v-icon left>mdi-plus</v-icon>
          Add Product
        </v-btn>
      </v-col>
    </v-row>

    <!-- Products Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="products"
        :loading="loading"
        :items-per-page="perPage"
        :server-items-length="total"
        @update:options="loadProducts"
      >
        <template v-slot:item.name="{ item }">
          <div>
            <div class="font-weight-bold">{{ item.name }}</div>
            <div class="text-caption text-grey">{{ item.sku }}</div>
          </div>
        </template>

        <template v-slot:item.category="{ item }">
          {{ item.category?.name || 'N/A' }}
        </template>

        <template v-slot:item.selling_price="{ item }">
          ₦{{ formatNumber(item.selling_price) }}
        </template>

        <template v-slot:item.cost_price="{ item }">
          ₦{{ formatNumber(item.cost_price) }}
        </template>

        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn icon size="small" @click="viewProduct(item)" title="View">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="editProduct(item)"
            title="Edit"
            v-if="authStore.hasPermission('update_products')"
          >
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="deleteProduct(item)"
            title="Delete"
            color="error"
            v-if="authStore.hasPermission('delete_products')"
          >
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="800px" persistent>
      <v-card>
        <v-card-title>
          <span class="text-h5">{{ editMode ? 'Edit Product' : 'New Product' }}</span>
        </v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.name"
                  label="Product Name *"
                  variant="outlined"
                  :rules="[v => !!v || 'Name is required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.sku"
                  label="SKU *"
                  variant="outlined"
                  :rules="[v => !!v || 'SKU is required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.category_id"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  label="Category *"
                  variant="outlined"
                  :rules="[v => !!v || 'Category is required']"
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.unit_id"
                  :items="units"
                  item-title="name"
                  item-value="id"
                  label="Unit *"
                  variant="outlined"
                  :rules="[v => !!v || 'Unit is required']"
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.cost_price"
                  label="Cost Price *"
                  type="number"
                  variant="outlined"
                  prefix="₦"
                  :rules="[v => v >= 0 || 'Must be positive']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.selling_price"
                  label="Selling Price *"
                  type="number"
                  variant="outlined"
                  prefix="₦"
                  :rules="[v => v >= 0 || 'Must be positive']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.barcode"
                  label="Barcode"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.valuation_method"
                  :items="valuationMethods"
                  label="Valuation Method *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model.number="formData.reorder_level"
                  label="Reorder Level *"
                  type="number"
                  variant="outlined"
                  :rules="[v => v >= 0 || 'Must be positive']"
                  hint="Minimum stock level before reorder"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model.number="formData.reorder_quantity"
                  label="Reorder Quantity *"
                  type="number"
                  variant="outlined"
                  :rules="[v => v >= 0 || 'Must be positive']"
                  hint="Quantity to order when restocking"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-switch
                  v-model="formData.track_inventory"
                  label="Track Inventory"
                  color="primary"
                ></v-switch>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch
                  v-model="formData.is_active"
                  label="Active"
                  color="primary"
                ></v-switch>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.description"
                  label="Description"
                  variant="outlined"
                  rows="3"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" @click="saveProduct" :loading="saving">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import axios from 'axios';

const authStore = useAuthStore();
const { success, handleError } = useToast();

const loading = ref(false);
const saving = ref(false);
const dialog = ref(false);
const editMode = ref(false);
const search = ref('');
const filterCategory = ref(null);
const filterStatus = ref(null);
const products = ref([]);
const categories = ref([]);
const units = ref([]);
const total = ref(0);
const perPage = ref(10);

const statusOptions = [
  { title: 'Active', value: 'active' },
  { title: 'Inactive', value: 'inactive' },
];

const headers = [
  { title: 'Product', key: 'name', sortable: true },
  { title: 'Category', key: 'category', sortable: false },
  { title: 'Selling Price', key: 'selling_price', sortable: true },
  { title: 'Cost Price', key: 'cost_price', sortable: true },
  { title: 'Status', key: 'is_active', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
];

const valuationMethods = [
  { title: 'Weighted Average', value: 'weighted_average' },
  { title: 'FIFO (First In First Out)', value: 'fifo' },
];

const formData = ref({
  name: '',
  sku: '',
  category_id: null,
  unit_id: null,
  cost_price: 0,
  selling_price: 0,
  barcode: '',
  description: '',
  reorder_level: 10,
  reorder_quantity: 20,
  valuation_method: 'weighted_average',
  track_inventory: true,
  is_active: true,
});

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-NG').format(num);
};

const loadProducts = async () => {
  loading.value = true;
  try {
    const params = {
      search: search.value,
      category_id: filterCategory.value,
      is_active: filterStatus.value === 'active' ? 1 : filterStatus.value === 'inactive' ? 0 : null,
      per_page: perPage.value,
    };
    const response = await axios.get('/api/products', { params });
    products.value = response.data.data || response.data;
    total.value = response.data.total || products.value.length;
  } catch (error) {
    console.error('Failed to load products:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateDialog = () => {
  editMode.value = false;
  formData.value = {
    name: '',
    sku: '',
    category_id: null,
    unit_id: null,
    cost_price: 0,
    selling_price: 0,
    barcode: '',
    description: '',
    reorder_level: 10,
    reorder_quantity: 20,
    valuation_method: 'weighted_average',
    track_inventory: true,
    is_active: true,
  };
  dialog.value = true;
};

const editProduct = (product) => {
  editMode.value = true;
  formData.value = { ...product };
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
};

const saveProduct = async () => {
  saving.value = true;
  try {
    if (editMode.value) {
      await axios.put(`/api/products/${formData.value.id}`, formData.value);
      success('Product updated successfully');
    } else {
      await axios.post('/api/products', formData.value);
      success('Product created successfully');
    }
    closeDialog();
    loadProducts();
  } catch (error) {
    handleError(error, 'Failed to save product');
  } finally {
    saving.value = false;
  }
};

const deleteProduct = async (product) => {
  if (confirm(`Are you sure you want to delete ${product.name}?`)) {
    try {
      await axios.delete(`/api/products/${product.id}`);
      success('Product deleted successfully');
      loadProducts();
    } catch (error) {
      handleError(error, 'Failed to delete product');
    }
  }
};

const viewProduct = (product) => {
  // Navigate to product detail page
  console.log('View product:', product);
};

onMounted(async () => {
  // Load categories and units
  const categoriesRes = await axios.get('/api/categories');
  categories.value = categoriesRes.data.data || categoriesRes.data;

  const unitsRes = await axios.get('/api/units');
  units.value = unitsRes.data.data || unitsRes.data;

  loadProducts();
});
</script>

