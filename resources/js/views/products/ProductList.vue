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
        <CategorySelect
          v-model="filterCategory"
          :items="categories"
          item-title="name"
          item-value="id"
          label="Category"
          variant="outlined"
          density="compact"
          clearable
          @update:model-value="loadProducts"
          @created="category => categories.push(category)"
        />
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
            @click="manageUnits(item)"
            title="Manage Units"
            color="info"
            v-if="authStore.hasPermission('update_products')"
          >
            <v-icon>mdi-package-variant-closed</v-icon>
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
                <CategorySelect
                  v-model="formData.category_id"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  label="Category *"
                  variant="outlined"
                  :rules="[v => !!v || 'Category is required']"
                  @created="category => categories.push(category)"
                />
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

    <!-- Units Management Dialog -->
    <v-dialog v-model="unitsDialog" max-width="900px" persistent>
      <v-card v-if="selectedProduct">
        <v-card-title class="bg-primary text-white">
          Manage Units - {{ selectedProduct.name }}
          <v-btn icon size="small" @click="unitsDialog = false" class="ml-auto">
            <v-icon color="white">mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text class="mt-4">
          <!-- Units Table -->
          <v-data-table
            :headers="unitHeaders"
            :items="productUnits"
            :loading="loadingUnits"
            class="elevation-1 mb-4"
          >
            <template v-slot:top>
              <v-toolbar flat>
                <v-toolbar-title>Product Units</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="openAddUnitDialog">
                  <v-icon left>mdi-plus</v-icon>
                  Add Unit
                </v-btn>
              </v-toolbar>
            </template>

            <template v-slot:item.unit="{ item }">
              {{ item.unit?.name || item.name }} ({{ item.unit?.short_name || item.short_name }})
            </template>

            <template v-slot:item.conversion_factor="{ item }">
              <v-chip color="info" size="small">
                1 {{ item.unit?.short_name || item.short_name }} = {{ item.conversion_factor }} {{ selectedProduct.unit?.short_name }}
              </v-chip>
            </template>

            <template v-slot:item.selling_price="{ item }">
              ₦{{ formatNumber(item.selling_price || 0) }}
            </template>

            <template v-slot:item.cost_price="{ item }">
              ₦{{ formatNumber(item.cost_price || 0) }}
            </template>

            <template v-slot:item.is_purchase_unit="{ item }">
              <v-icon :color="item.is_purchase_unit ? 'success' : 'grey'">
                {{ item.is_purchase_unit ? 'mdi-check-circle' : 'mdi-close-circle' }}
              </v-icon>
            </template>

            <template v-slot:item.is_sale_unit="{ item }">
              <v-icon :color="item.is_sale_unit ? 'success' : 'grey'">
                {{ item.is_sale_unit ? 'mdi-check-circle' : 'mdi-close-circle' }}
              </v-icon>
            </template>

            <template v-slot:item.is_default="{ item }">
              <v-icon :color="item.is_default ? 'warning' : 'grey'">
                {{ item.is_default ? 'mdi-star' : 'mdi-star-outline' }}
              </v-icon>
            </template>

            <template v-slot:item.actions="{ item }">
              <v-btn icon size="small" color="primary" @click="editUnit(item)" v-if="!item.is_default">
                <v-icon>mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon size="small" color="error" @click="deleteUnit(item)" v-if="!item.is_default">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Add/Edit Unit Dialog -->
    <v-dialog v-model="unitFormDialog" max-width="600px" persistent>
      <v-card>
        <v-card-title>
          {{ editingUnit ? 'Edit' : 'Add' }} Unit
        </v-card-title>
        <v-card-text>
          <v-form ref="unitForm">
            <v-row>
              <v-col cols="12">
                <v-select
                  v-model="unitFormData.unit_id"
                  :items="availableUnits"
                  item-title="name"
                  item-value="id"
                  label="Unit *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                >
                  <template v-slot:item="{ props, item }">
                    <v-list-item v-bind="props">
                      <template v-slot:title>
                        {{ item.raw.name }} ({{ item.raw.short_name }})
                      </template>
                    </v-list-item>
                  </template>
                </v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="unitFormData.conversion_factor"
                  label="Conversion Factor *"
                  type="number"
                  step="0.01"
                  variant="outlined"
                  :rules="[v => v > 0 || 'Must be > 0']"
                  hint="How many base units in this unit"
                  persistent-hint
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-alert type="info" density="compact" v-if="unitFormData.conversion_factor > 0 && selectedProduct">
                  1 unit = {{ unitFormData.conversion_factor }} {{ selectedProduct.unit?.short_name }}
                </v-alert>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="unitFormData.selling_price"
                  label="Selling Price"
                  type="number"
                  step="0.01"
                  variant="outlined"
                  prefix="₦"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="unitFormData.cost_price"
                  label="Cost Price"
                  type="number"
                  step="0.01"
                  variant="outlined"
                  prefix="₦"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-checkbox
                  v-model="unitFormData.is_purchase_unit"
                  label="Purchase Unit"
                  density="compact"
                ></v-checkbox>
              </v-col>
              <v-col cols="12" md="4">
                <v-checkbox
                  v-model="unitFormData.is_sale_unit"
                  label="Sale Unit"
                  density="compact"
                ></v-checkbox>
              </v-col>
              <v-col cols="12" md="4">
                <v-checkbox
                  v-model="unitFormData.is_default"
                  label="Default Unit"
                  density="compact"
                ></v-checkbox>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="unitFormDialog = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" @click="saveUnit" :loading="savingUnit">Save</v-btn>
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
  const confirmed = await confirm(`Are you sure you want to delete ${product.name}?`);
  if (!confirmed) return;
  try {
    await axios.delete(`/api/products/${product.id}`);
    success('Product deleted successfully');
    loadProducts();
  } catch (error) {
    handleError(error, 'Failed to delete product');
  }
};

const viewProduct = (product) => {
  // Navigate to product detail page
  console.log('View product:', product);
};

// Units Management
const unitsDialog = ref(false);
const unitFormDialog = ref(false);
const selectedProduct = ref(null);
const productUnits = ref([]);
const availableUnits = ref([]);
const loadingUnits = ref(false);
const savingUnit = ref(false);
const editingUnit = ref(false);

const unitHeaders = [
  { title: 'Unit', key: 'unit' },
  { title: 'Conversion', key: 'conversion_factor' },
  { title: 'Selling Price', key: 'selling_price' },
  { title: 'Cost Price', key: 'cost_price' },
  { title: 'Purchase', key: 'is_purchase_unit' },
  { title: 'Sale', key: 'is_sale_unit' },
  { title: 'Default', key: 'is_default' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const unitFormData = ref({
  unit_id: null,
  conversion_factor: 1,
  selling_price: 0,
  cost_price: 0,
  is_purchase_unit: true,
  is_sale_unit: true,
  is_default: false,
});

const manageUnits = async (product) => {
  selectedProduct.value = product;
  unitsDialog.value = true;
  await loadProductUnits();
};

const loadProductUnits = async () => {
  if (!selectedProduct.value) return;

  loadingUnits.value = true;
  try {
    const response = await axios.get(`/api/products/${selectedProduct.value.id}/units`);
    productUnits.value = response.data || [];
  } catch (error) {
    handleError(error);
  } finally {
    loadingUnits.value = false;
  }
};

const openAddUnitDialog = () => {
  editingUnit.value = false;
  unitFormData.value = {
    unit_id: null,
    conversion_factor: 1,
    selling_price: 0,
    cost_price: 0,
    is_purchase_unit: true,
    is_sale_unit: true,
    is_default: false,
  };
  unitFormDialog.value = true;
};

const editUnit = (unit) => {
  editingUnit.value = true;
  unitFormData.value = {
    ...unit,
    unit_id: unit.unit_id || unit.unit?.id || unit.id
  };
  unitFormDialog.value = true;
};

const saveUnit = async () => {
  savingUnit.value = true;
  try {
    if (editingUnit.value) {
      await axios.put(`/api/products/${selectedProduct.value.id}/units/${unitFormData.value.id}`, unitFormData.value);
      success('Unit updated successfully');
    } else {
      await axios.post(`/api/products/${selectedProduct.value.id}/units`, unitFormData.value);
      success('Unit added successfully');
    }
    unitFormDialog.value = false;
    await loadProductUnits();
  } catch (error) {
    handleError(error);
  } finally {
    savingUnit.value = false;
  }
};

const deleteUnit = async (unit) => {
  const confirmed = await confirm(`Delete unit ${unit.unit?.name || unit.name}?`);
  if (!confirmed) return;
  try {
    await axios.delete(`/api/products/${selectedProduct.value.id}/units/${unit.id}`);
    success('Unit deleted successfully');
    await loadProductUnits();
  } catch (error) {
    handleError(error);
  }
};

onMounted(async () => {
  // Load categories and units
  const categoriesRes = await axios.get('/api/categories');
  categories.value = categoriesRes.data.data || categoriesRes.data;

  const unitsRes = await axios.get('/api/units');
  units.value = unitsRes.data.data || unitsRes.data;
  availableUnits.value = units.value;

  loadProducts();
});
</script>
