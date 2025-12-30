<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Orders</h1>
      </v-col>
    </v-row>

    <!-- Filters and Actions -->
    <v-row>
      <v-col cols="12" md="4">
        <v-text-field
          v-model="filters.search"
          label="Search (Order #, Customer)"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadOrders"
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
          @update:model-value="loadOrders"
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
          @update:model-value="loadOrders"
        ></v-select>
      </v-col>
      <v-col cols="12" md="3" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" v-if="authStore.hasPermission('create_orders')">
          <v-icon left>mdi-plus</v-icon>
          New Order
        </v-btn>
      </v-col>
    </v-row>

    <!-- Orders Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="orders"
        :loading="loading"
      >
        <template v-slot:item.order_number="{ item }">
          <a @click="viewOrder(item)" class="text-primary cursor-pointer">
            {{ item.order_number }}
          </a>
        </template>

        <template v-slot:item.customer="{ item }">
          {{ item.customer?.name }}
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
          <v-btn icon size="small" @click="viewOrder(item)" title="View">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="confirmOrder(item)"
            title="Confirm"
            color="success"
            v-if="item.status === 'pending' && authStore.hasPermission('confirm_orders')"
          >
            <v-icon>mdi-check</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="cancelOrder(item)"
            title="Cancel"
            color="error"
            v-if="item.status !== 'cancelled' && authStore.hasPermission('cancel_orders')"
          >
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Order Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="900px">
      <v-card v-if="selectedOrder">
        <v-card-title class="bg-primary text-white">
          Order Details - {{ selectedOrder.order_number }}
          <v-chip :color="getStatusColor(selectedOrder.status)" class="ml-2">
            {{ selectedOrder.status }}
          </v-chip>
        </v-card-title>
        <v-card-text class="mt-4">
          <v-row>
            <v-col cols="6">
              <strong>Date:</strong> {{ selectedOrder.order_date }}
            </v-col>
            <v-col cols="6">
              <strong>Customer:</strong> {{ selectedOrder.customer?.name }}
            </v-col>
            <v-col cols="6">
              <strong>Store:</strong> {{ selectedOrder.store?.name }}
            </v-col>
            <v-col cols="6">
              <strong>Delivery Date:</strong> {{ selectedOrder.delivery_date || 'N/A' }}
            </v-col>
            <v-col cols="12">
              <strong>Delivery Address:</strong> {{ selectedOrder.delivery_address || 'N/A' }}
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <h3 class="mb-2">Items</h3>
          <v-table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in selectedOrder.items" :key="item.id">
                <td>{{ item.product?.name }}</td>
                <td>{{ item.quantity }}</td>
                <td>₦{{ formatNumber(item.unit_price) }}</td>
                <td>₦{{ formatNumber(item.subtotal) }}</td>
              </tr>
            </tbody>
          </v-table>

          <v-divider class="my-4"></v-divider>

          <v-row>
            <v-col cols="6" offset="6">
              <div class="d-flex justify-space-between mb-2">
                <span>Subtotal:</span>
                <span>₦{{ formatNumber(selectedOrder.subtotal_amount) }}</span>
              </div>
              <div class="d-flex justify-space-between mb-2">
                <span>Discount:</span>
                <span>₦{{ formatNumber(selectedOrder.discount_amount) }}</span>
              </div>
              <div class="d-flex justify-space-between mb-2">
                <span>Tax:</span>
                <span>₦{{ formatNumber(selectedOrder.tax_amount) }}</span>
              </div>
              <v-divider class="my-2"></v-divider>
              <div class="d-flex justify-space-between text-h6">
                <span>Total:</span>
                <span class="text-primary">₦{{ formatNumber(selectedOrder.total_amount) }}</span>
              </div>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <div v-if="selectedOrder.notes">
            <strong>Notes:</strong>
            <p>{{ selectedOrder.notes }}</p>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-btn
            color="success"
            @click="confirmOrder(selectedOrder)"
            v-if="selectedOrder.status === 'pending' && authStore.hasPermission('confirm_orders')"
          >
            Confirm Order
          </v-btn>
          <v-btn
            color="error"
            @click="cancelOrder(selectedOrder)"
            v-if="selectedOrder.status !== 'cancelled' && authStore.hasPermission('cancel_orders')"
          >
            Cancel Order
          </v-btn>
          <v-spacer></v-spacer>
          <v-btn @click="detailDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Create Order Dialog -->
    <v-dialog v-model="createDialog" max-width="800px" persistent>
      <v-card>
        <v-card-title>New Order</v-card-title>
        <v-card-text>
          <v-form ref="orderForm">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="orderData.customer_id"
                  :items="customers"
                  item-title="name"
                  item-value="id"
                  label="Customer *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="orderData.store_id"
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
                  v-model="orderData.delivery_date"
                  label="Delivery Date"
                  type="date"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="orderData.delivery_address"
                  label="Delivery Address"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="orderData.notes"
                  label="Notes"
                  variant="outlined"
                  rows="2"
                ></v-textarea>
              </v-col>
            </v-row>
            <p class="text-caption text-grey">Note: Add items after creating the order</p>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="createDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="createOrder" :loading="saving">Create Order</v-btn>
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
const detailDialog = ref(false);
const createDialog = ref(false);
const orders = ref([]);
const stores = ref([]);
const customers = ref([]);
const selectedOrder = ref(null);

const filters = ref({
  search: '',
  status: null,
  store_id: null,
});

const orderData = ref({
  customer_id: null,
  store_id: null,
  delivery_date: '',
  delivery_address: '',
  notes: '',
});

const statusOptions = [
  { title: 'Pending', value: 'pending' },
  { title: 'Confirmed', value: 'confirmed' },
  { title: 'Processing', value: 'processing' },
  { title: 'Completed', value: 'completed' },
  { title: 'Cancelled', value: 'cancelled' },
];

const headers = [
  { title: 'Order #', key: 'order_number' },
  { title: 'Date', key: 'order_date' },
  { title: 'Customer', key: 'customer' },
  { title: 'Total', key: 'total_amount' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formatNumber = (num) => new Intl.NumberFormat('en-NG').format(num);

const getStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    confirmed: 'info',
    processing: 'primary',
    completed: 'success',
    cancelled: 'error',
  };
  return colors[status] || 'default';
};

const loadOrders = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/orders', { params: filters.value });
    orders.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load orders:', error);
  } finally {
    loading.value = false;
  }
};

const viewOrder = async (order) => {
  try {
    const response = await axios.get(`/api/orders/${order.id}`);
    selectedOrder.value = response.data;
    detailDialog.value = true;
  } catch (error) {
    console.error('Failed to load order details:', error);
  }
};

const openCreateDialog = () => {
  orderData.value = {
    customer_id: null,
    store_id: null,
    delivery_date: '',
    delivery_address: '',
    notes: '',
  };
  createDialog.value = true;
};

const createOrder = async () => {
  saving.value = true;
  try {
    await axios.post('/api/orders', orderData.value);
    createDialog.value = false;
    loadOrders();
    alert('Order created successfully');
  } catch (error) {
    console.error('Failed to create order:', error);
    alert('Failed to create order');
  } finally {
    saving.value = false;
  }
};

const confirmOrder = async (order) => {
  if (confirm(`Confirm order ${order.order_number}?`)) {
    try {
      await axios.post(`/api/orders/${order.id}/confirm`);
      loadOrders();
      if (detailDialog.value) detailDialog.value = false;
      alert('Order confirmed successfully');
    } catch (error) {
      console.error('Failed to confirm order:', error);
      alert('Failed to confirm order');
    }
  }
};

const cancelOrder = async (order) => {
  if (confirm(`Cancel order ${order.order_number}?`)) {
    try {
      await axios.post(`/api/orders/${order.id}/cancel`);
      loadOrders();
      if (detailDialog.value) detailDialog.value = false;
      alert('Order cancelled successfully');
    } catch (error) {
      console.error('Failed to cancel order:', error);
      alert('Failed to cancel order');
    }
  }
};

onMounted(async () => {
  const [storesRes, customersRes] = await Promise.all([
    axios.get('/api/stores'),
    axios.get('/api/customers'),
  ]);
  stores.value = storesRes.data.data || storesRes.data;
  customers.value = customersRes.data.data || customersRes.data;
  loadOrders();
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>

