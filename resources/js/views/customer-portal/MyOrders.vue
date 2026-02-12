<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">My Orders</h1>
      </v-col>
    </v-row>

    <!-- Filters -->
    <v-row>
      <v-col cols="12" md="4">
        <v-text-field
          v-model="filters.search"
          label="Search orders..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadOrders"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
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
    </v-row>

    <!-- Orders List -->
    <v-row>
      <v-col
        v-for="order in orders"
        :key="order.id"
        cols="12"
        md="6"
      >
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Order #{{ order.order_number }}</span>
            <v-chip :color="getStatusColor(order.status)" size="small">
              {{ order.status }}
            </v-chip>
          </v-card-title>

          <v-card-text>
            <v-row>
              <v-col cols="6">
                <div class="text-caption text-grey">Order Date</div>
                <div>{{ order.order_date }}</div>
              </v-col>
              <v-col cols="6">
                <div class="text-caption text-grey">Total Amount</div>
                <div class="text-h6 text-primary">₦{{ formatNumber(order.total_amount) }}</div>
              </v-col>
              <v-col cols="12" v-if="order.delivery_date">
                <div class="text-caption text-grey">Delivery Date</div>
                <div>{{ order.delivery_date }}</div>
              </v-col>
              <v-col cols="12" v-if="order.delivery_address">
                <div class="text-caption text-grey">Delivery Address</div>
                <div>{{ order.delivery_address }}</div>
              </v-col>
            </v-row>
          </v-card-text>

          <v-card-actions>
            <v-btn @click="viewOrderDetails(order)">
              <v-icon left>mdi-eye</v-icon>
              View Details
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn
              color="error"
              variant="text"
              @click="cancelOrder(order)"
              v-if="order.status === 'pending'"
            >
              Cancel
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <div v-if="orders.length === 0 && !loading" class="text-center text-grey py-8">
      <v-icon size="64">mdi-package-variant-closed</v-icon>
      <div class="mt-2">No orders found</div>
    </div>

    <!-- Order Details Dialog -->
    <v-dialog v-model="detailDialog" max-width="700px">
      <v-card v-if="selectedOrder">
        <v-card-title class="bg-primary text-white">
          Order #{{ selectedOrder.order_number }}
          <v-chip :color="getStatusColor(selectedOrder.status)" class="ml-2">
            {{ selectedOrder.status }}
          </v-chip>
        </v-card-title>

        <v-card-text class="mt-4">
          <v-row>
            <v-col cols="6">
              <strong>Order Date:</strong> {{ selectedOrder.order_date }}
            </v-col>
            <v-col cols="6">
              <strong>Delivery Date:</strong> {{ selectedOrder.delivery_date || 'N/A' }}
            </v-col>
            <v-col cols="12">
              <strong>Delivery Address:</strong> {{ selectedOrder.delivery_address || 'N/A' }}
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <h3 class="mb-2">Order Items</h3>
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
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="detailDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';
import { useDialog } from '@/composables/useDialog';

const authStore = useAuthStore();
const { alert, confirm } = useDialog();
const loading = ref(false);
const detailDialog = ref(false);
const orders = ref([]);
const selectedOrder = ref(null);

const filters = ref({
  search: '',
  status: null,
});

const statusOptions = [
  { title: 'Pending', value: 'pending' },
  { title: 'Confirmed', value: 'confirmed' },
  { title: 'Processing', value: 'processing' },
  { title: 'Completed', value: 'completed' },
  { title: 'Cancelled', value: 'cancelled' },
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
    const params = { ...filters.value, customer_id: authStore.user.id };
    const response = await axios.get('/api/orders', { params });
    orders.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load orders:', error);
  } finally {
    loading.value = false;
  }
};

const viewOrderDetails = async (order) => {
  try {
    const response = await axios.get(`/api/orders/${order.id}`);
    selectedOrder.value = response.data;
    detailDialog.value = true;
  } catch (error) {
    console.error('Failed to load order details:', error);
  }
};

const cancelOrder = async (order) => {
  const confirmed = await confirm('Are you sure you want to cancel this order?');
  if (!confirmed) return;
  try {
    await axios.post(`/api/orders/${order.id}/cancel`);
    loadOrders();
    alert('Order cancelled successfully');
  } catch (error) {
    console.error('Failed to cancel order:', error);
    alert('Failed to cancel order');
  }
};

onMounted(() => {
  loadOrders();
});
</script>
