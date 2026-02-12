<template>
  <div>
    <AddNewSelect
      v-bind="forwardedAttrs"
      :model-value="modelValue"
      :items="items"
      :label="label"
      :add-new-text="addNewText"
      :control="control"
      :show-add-new="showAddNew"
      @update:model-value="value => emit('update:modelValue', value)"
      @add-new="openDialog"
    />

    <v-dialog v-model="dialog" max-width="900px" persistent>
      <v-card>
        <v-card-title>New Product</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.name"
                  label="Product Name *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.sku"
                  label="SKU *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.category_id"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  label="Category"
                  variant="outlined"
                  clearable
                  :loading="loadingCategories"
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
                  :rules="[v => !!v || 'Required']"
                  :loading="loadingUnits"
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
                  :rules="[v => v >= formData.cost_price || 'Must be >= cost price']"
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
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.reorder_level"
                  label="Reorder Level *"
                  type="number"
                  variant="outlined"
                  :rules="[v => v >= 0 || 'Must be positive']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.reorder_quantity"
                  label="Reorder Quantity *"
                  type="number"
                  variant="outlined"
                  :rules="[v => v >= 0 || 'Must be positive']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
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
                  rows="2"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveProduct">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, ref, useAttrs } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
import AddNewSelect from './AddNewSelect.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  modelValue: {
    type: [String, Number, Array, Object, Boolean, null],
    default: null,
  },
  items: {
    type: Array,
    default: () => [],
  },
  label: {
    type: String,
    default: 'Product',
  },
  addNewText: {
    type: String,
    default: 'Add New Product',
  },
  control: {
    type: String,
    default: 'select',
  },
  showAddNew: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:modelValue', 'created']);
const attrs = useAttrs();
const forwardedAttrs = computed(() => {
  const { 'onUpdate:modelValue': _onUpdateModelValue, 'onUpdate:model-value': _onUpdateModelValueKebab, ...rest } = attrs;
  return rest;
});
const { success, handleError } = useToast();

const dialog = ref(false);
const saving = ref(false);
const form = ref(null);
const units = ref([]);
const categories = ref([]);
const loadingUnits = ref(false);
const loadingCategories = ref(false);

const valuationMethods = ['weighted_average', 'fifo'];

const formData = ref({
  name: '',
  sku: '',
  barcode: '',
  category_id: null,
  unit_id: null,
  cost_price: 0,
  selling_price: 0,
  reorder_level: 0,
  reorder_quantity: 0,
  valuation_method: 'weighted_average',
  track_inventory: true,
  is_active: true,
  description: '',
});

const openDialog = async () => {
  dialog.value = true;
  if (!units.value.length) {
    await loadUnits();
  }
  if (!categories.value.length) {
    await loadCategories();
  }
};

const closeDialog = () => {
  dialog.value = false;
  formData.value = {
    name: '',
    sku: '',
    barcode: '',
    category_id: null,
    unit_id: null,
    cost_price: 0,
    selling_price: 0,
    reorder_level: 0,
    reorder_quantity: 0,
    valuation_method: 'weighted_average',
    track_inventory: true,
    is_active: true,
    description: '',
  };
  if (form.value?.resetValidation) form.value.resetValidation();
};

const loadUnits = async () => {
  loadingUnits.value = true;
  try {
    const response = await axios.get('/api/units', { params: { per_page: 1000 } });
    units.value = response.data.data || response.data;
  } catch (error) {
    handleError(error, 'Failed to load units');
  } finally {
    loadingUnits.value = false;
  }
};

const loadCategories = async () => {
  loadingCategories.value = true;
  try {
    const response = await axios.get('/api/categories', { params: { per_page: 1000 } });
    categories.value = response.data.data || response.data;
  } catch (error) {
    handleError(error, 'Failed to load categories');
  } finally {
    loadingCategories.value = false;
  }
};

const saveProduct = async () => {
  const validation = await form.value?.validate();
  if (validation === false || validation?.valid === false) return;

  saving.value = true;
  try {
    const response = await axios.post('/api/products', formData.value);
    const product = response.data?.product || response.data;
    success('Product created successfully');
    emit('created', product);
    emit('update:modelValue', product.id);
    closeDialog();
  } catch (error) {
    handleError(error, 'Failed to create product');
  } finally {
    saving.value = false;
  }
};
</script>
