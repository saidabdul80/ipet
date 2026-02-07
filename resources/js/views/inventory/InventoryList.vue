<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Inventory Management</h1>
      </v-col>
    </v-row>

    <v-tabs v-model="activeTab" color="primary">
      <v-tab value="stock-levels">Stock Levels</v-tab>
      <v-tab value="stock-ledger">Stock Ledger</v-tab>
      <v-tab value="low-stock">Low Stock Alerts</v-tab>
      <v-tab value="transfers">Stock Transfers</v-tab>
    </v-tabs>

    <v-window v-model="activeTab" class="mt-4">
      <!-- Stock Levels -->
      <v-window-item value="stock-levels">
        <v-card>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <v-select
                  v-model="filters.store_id"
                  :items="stores"
                  item-title="name"
                  item-value="id"
                  label="Store *"
                  variant="outlined"
                  density="compact"
                  @update:model-value="loadStockLevels"
                ></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="filters.category_id"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  label="Category"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadStockLevels"
                ></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="filters.search"
                  label="Search Product"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="compact"
                  clearable
                  @input="loadStockLevels"
                ></v-text-field>
              </v-col>
            </v-row>

            <v-data-table
              :headers="stockHeaders"
              :items="stockLevels"
              :loading="loading"
            >
              <template v-slot:item.current_quantity="{ item }">
                <v-chip :color="getStockColor(item)" size="small">
                  {{ item.current_quantity }} {{ item.unit?.short_name || '' }}
                </v-chip>
              </template>
              <template v-slot:item.average_cost="{ item }">
                ₦{{ formatNumber(item.average_cost) }}
              </template>
              <template v-slot:item.stock_value="{ item }">
                ₦{{ formatNumber(item.stock_value) }}
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Stock Ledger -->
      <v-window-item value="stock-ledger">
        <v-card>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="3">
                <v-select
                  v-model="ledgerFilters.store_id"
                  :items="stores"
                  item-title="name"
                  item-value="id"
                  label="Store *"
                  variant="outlined"
                  density="compact"
                  @update:model-value="loadStockLedger"
                ></v-select>
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="ledgerFilters.product_id"
                  :items="products"
                  item-title="name"
                  item-value="id"
                  label="Product"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadStockLedger"
                ></v-select>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="ledgerFilters.date_from"
                  label="From Date"
                  type="date"
                  variant="outlined"
                  density="compact"
                  @change="loadStockLedger"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="ledgerFilters.date_to"
                  label="To Date"
                  type="date"
                  variant="outlined"
                  density="compact"
                  @change="loadStockLedger"
                ></v-text-field>
              </v-col>
            </v-row>

            <v-data-table
              :headers="ledgerHeaders"
              :items="stockLedger"
              :loading="loadingLedger"
            >
              <template v-slot:item.transaction_date="{ item }">
                {{ formatDate(item.transaction_date) }}
              </template>
              <template v-slot:item.transaction_type="{ item }">
                <v-chip :color="getTransactionColor(item.transaction_type)" size="small">
                  {{ formatTransactionType(item.transaction_type) }}
                </v-chip>
              </template>
              <template v-slot:item.quantity_in="{ item }">
                <span class="text-success font-weight-bold">{{ getQuantityIn(item) }}</span>
              </template>
              <template v-slot:item.quantity_out="{ item }">
                <span class="text-error font-weight-bold">{{ getQuantityOut(item) }}</span>
              </template>
              <template v-slot:item.balance_quantity="{ item }">
                <span class="font-weight-bold">{{ formatNumber(item.balance_quantity) }}</span>
              </template>
              <template v-slot:item.reference_type="{ item }">
                <span v-if="item.reference_type">
                  {{ formatReferenceType(item.reference_type) }} #{{ item.reference_id }}
                </span>
                <span v-else class="text-grey">-</span>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Low Stock Alerts -->
      <v-window-item value="low-stock">
        <v-card>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="lowStockFilter.store_id"
                  :items="stores"
                  item-title="name"
                  item-value="id"
                  label="Store"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadLowStock"
                ></v-select>
              </v-col>
            </v-row>

            <v-data-table
              :headers="lowStockHeaders"
              :items="lowStockItems"
              :loading="loadingLowStock"
            >
              <template v-slot:item.current_stock="{ item }">
                <v-chip :color="item.current_stock <= item.reorder_level ? 'error' : 'warning'" size="small">
                  {{ item.current_stock }}
                </v-chip>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Stock Transfers -->
      <v-window-item value="transfers">
        <v-card>
          <v-card-text>
            <v-row class="mb-4">
              <v-col cols="12" md="3">
                <v-select
                  v-model="transferFilters.store_id"
                  :items="stores"
                  item-title="name"
                  item-value="id"
                  label="Filter by Store"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadTransfers"
                ></v-select>
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="transferFilters.status"
                  :items="['draft', 'pending', 'in_transit', 'received', 'cancelled']"
                  label="Status"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadTransfers"
                ></v-select>
              </v-col>
              <v-col cols="12" md="auto">
                <v-btn color="primary" @click="openTransferDialog" v-if="authStore.hasPermission('create_stock_transfers')">
                  <v-icon left>mdi-transfer</v-icon>
                  New Transfer
                </v-btn>
              </v-col>
            </v-row>

            <v-data-table
              :headers="transferHeaders"
              :items="transfers"
              :loading="loadingTransfers"
            >
              <template v-slot:item.transfer_number="{ item }">
                <span class="font-weight-medium">{{ item.transfer_number }}</span>
              </template>
              <template v-slot:item.transfer_date="{ item }">
                {{ new Date(item.transfer_date).toLocaleDateString() }}
              </template>
              <template v-slot:item.from_store="{ item }">
                {{ item.from_store?.name }}
              </template>
              <template v-slot:item.to_store="{ item }">
                {{ item.to_store?.name }}
              </template>
              <template v-slot:item.status="{ item }">
                <v-chip :color="getTransferStatusColor(item.status)" size="small">
                  {{ item.status }}
                </v-chip>
              </template>
              <template v-slot:item.initiated_by="{ item }">
                {{ item.initiated_by?.name || 'N/A' }}
              </template>
              <template v-slot:item.actions="{ item }">
                <v-btn
                  icon
                  size="small"
                  color="primary"
                  @click="viewTransferDetails(item)"
                  title="View Details"
                >
                  <v-icon>mdi-eye</v-icon>
                </v-btn>
                <v-btn
                  icon
                  size="small"
                  color="success"
                  @click="approveTransfer(item)"
                  title="Approve Transfer"
                  v-if="item.status === 'pending' && authStore.hasPermission('approve_stock_transfers')"
                >
                  <v-icon>mdi-check-circle</v-icon>
                </v-btn>
                <v-btn
                  icon
                  size="small"
                  color="info"
                  @click="receiveTransfer(item)"
                  title="Receive Transfer"
                  v-if="item.status === 'in_transit' && authStore.hasPermission('receive_stock_transfers')"
                >
                  <v-icon>mdi-package-variant</v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>
    </v-window>

    <!-- Transfer Dialog -->
    <v-dialog v-model="transferDialog" max-width="600px" persistent>
      <v-card>
        <v-card-title>Create Stock Transfer</v-card-title>
        <v-card-text>
          <v-form ref="transferForm">
            <v-select
              v-model="transferData.from_store_id"
              :items="stores"
              item-title="name"
              item-value="id"
              label="From Store *"
              variant="outlined"
              :rules="[v => !!v || 'Required']"
            ></v-select>
            <v-select
              v-model="transferData.to_store_id"
              :items="stores"
              item-title="name"
              item-value="id"
              label="To Store *"
              variant="outlined"
              :rules="[v => !!v || 'Required', v => v !== transferData.from_store_id || 'Must be different']"
            ></v-select>
            <v-select
              v-model="transferData.product_id"
              :items="products"
              item-title="name"
              item-value="id"
              label="Product *"
              variant="outlined"
              :rules="[v => !!v || 'Required']"
            ></v-select>
            <v-text-field
              v-model.number="transferData.quantity"
              label="Quantity *"
              type="number"
              min="1"
              variant="outlined"
              :rules="[v => v > 0 || 'Must be greater than 0']"
            ></v-text-field>
            <v-textarea
              v-model="transferData.notes"
              label="Notes"
              variant="outlined"
              rows="2"
            ></v-textarea>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="transferDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="createTransfer" :loading="savingTransfer">Create Transfer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Transfer Details Dialog -->
    <v-dialog v-model="detailsDialog" max-width="800px">
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>Stock Transfer Details</span>
          <v-chip :color="getTransferStatusColor(selectedTransfer?.status)" size="small">
            {{ selectedTransfer?.status }}
          </v-chip>
        </v-card-title>
        <v-card-text>
          <v-row v-if="selectedTransfer">
            <v-col cols="12" md="6">
              <div class="mb-2"><strong>Transfer Number:</strong> {{ selectedTransfer.transfer_number }}</div>
              <div class="mb-2"><strong>From Store:</strong> {{ selectedTransfer.from_store?.name }}</div>
              <div class="mb-2"><strong>To Store:</strong> {{ selectedTransfer.to_store?.name }}</div>
              <div class="mb-2"><strong>Transfer Date:</strong> {{ new Date(selectedTransfer.transfer_date).toLocaleDateString() }}</div>
            </v-col>
            <v-col cols="12" md="6">
              <div class="mb-2"><strong>Initiated By:</strong> {{ selectedTransfer.initiated_by?.name }}</div>
              <div class="mb-2" v-if="selectedTransfer.approved_by">
                <strong>Approved By:</strong> {{ selectedTransfer.approved_by?.name }}
              </div>
              <div class="mb-2" v-if="selectedTransfer.received_by">
                <strong>Received By:</strong> {{ selectedTransfer.received_by?.name }}
              </div>
              <div class="mb-2" v-if="selectedTransfer.notes">
                <strong>Notes:</strong> {{ selectedTransfer.notes }}
              </div>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <h3 class="mb-3">Transfer Items</h3>
          <v-data-table
            :headers="detailsHeaders"
            :items="transferItems"
            :loading="loadingDetails"
            density="compact"
          >
            <template v-slot:item.line_total="{ item }">
              ₦{{ item.line_total?.toLocaleString() }}
            </template>
          </v-data-table>

          <v-row class="mt-4">
            <v-col cols="12" class="text-right">
              <h3>Total: ₦{{ transferTotal.toLocaleString() }}</h3>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="detailsDialog = false">Close</v-btn>
          <v-btn
            color="success"
            @click="approveFromDetails"
            v-if="selectedTransfer?.status === 'pending' && authStore.hasPermission('approve_stock_transfers')"
          >
            <v-icon left>mdi-check-circle</v-icon>
            Approve Transfer
          </v-btn>
          <v-btn
            color="info"
            @click="receiveFromDetails"
            v-if="selectedTransfer?.status === 'in_transit' && authStore.hasPermission('receive_stock_transfers')"
          >
            <v-icon left>mdi-package-variant</v-icon>
            Receive Transfer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const authStore = useAuthStore();
const activeTab = ref('stock-levels');
const loading = ref(false);
const loadingLedger = ref(false);
const loadingLowStock = ref(false);
const loadingTransfers = ref(false);
const savingTransfer = ref(false);
const transferDialog = ref(false);
const detailsDialog = ref(false);
const loadingDetails = ref(false);

const stores = ref([]);
const categories = ref([]);
const products = ref([]);
const stockLevels = ref([]);
const stockLedger = ref([]);
const lowStockItems = ref([]);
const transfers = ref([]);
const selectedTransfer = ref(null);
const transferItems = ref([]);

const filters = ref({ store_id: null, category_id: null, search: '' });
const ledgerFilters = ref({ store_id: null, product_id: null, date_from: '', date_to: '' });
const lowStockFilter = ref({ store_id: null });
const transferFilters = ref({ store_id: null, status: null });

const transferData = ref({
  from_store_id: null,
  to_store_id: null,
  product_id: null,
  quantity: 1,
  notes: '',
});

const stockHeaders = [
  { title: 'Product', key: 'product_name' },
  { title: 'SKU', key: 'sku' },
  { title: 'Current Quantity', key: 'current_quantity' },
  { title: 'Reorder Level', key: 'reorder_level' },
  { title: 'Average Cost', key: 'average_cost' },
  { title: 'Stock Value', key: 'stock_value' },
];

const ledgerHeaders = [
  { title: 'Date', key: 'transaction_date' },
  { title: 'Product', key: 'product.name' },
  { title: 'Type', key: 'transaction_type' },
  { title: 'In', key: 'quantity_in' },
  { title: 'Out', key: 'quantity_out' },
  { title: 'Balance', key: 'balance_quantity' },
  { title: 'Reference', key: 'reference_type' },
];

const transferHeaders = [
  { title: 'Transfer #', key: 'transfer_number' },
  { title: 'Date', key: 'transfer_date' },
  { title: 'From Store', key: 'from_store' },
  { title: 'To Store', key: 'to_store' },
  { title: 'Status', key: 'status' },
  { title: 'Initiated By', key: 'initiated_by' },
  { title: 'Notes', key: 'notes' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const lowStockHeaders = [
  { title: 'Product', key: 'product.name' },
  { title: 'SKU', key: 'product.sku' },
  { title: 'Current Stock', key: 'current_stock' },
  { title: 'Reorder Level', key: 'reorder_level' },
  { title: 'Reorder Quantity', key: 'reorder_quantity' },
];

const detailsHeaders = [
  { title: 'Product', key: 'product_name' },
  { title: 'SKU', key: 'product_sku' },
  { title: 'Quantity', key: 'quantity' },
  { title: 'Unit', key: 'unit_name' },
  { title: 'Unit Cost', key: 'unit_cost' },
  { title: 'Line Total', key: 'line_total' },
];

const formatNumber = (num) => new Intl.NumberFormat('en-NG').format(num);

const transferTotal = computed(() => {
  return transferItems.value.reduce((sum, item) => sum + (item.line_total || 0), 0);
});

const getStockColor = (item) => {
  if (item.current_quantity <= item.reorder_level) return 'error';
  if (item.current_quantity <= item.reorder_level * 1.5) return 'warning';
  return 'success';
};

const getTransactionColor = (type) => {
  const colors = {
    'receipt': 'success',
    'issue': 'error',
    'transfer_in': 'info',
    'transfer_out': 'warning',
    'adjustment_in': 'success',
    'adjustment_out': 'error',
    'return': 'info',
    'damage': 'error',
    'loss': 'error',
  };
  return colors[type] || 'default';
};

const formatTransactionType = (type) => {
  const types = {
    'receipt': 'Receipt',
    'issue': 'Issue',
    'transfer_in': 'Transfer In',
    'transfer_out': 'Transfer Out',
    'adjustment_in': 'Adjustment In',
    'adjustment_out': 'Adjustment Out',
    'return': 'Return',
    'damage': 'Damage',
    'loss': 'Loss',
  };
  return types[type] || type;
};

const formatReferenceType = (type) => {
  const types = {
    'purchase_order': 'PO',
    'sale': 'Sale',
    'transfer': 'Transfer',
    'adjustment': 'Adjustment',
    'grn': 'GRN',
  };
  return types[type] || type;
};

const getQuantityIn = (item) => {
  // Transactions that increase stock
  const inTypes = ['receipt', 'transfer_in', 'adjustment_in', 'return'];
  if (inTypes.includes(item.transaction_type)) {
    return formatNumber(Math.abs(item.quantity));
  }
  return '-';
};

const getQuantityOut = (item) => {
  // Transactions that decrease stock
  const outTypes = ['issue', 'transfer_out', 'adjustment_out', 'damage', 'loss'];
  if (outTypes.includes(item.transaction_type)) {
    return formatNumber(Math.abs(item.quantity));
  }
  return '-';
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleString('en-NG', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const loadStockLevels = async () => {
  if (!filters.value.store_id) return;
  loading.value = true;
  try {
    const response = await axios.get('/api/inventory/stock-levels', { params: filters.value });
    stockLevels.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load stock levels:', error);
  } finally {
    loading.value = false;
  }
};

const loadStockLedger = async () => {
  if (!ledgerFilters.value.store_id) return;
  loadingLedger.value = true;
  try {
    const response = await axios.get('/api/inventory/stock-ledger', { params: ledgerFilters.value });
    stockLedger.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load stock ledger:', error);
  } finally {
    loadingLedger.value = false;
  }
};

const loadLowStock = async () => {
  if (!lowStockFilter.value.store_id) return;
  loadingLowStock.value = true;
  try {
    const response = await axios.get('/api/inventory/low-stock-alert', { params: lowStockFilter.value });
    lowStockItems.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load low stock items:', error);
  } finally {
    loadingLowStock.value = false;
  }
};

const loadTransfers = async () => {
  loadingTransfers.value = true;
  try {
    const response = await axios.get('/api/inventory/stock-transfers', { params: transferFilters.value });
    transfers.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load transfers:', error);
  } finally {
    loadingTransfers.value = false;
  }
};

const getTransferStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    approved: 'info',
    completed: 'success',
    cancelled: 'error',
  };
  return colors[status] || 'grey';
};

const openTransferDialog = () => {
  transferData.value = {
    from_store_id: null,
    to_store_id: null,
    product_id: null,
    quantity: 1,
    notes: '',
  };
  transferDialog.value = true;
};

const createTransfer = async () => {
  savingTransfer.value = true;
  try {
    await axios.post('/api/inventory/stock-transfers', transferData.value);
    transferDialog.value = false;
    alert('Stock transfer created successfully!');
    loadTransfers(); // Reload transfers
  } catch (error) {
    console.error('Failed to create transfer:', error);
    alert('Failed to create transfer');
  } finally {
    savingTransfer.value = false;
  }
};

const approveTransfer = async (transfer) => {
  if (!confirm(`Approve stock transfer ${transfer.transfer_number}?`)) return;

  try {
    const response = await axios.post(`/api/inventory/stock-transfers/${transfer.id}/approve`, {});
    alert(response.data.message);
    loadTransfers(); // Reload the list
  } catch (error) {
    console.error('Failed to approve transfer:', error);
    alert(error.response?.data?.message || 'Failed to approve transfer');
  }
};

const receiveTransfer = async (transfer) => {
  if (!confirm(`Mark stock transfer ${transfer.transfer_number} as received?`)) return;

  try {
    const response = await axios.post(`/api/inventory/stock-transfers/${transfer.id}/receive`, {
      received_date: new Date().toISOString().split('T')[0],
      notes: null
    });
    alert(response.data.message);
    loadTransfers(); // Reload the list
  } catch (error) {
    console.error('Failed to receive transfer:', error);
    alert(error.response?.data?.message || 'Failed to receive transfer');
  }
};

const viewTransferDetails = async (transfer) => {
  loadingDetails.value = true;
  detailsDialog.value = true;
  selectedTransfer.value = transfer;
  transferItems.value = [];

  try {
    const response = await axios.get(`/api/inventory/stock-transfers/${transfer.id}`);
    selectedTransfer.value = response.data.transfer;
    transferItems.value = response.data.items;
  } catch (error) {
    console.error('Failed to load transfer details:', error);
    alert('Failed to load transfer details');
  } finally {
    loadingDetails.value = false;
  }
};

const approveFromDetails = async () => {
  if (!selectedTransfer.value) return;

  if (!confirm(`Approve stock transfer ${selectedTransfer.value.transfer_number}?`)) return;

  try {
    const response = await axios.post(`/api/inventory/stock-transfers/${selectedTransfer.value.id}/approve`, {});
    alert(response.data.message);
    detailsDialog.value = false;
    loadTransfers(); // Reload the list
  } catch (error) {
    console.error('Failed to approve transfer:', error);
    alert(error.response?.data?.message || 'Failed to approve transfer');
  }
};

const receiveFromDetails = async () => {
  if (!selectedTransfer.value) return;

  if (!confirm(`Mark stock transfer ${selectedTransfer.value.transfer_number} as received?`)) return;

  try {
    const response = await axios.post(`/api/inventory/stock-transfers/${selectedTransfer.value.id}/receive`, {
      received_date: new Date().toISOString().split('T')[0],
      notes: null
    });
    alert(response.data.message);
    detailsDialog.value = false;
    loadTransfers(); // Reload the list
  } catch (error) {
    console.error('Failed to receive transfer:', error);
    alert(error.response?.data?.message || 'Failed to receive transfer');
  }
};

onMounted(async () => {
  const [storesRes, categoriesRes, productsRes] = await Promise.all([
    axios.get('/api/stores'),
    axios.get('/api/categories'),
    axios.get('/api/products'),
  ]);
  stores.value = storesRes.data.data || storesRes.data;
  categories.value = categoriesRes.data.data || categoriesRes.data;
  products.value = productsRes.data.data || productsRes.data;

  // Auto-select first store for all tabs if available
  if (stores.value.length > 0) {
    filters.value.store_id = stores.value[0].id;
    ledgerFilters.value.store_id = stores.value[0].id;
    lowStockFilter.value.store_id = stores.value[0].id;
    loadStockLevels();
    loadStockLedger();
    loadLowStock();
  }
});
</script>

