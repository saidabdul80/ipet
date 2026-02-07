<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Purchase Orders</h1>
      </v-col>
    </v-row>

    <!-- Filters and Actions -->
    <v-row>
      <v-col cols="12" md="4">
        <v-text-field
          v-model="filters.search"
          label="Search (PO #, Supplier)"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadPurchaseOrders"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filters.status"
          :items="statusOptions"
          label="Status"
          variant="outlined"
          density="compact"
          clearable
          @update:model-value="loadPurchaseOrders"
        ></v-select>
      </v-col>
      <v-col cols="12" md="2">
        <v-select
          v-model="filters.store_id"
          :items="stores"
          item-title="name"
          item-value="id"
          label="Store"
          variant="outlined"
          density="compact"
          clearable
          @update:model-value="loadPurchaseOrders"
        ></v-select>
      </v-col>
      <v-col cols="12" md="3" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" v-if="authStore.hasPermission('create_purchase_orders')">
          <v-icon left>mdi-plus</v-icon>
          New PO
        </v-btn>
      </v-col>
    </v-row>

    <!-- Purchase Orders Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="purchaseOrders"
        :loading="loading"
      >
        <template v-slot:item.po_number="{ item }">
          <a @click="viewPO(item)" class="text-primary cursor-pointer">
            {{ item.po_number }}
          </a>
        </template>

        <template v-slot:item.supplier="{ item }">
          {{ item.supplier?.name }}
        </template>

        <template v-slot:item.total_amount="{ item }">
          ₦{{ formatNumber(item.total_amount) }}
        </template>

        <template v-slot:item.status="{ item }">
          <v-chip :color="getStatusColor(item.status)" size="small">
            {{ item.status }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn icon size="small" @click="viewPO(item)" title="View">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="approvePO(item)"
            title="Approve"
            color="success"
            v-if="item.status === 'pending' && authStore.hasPermission('approve_purchase_orders')"
          >
            <v-icon>mdi-check</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="receiveGoods(item)"
            title="Receive Goods"
            color="info"
            v-if="item.status === 'approved' && authStore.hasPermission('receive_goods')"
          >
            <v-icon>mdi-package-down</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- PO Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="900px">
      <v-card v-if="selectedPO">
        <v-card-title class="bg-primary text-white">
          Purchase Order - {{ selectedPO.po_number }}
          <v-chip :color="getStatusColor(selectedPO.status)" class="ml-2">
            {{ selectedPO.status }}
          </v-chip>
        </v-card-title>
        <v-card-text class="mt-4">
          <v-row>
            <v-col cols="6">
              <strong>Date:</strong> {{ selectedPO.order_date }}
            </v-col>
            <v-col cols="6">
              <strong>Supplier:</strong> {{ selectedPO.supplier?.name }}
            </v-col>
            <v-col cols="6">
              <strong>Store:</strong> {{ selectedPO.store?.name }}
            </v-col>
            <v-col cols="6">
              <strong>Expected Date:</strong> {{ selectedPO.expected_delivery_date || 'N/A' }}
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <h3 class="mb-2">Items</h3>
          <v-table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Quantity Ordered</th>
                <th>Quantity Received</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in selectedPO.items" :key="item.id">
                <td>{{ item.product?.name }}</td>
                <td>
                  {{ item.quantity }} {{ item.unit?.short_name || item.product?.unit?.short_name || '' }}
                  <span v-if="item.base_quantity && item.base_quantity !== item.quantity" class="text-caption text-grey ml-1">
                    ({{ item.base_quantity }} {{ item.product?.unit?.short_name }})
                  </span>
                </td>
                <td>
                  {{ item.received_quantity || 0 }} {{ item.unit?.short_name || item.product?.unit?.short_name || '' }}
                </td>
                <td>₦{{ formatNumber(item.unit_cost) }}</td>
                <td>₦{{ formatNumber(item.line_total) }}</td>
              </tr>
            </tbody>
          </v-table>

          <v-divider class="my-4"></v-divider>

          <v-row>
            <v-col cols="6" offset="6">
              <div class="d-flex justify-space-between mb-2">
                <span>Subtotal:</span>
                <span>₦{{ formatNumber(selectedPO.subtotal || 0) }}</span>
              </div>
              <div class="d-flex justify-space-between mb-2">
                <span>Tax:</span>
                <span>₦{{ formatNumber(selectedPO.tax_amount || 0) }}</span>
              </div>
              <v-divider class="my-2"></v-divider>
              <div class="d-flex justify-space-between text-h6">
                <span>Total:</span>
                <span class="text-primary">₦{{ formatNumber(selectedPO.total_amount || 0) }}</span>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-btn
            color="primary"
            variant="outlined"
            @click="printPO(selectedPO)"
          >
            <v-icon left>mdi-printer</v-icon>
            Print
          </v-btn>
          <v-btn
            color="warning"
            @click="editPO(selectedPO)"
            v-if="['draft', 'pending'].includes(selectedPO.status) && authStore.hasPermission('edit_purchase_orders')"
          >
            <v-icon left>mdi-pencil</v-icon>
            Edit
          </v-btn>
          <v-btn
            color="success"
            @click="approvePO(selectedPO)"
            v-if="['draft', 'pending'].includes(selectedPO.status) && authStore.hasPermission('approve_purchase_orders')"
          >
            <v-icon left>mdi-check</v-icon>
            Approve
          </v-btn>
          <v-btn
            color="info"
            @click="receiveGoods(selectedPO)"
            v-if="selectedPO.status === 'approved' && authStore.hasPermission('receive_purchase_orders')"
          >
            <v-icon left>mdi-package-down</v-icon>
            Receive Goods
          </v-btn>
          <v-btn
            color="error"
            @click="cancelPO(selectedPO)"
            v-if="!['received', 'completed', 'cancelled'].includes(selectedPO.status) && authStore.hasPermission('delete_purchase_orders')"
          >
            <v-icon left>mdi-cancel</v-icon>
            Cancel
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn @click="detailDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Create PO Dialog -->
    <v-dialog v-model="createDialog" max-width="1000px" persistent>
      <v-card>
        <v-card-title>New Purchase Order</v-card-title>
        <v-card-text>
          <v-form ref="poForm">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="poData.supplier_id"
                  :items="suppliers"
                  item-title="name"
                  item-value="id"
                  label="Supplier *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="poData.store_id"
                  :items="stores"
                  item-title="name"
                  item-value="id"
                  label="Store *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="poData.order_date"
                  label="Order Date *"
                  type="date"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="poData.expected_date"
                  label="Expected Delivery Date"
                  type="date"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="poData.notes"
                  label="Notes"
                  variant="outlined"
                  rows="2"
                ></v-textarea>
              </v-col>
            </v-row>

            <!-- Items Section -->
            <v-divider class="my-4"></v-divider>
            <div class="d-flex justify-space-between align-center mb-2">
              <h3>Items *</h3>
              <v-btn size="small" color="primary" @click="addItem">
                <v-icon left>mdi-plus</v-icon>
                Add Item
              </v-btn>
            </div>

            <v-alert v-if="poData.items.length === 0" type="warning" variant="tonal" class="mb-4">
              Please add at least one item to the purchase order
            </v-alert>

            <v-row v-for="(item, index) in poData.items" :key="index" class="mb-2">
              <v-col cols="12" md="4">
                <v-autocomplete
                  v-model="item.product_id"
                  :items="products"
                  item-title="name"
                  item-value="id"
                  label="Product *"
                  variant="outlined"
                  density="compact"
                  :rules="[v => !!v || 'Required']"
                  @update:model-value="onProductChange(item, index)"
                ></v-autocomplete>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model.number="item.quantity"
                  label="Quantity *"
                  type="number"
                  variant="outlined"
                  density="compact"
                  :rules="[v => v > 0 || 'Must be > 0']"
                  @input="calculateConversion(item)"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="2">
                <v-select
                  v-model="item.unit_id"
                  :items="item.available_units || []"
                  item-title="name"
                  item-value="id"
                  label="Unit *"
                  variant="outlined"
                  density="compact"
                  :disabled="!item.product_id"
                  @update:model-value="onUnitChange(item)"
                >
                  <template v-slot:item="{ props, item: unitItem }">
                    <v-list-item v-bind="props">
                      <template v-slot:title>
                        {{ unitItem.raw.name }}
                        <span v-if="unitItem.raw.conversion_factor > 1" class="text-caption text-grey">
                          ({{ unitItem.raw.conversion_factor }} {{ getProductBaseUnit(item.product_id) }})
                        </span>
                      </template>
                    </v-list-item>
                  </template>
                </v-select>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model.number="item.unit_cost"
                  label="Unit Cost *"
                  type="number"
                  variant="outlined"
                  density="compact"
                  :rules="[v => v >= 0 || 'Must be >= 0']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="1" class="d-flex align-center">
                <v-btn icon size="small" color="error" @click="removeItem(index)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </v-col>
              <v-col v-if="item.conversion_info" cols="12">
                <v-alert type="info" density="compact" variant="tonal">
                  {{ item.conversion_info }}
                </v-alert>
              </v-col>
            </v-row>

            <div v-if="poData.items.length > 0" class="text-right mt-4">
              <h3>Total: ₦{{ formatNumber(calculateTotal()) }}</h3>
            </div>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="createDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="createPO" :loading="saving">Create PO</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Receive Goods Dialog -->
    <v-dialog v-model="receiveDialog" max-width="900px" persistent>
      <v-card v-if="selectedPO">
        <v-card-title>Receive Goods - {{ selectedPO.po_number }}</v-card-title>
        <v-card-text>
          <v-form ref="receiveForm">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="receiveData.received_date"
                  label="Received Date *"
                  type="date"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="receiveData.notes"
                  label="Notes"
                  variant="outlined"
                  rows="2"
                ></v-textarea>
              </v-col>
            </v-row>

            <!-- Items to Receive -->
            <v-divider class="my-4"></v-divider>
            <h3 class="mb-3">Items to Receive</h3>
            <v-table>
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Ordered</th>
                  <th>Already Received</th>
                  <th>Receive Now *</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in receiveData.items" :key="index">
                  <td>{{ item.product_name }}</td>
                  <td>{{ item.quantity_ordered }}</td>
                  <td>{{ item.already_received }}</td>
                  <td>
                    <v-text-field
                      v-model.number="item.quantity_received"
                      type="number"
                      variant="outlined"
                      density="compact"
                      :min="0"
                      :max="item.quantity_ordered - item.already_received"
                      hide-details
                    ></v-text-field>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="receiveDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="submitReceive" :loading="receiving">Receive Goods</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Edit PO Dialog -->
    <v-dialog v-model="editDialog" max-width="1000px" persistent>
      <v-card>
        <v-card-title>Edit Purchase Order - {{ editData.po_number }}</v-card-title>
        <v-card-text>
          <v-form ref="editForm">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editData.supplier_id"
                  :items="suppliers"
                  item-title="name"
                  item-value="id"
                  label="Supplier *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="editData.store_id"
                  :items="stores"
                  item-title="name"
                  item-value="id"
                  label="Store *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editData.order_date"
                  label="Order Date *"
                  type="date"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editData.expected_date"
                  label="Expected Delivery Date"
                  type="date"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="editData.notes"
                  label="Notes"
                  variant="outlined"
                  rows="2"
                ></v-textarea>
              </v-col>
            </v-row>

            <!-- Items Section -->
            <v-divider class="my-4"></v-divider>
            <div class="d-flex justify-space-between align-center mb-2">
              <h3>Items *</h3>
              <v-btn size="small" color="primary" @click="addEditItem">
                <v-icon left>mdi-plus</v-icon>
                Add Item
              </v-btn>
            </div>

            <v-alert v-if="editData.items.length === 0" type="warning" variant="tonal" class="mb-4">
              Please add at least one item to the purchase order
            </v-alert>

            <v-row v-for="(item, index) in editData.items" :key="index" class="mb-2">
              <v-col cols="12" md="4">
                <v-autocomplete
                  v-model="item.product_id"
                  :items="products"
                  item-title="name"
                  item-value="id"
                  label="Product *"
                  variant="outlined"
                  density="compact"
                  :rules="[v => !!v || 'Required']"
                  @update:model-value="onProductChange(item, index)"
                ></v-autocomplete>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model.number="item.quantity"
                  label="Quantity *"
                  type="number"
                  variant="outlined"
                  density="compact"
                  :rules="[v => v > 0 || 'Must be > 0']"
                  @input="calculateConversion(item)"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="2">
                <v-select
                  v-model="item.unit_id"
                  :items="item.available_units || []"
                  item-title="name"
                  item-value="id"
                  label="Unit *"
                  variant="outlined"
                  density="compact"
                  :disabled="!item.product_id"
                  @update:model-value="onUnitChange(item)"
                >
                  <template v-slot:item="{ props, item: unitItem }">
                    <v-list-item v-bind="props">
                      <template v-slot:title>
                        {{ unitItem.raw.name }}
                        <span v-if="unitItem.raw.conversion_factor > 1" class="text-caption text-grey">
                          ({{ unitItem.raw.conversion_factor }} {{ getProductBaseUnit(item.product_id) }})
                        </span>
                      </template>
                    </v-list-item>
                  </template>
                </v-select>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model.number="item.unit_cost"
                  label="Unit Cost *"
                  type="number"
                  variant="outlined"
                  density="compact"
                  :rules="[v => v >= 0 || 'Must be >= 0']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="1" class="d-flex align-center">
                <v-btn icon size="small" color="error" @click="removeEditItem(index)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </v-col>
              <v-col v-if="item.conversion_info" cols="12">
                <v-alert type="info" density="compact" variant="tonal">
                  {{ item.conversion_info }}
                </v-alert>
              </v-col>
            </v-row>

            <div v-if="editData.items.length > 0" class="text-right mt-4">
              <h3>Total: ₦{{ formatNumber(calculateEditTotal()) }}</h3>
            </div>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="updatePO" :loading="saving">Update PO</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const authStore = useAuthStore();
const loading = ref(false);
const saving = ref(false);
const receiving = ref(false);
const detailDialog = ref(false);
const createDialog = ref(false);
const editDialog = ref(false);
const receiveDialog = ref(false);
const purchaseOrders = ref([]);
const stores = ref([]);
const suppliers = ref([]);
const products = ref([]);
const selectedPO = ref(null);

const filters = ref({
  search: '',
  status: null,
  store_id: null,
});

const poData = ref({
  supplier_id: null,
  store_id: null,
  expected_date: '',
  notes: '',
});

const editData = ref({
  id: null,
  po_number: '',
  supplier_id: null,
  store_id: null,
  order_date: '',
  expected_date: '',
  notes: '',
  items: [],
});

const receiveData = ref({
  grn_number: '',
  received_date: new Date().toISOString().split('T')[0],
  notes: '',
});

const statusOptions = [
  { title: 'Pending', value: 'pending' },
  { title: 'Approved', value: 'approved' },
  { title: 'Received', value: 'received' },
  { title: 'Cancelled', value: 'cancelled' },
];

const headers = [
  { title: 'PO #', key: 'po_number' },
  { title: 'Date', key: 'po_date' },
  { title: 'Supplier', key: 'supplier' },
  { title: 'Total', key: 'total_amount' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formatNumber = (num) => new Intl.NumberFormat('en-NG').format(num);

const getStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    approved: 'info',
    received: 'success',
    cancelled: 'error',
  };
  return colors[status] || 'default';
};

const loadPurchaseOrders = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/purchase-orders', { params: filters.value });
    purchaseOrders.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load purchase orders:', error);
  } finally {
    loading.value = false;
  }
};

const viewPO = async (po) => {
  try {
    const response = await axios.get(`/api/purchase-orders/${po.id}`);
    selectedPO.value = response.data;
    detailDialog.value = true;
  } catch (error) {
    console.error('Failed to load PO details:', error);
  }
};

const addItem = () => {
  poData.value.items.push({
    product_id: null,
    quantity: 1,
    unit_id: null,
    unit_cost: 0,
    available_units: [],
    conversion_info: null,
  });
};

// Load available units for a product
const onProductChange = async (item, index) => {
  if (!item.product_id) {
    item.available_units = [];
    item.unit_id = null;
    return;
  }

  try {
    const response = await axios.get(`/api/products/${item.product_id}/units`, {
      params: { type: 'purchase' }
    });
    item.available_units = response.data || [];

    // Auto-select the default unit or first unit
    const defaultUnit = item.available_units.find(u => u.is_default);
    item.unit_id = defaultUnit ? defaultUnit.id : (item.available_units[0]?.id || null);

    // Load unit price if available
    if (item.unit_id) {
      await onUnitChange(item);
    }
  } catch (error) {
    console.error('Failed to load product units:', error);
    // Fallback: use product's base unit
    const product = products.value.find(p => p.id === item.product_id);
    if (product && product.unit) {
      item.available_units = [{
        id: product.unit.id,
        name: product.unit.name,
        short_name: product.unit.short_name,
        conversion_factor: 1,
        is_default: true,
      }];
      item.unit_id = product.unit.id;
    }
  }
};

// Handle unit change
const onUnitChange = async (item) => {
  if (!item.unit_id || !item.product_id) return;

  try {
    console.log('Unit changed:', { product_id: item.product_id, unit_id: item.unit_id });

    // Get unit price
    const response = await axios.post(`/api/products/${item.product_id}/units/price`, {
      unit_id: item.unit_id,
      price_type: 'cost'
    });

    console.log('Unit price response:', response.data);

    if (response.data.price) {
      item.unit_cost = response.data.price;
      console.log('Updated unit_cost to:', item.unit_cost);
    } else {
      console.warn('No price returned from API');
    }

    // Calculate conversion info
    calculateConversion(item);
  } catch (error) {
    console.error('Failed to get unit price:', error);
    console.error('Error details:', error.response?.data);
  }
};

// Calculate and display conversion
const calculateConversion = (item) => {
  if (!item.quantity || !item.unit_id || !item.available_units) {
    item.conversion_info = null;
    return;
  }

  const selectedUnit = item.available_units.find(u => u.id === item.unit_id);
  if (!selectedUnit || selectedUnit.conversion_factor === 1) {
    item.conversion_info = null;
    return;
  }

  const baseQuantity = item.quantity * selectedUnit.conversion_factor;
  const baseUnit = getProductBaseUnit(item.product_id);

  item.conversion_info = `${item.quantity} ${selectedUnit.short_name} = ${baseQuantity} ${baseUnit}`;
};

// Get product's base unit name
const getProductBaseUnit = (productId) => {
  const product = products.value.find(p => p.id === productId);
  return product?.unit?.short_name || 'units';
};

const removeItem = (index) => {
  poData.value.items.splice(index, 1);
};

const calculateTotal = () => {
  return poData.value.items.reduce((sum, item) => {
    return sum + (item.quantity * item.unit_cost);
  }, 0);
};



const openCreateDialog = () => {
  poData.value = {
    supplier_id: null,
    store_id: null,
    order_date: new Date().toISOString().split('T')[0], // Today's date
    expected_date: '',
    notes: '',
    items: [], // Initialize items array
  };
  createDialog.value = true;
};

const createPO = async () => {
  // Validate that we have at least one item
  if (!poData.value.items || poData.value.items.length === 0) {
    alert('Please add at least one item to the purchase order');
    return;
  }

  saving.value = true;
  try {
    const payload = {
      supplier_id: poData.value.supplier_id,
      store_id: poData.value.store_id,
      order_date: poData.value.order_date,
      expected_delivery_date: poData.value.expected_date || null,
      notes: poData.value.notes,
      items: poData.value.items,
    };

    await axios.post('/api/purchase-orders', payload);
    createDialog.value = false;
    loadPurchaseOrders();
    alert('Purchase order created successfully');
  } catch (error) {
    console.error('Failed to create PO:', error);
    const errorMsg = error.response?.data?.message || 'Failed to create PO';
    alert(errorMsg);
  } finally {
    saving.value = false;
  }
};

const editPO = (po) => {
  editData.value = {
    id: po.id,
    po_number: po.po_number,
    supplier_id: po.supplier_id,
    store_id: po.store_id,
    order_date: po.order_date,
    expected_date: po.expected_delivery_date || '',
    notes: po.notes || '',
    items: po.items.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity,
      unit_cost: item.unit_cost,
    })),
  };
  detailDialog.value = false;
  editDialog.value = true;
};

const addEditItem = () => {
  editData.value.items.push({
    product_id: null,
    quantity: 1,
    unit_id: null,
    unit_cost: 0,
    available_units: [],
    conversion_info: null,
  });
};

const removeEditItem = (index) => {
  editData.value.items.splice(index, 1);
};

const calculateEditTotal = () => {
  return editData.value.items.reduce((sum, item) => {
    return sum + (item.quantity * item.unit_cost);
  }, 0);
};

const updatePO = async () => {
  if (!editData.value.items || editData.value.items.length === 0) {
    alert('Please add at least one item to the purchase order');
    return;
  }

  saving.value = true;
  try {
    const payload = {
      supplier_id: editData.value.supplier_id,
      store_id: editData.value.store_id,
      order_date: editData.value.order_date,
      expected_delivery_date: editData.value.expected_date || null,
      notes: editData.value.notes,
      items: editData.value.items,
    };

    await axios.put(`/api/purchase-orders/${editData.value.id}`, payload);
    editDialog.value = false;
    loadPurchaseOrders();
    alert('Purchase order updated successfully');
  } catch (error) {
    console.error('Failed to update PO:', error);
    const errorMsg = error.response?.data?.message || 'Failed to update PO';
    alert(errorMsg);
  } finally {
    saving.value = false;
  }
};

const cancelPO = async (po) => {
  if (confirm(`Are you sure you want to cancel purchase order ${po.po_number}?`)) {
    try {
      await axios.post(`/api/purchase-orders/${po.id}/cancel`, {});
      loadPurchaseOrders();
      if (detailDialog.value) detailDialog.value = false;
      alert('Purchase order cancelled successfully');
    } catch (error) {
      console.error('Failed to cancel PO:', error);
      const errorMsg = error.response?.data?.message || 'Failed to cancel PO';
      alert(errorMsg);
    }
  }
};

const printPO = (po) => {
  // Create a printable version
  const printWindow = window.open('', '_blank');
  printWindow.document.write(`
    <html>
      <head>
        <title>Purchase Order - ${po.po_number}</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 20px; }
          h1 { color: #333; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background-color: #f2f2f2; }
          .header { margin-bottom: 20px; }
          .total { text-align: right; font-weight: bold; margin-top: 20px; }
          @media print {
            button { display: none; }
          }
        </style>
      </head>
      <body>
        <div class="header">
          <h1>Purchase Order</h1>
          <p><strong>PO Number:</strong> ${po.po_number}</p>
          <p><strong>Date:</strong> ${po.order_date}</p>
          <p><strong>Supplier:</strong> ${po.supplier?.name || 'N/A'}</p>
          <p><strong>Store:</strong> ${po.store?.name || 'N/A'}</p>
          <p><strong>Expected Delivery:</strong> ${po.expected_delivery_date || 'N/A'}</p>
          <p><strong>Status:</strong> ${po.status}</p>
        </div>

        <table>
          <thead>
            <tr>
              <th>Product</th>
              <th>Quantity</th>
              <th>Unit Cost</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            ${po.items.map(item => `
              <tr>
                <td>${item.product?.name || 'N/A'}</td>
                <td>${item.quantity}</td>
                <td>₦${new Intl.NumberFormat('en-NG').format(item.unit_cost)}</td>
                <td>₦${new Intl.NumberFormat('en-NG').format(item.quantity * item.unit_cost)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>

        <div class="total">
          <p>Subtotal: ₦${new Intl.NumberFormat('en-NG').format(po.subtotal)}</p>
          <p>Tax: ₦${new Intl.NumberFormat('en-NG').format(po.tax_amount || 0)}</p>
          <p style="font-size: 1.2em;">Total: ₦${new Intl.NumberFormat('en-NG').format(po.total_amount)}</p>
        </div>

        ${po.notes ? `<p><strong>Notes:</strong> ${po.notes}</p>` : ''}

        <button onclick="window.print()" style="margin-top: 20px; padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer;">Print</button>
      </body>
    </html>
  `);
  printWindow.document.close();
};

const approvePO = async (po) => {
  if (confirm(`Approve purchase order ${po.po_number}?`)) {
    try {
      await axios.post(`/api/purchase-orders/${po.id}/approve`, {});
      loadPurchaseOrders();
      if (detailDialog.value) detailDialog.value = false;
      alert('Purchase order approved successfully');
    } catch (error) {
      console.error('Failed to approve PO:', error);
      const errorMsg = error.response?.data?.message || 'Failed to approve PO';
      alert(errorMsg);
    }
  }
};

const receiveGoods = (po) => {
  selectedPO.value = po;
  receiveData.value = {
    received_date: new Date().toISOString().split('T')[0],
    notes: '',
    items: po.items.map(item => ({
      purchase_order_item_id: item.id,
      product_name: item.product?.name || 'Unknown Product',
      quantity_ordered: item.quantity,
      already_received: item.received_quantity || 0,
      quantity_received: item.quantity - (item.received_quantity || 0), // Default to remaining quantity
    })),
  };
  receiveDialog.value = true;
};

const submitReceive = async () => {
  receiving.value = true;
  try {
    // Prepare payload with only the required fields
    const payload = {
      received_date: receiveData.value.received_date,
      notes: receiveData.value.notes,
      items: receiveData.value.items.map(item => ({
        purchase_order_item_id: item.purchase_order_item_id,
        quantity_received: item.quantity_received,
      })),
    };

    await axios.post(`/api/purchase-orders/${selectedPO.value.id}/receive`, payload);
    receiveDialog.value = false;
    loadPurchaseOrders();
    alert('Goods received successfully');
  } catch (error) {
    console.error('Failed to receive goods:', error);
    const errorMsg = error.response?.data?.message || 'Failed to receive goods';
    alert(errorMsg);
  } finally {
    receiving.value = false;
  }
};

onMounted(async () => {
  const [storesRes, suppliersRes, productsRes] = await Promise.all([
    axios.get('/api/stores'),
    axios.get('/api/suppliers'),
    axios.get('/api/products', { params: { per_page: 1000 } }),
  ]);
  stores.value = storesRes.data.data || storesRes.data;
  suppliers.value = suppliersRes.data.data || suppliersRes.data;
  products.value = productsRes.data.data || productsRes.data;
  loadPurchaseOrders();
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>

