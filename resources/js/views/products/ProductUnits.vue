<template>
  <v-container fluid>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Product Units Management</span>
        <v-btn color="primary" @click="openCreateDialog">
          <v-icon left>mdi-plus</v-icon>
          Add Product Unit
        </v-btn>
      </v-card-title>

      <v-card-text>
        <!-- Product Selection -->
        <v-row class="mb-4">
          <v-col cols="12" md="6">
            <v-autocomplete
              v-model="selectedProductId"
              :items="products"
              item-title="name"
              item-value="id"
              label="Select Product"
              variant="outlined"
              density="compact"
              @update:model-value="loadProductUnits"
            >
              <template v-slot:item="{ props, item }">
                <v-list-item v-bind="props">
                  <template v-slot:title>
                    {{ item.raw.name }}
                  </template>
                  <template v-slot:subtitle>
                    SKU: {{ item.raw.sku }} | Base Unit: {{ item.raw.unit?.name }}
                  </template>
                </v-list-item>
              </template>
            </v-autocomplete>
          </v-col>
        </v-row>

        <!-- Product Units Table -->
        <v-data-table
          v-if="selectedProductId"
          :headers="headers"
          :items="productUnits"
          :loading="loading"
          class="elevation-1"
        >
          <template v-slot:item.unit="{ item }">
            {{ item.unit?.name }} ({{ item.unit?.short_name }})
          </template>
          
          <template v-slot:item.conversion_factor="{ item }">
            <v-chip color="info" size="small">
              1 {{ item.unit?.short_name }} = {{ item.conversion_factor }} {{ baseUnit }}
            </v-chip>
          </template>

          <template v-slot:item.selling_price="{ item }">
            ₦{{ formatNumber(item.selling_price) }}
          </template>

          <template v-slot:item.cost_price="{ item }">
            ₦{{ formatNumber(item.cost_price) }}
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
            <v-btn icon size="small" color="primary" @click="editUnit(item)">
              <v-icon>mdi-pencil</v-icon>
            </v-btn>
            <v-btn icon size="small" color="error" @click="deleteUnit(item)">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
          </template>
        </v-data-table>

        <v-alert v-else type="info" variant="tonal" class="mt-4">
          Please select a product to manage its units
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="600px" persistent>
      <v-card>
        <v-card-title>
          {{ editMode ? 'Edit' : 'Add' }} Product Unit
        </v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12">
                <v-select
                  v-model="formData.unit_id"
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
                  v-model.number="formData.conversion_factor"
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
                <v-alert type="info" density="compact" v-if="formData.conversion_factor > 0">
                  1 unit = {{ formData.conversion_factor }} {{ baseUnit }}
                </v-alert>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.selling_price"
                  label="Selling Price"
                  type="number"
                  step="0.01"
                  variant="outlined"
                  prefix="₦"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.cost_price"
                  label="Cost Price"
                  type="number"
                  step="0.01"
                  variant="outlined"
                  prefix="₦"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-checkbox
                  v-model="formData.is_purchase_unit"
                  label="Purchase Unit"
                  density="compact"
                ></v-checkbox>
              </v-col>
              <v-col cols="12" md="4">
                <v-checkbox
                  v-model="formData.is_sale_unit"
                  label="Sale Unit"
                  density="compact"
                ></v-checkbox>
              </v-col>
              <v-col cols="12" md="4">
                <v-checkbox
                  v-model="formData.is_default"
                  label="Default Unit"
                  density="compact"
                ></v-checkbox>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" @click="saveUnit">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const products = ref([]);
const selectedProductId = ref(null);
const productUnits = ref([]);
const availableUnits = ref([]);
const loading = ref(false);
const dialog = ref(false);
const editMode = ref(false);
const form = ref(null);

const formData = ref({
  unit_id: null,
  conversion_factor: 1,
  selling_price: 0,
  cost_price: 0,
  is_purchase_unit: true,
  is_sale_unit: true,
  is_default: false,
});

const headers = [
  { title: 'Unit', key: 'unit' },
  { title: 'Conversion', key: 'conversion_factor' },
  { title: 'Selling Price', key: 'selling_price' },
  { title: 'Cost Price', key: 'cost_price' },
  { title: 'Purchase', key: 'is_purchase_unit' },
  { title: 'Sale', key: 'is_sale_unit' },
  { title: 'Default', key: 'is_default' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const baseUnit = computed(() => {
  if (!selectedProductId.value) return 'units';
  const product = products.value.find(p => p.id === selectedProductId.value);
  return product?.unit?.short_name || 'units';
});

const formatNumber = (num) => new Intl.NumberFormat('en-NG').format(num);

const loadProducts = async () => {
  try {
    const response = await axios.get('/api/products', { params: { per_page: 1000 } });
    products.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load products:', error);
  }
};

const loadUnits = async () => {
  try {
    const response = await axios.get('/api/units');
    availableUnits.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load units:', error);
  }
};

const loadProductUnits = async () => {
  if (!selectedProductId.value) return;

  loading.value = true;
  try {
    const response = await axios.get(`/api/products/${selectedProductId.value}/units`);
    productUnits.value = response.data || [];
  } catch (error) {
    console.error('Failed to load product units:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateDialog = () => {
  if (!selectedProductId.value) {
    alert('Please select a product first');
    return;
  }

  editMode.value = false;
  formData.value = {
    unit_id: null,
    conversion_factor: 1,
    selling_price: 0,
    cost_price: 0,
    is_purchase_unit: true,
    is_sale_unit: true,
    is_default: false,
  };
  dialog.value = true;
};

const editUnit = (unit) => {
  editMode.value = true;
  formData.value = { ...unit, unit_id: unit.unit.id };
  dialog.value = true;
};

const saveUnit = async () => {
  try {
    if (editMode.value) {
      await axios.put(`/api/products/${selectedProductId.value}/units/${formData.value.id}`, formData.value);
      alert('Product unit updated successfully');
    } else {
      await axios.post(`/api/products/${selectedProductId.value}/units`, formData.value);
      alert('Product unit added successfully');
    }
    dialog.value = false;
    loadProductUnits();
  } catch (error) {
    console.error('Failed to save product unit:', error);
    alert(error.response?.data?.message || 'Failed to save product unit');
  }
};

const deleteUnit = async (unit) => {
  if (confirm(`Delete unit ${unit.unit.name}?`)) {
    try {
      await axios.delete(`/api/products/${selectedProductId.value}/units/${unit.id}`);
      alert('Product unit deleted successfully');
      loadProductUnits();
    } catch (error) {
      console.error('Failed to delete product unit:', error);
      alert(error.response?.data?.message || 'Failed to delete product unit');
    }
  }
};

onMounted(async () => {
  await Promise.all([loadProducts(), loadUnits()]);
});
</script>
