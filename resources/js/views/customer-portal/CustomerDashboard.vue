<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Welcome, {{ authStore.user?.name }}!</h1>
      </v-col>
    </v-row>

    <!-- Stats Cards -->
    <v-row>
      <v-col cols="12" md="3">
        <v-card color="primary" dark>
          <v-card-text>
            <div class="text-h4">{{ stats.total_orders }}</div>
            <div class="text-caption">Total Orders</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="success" dark>
          <v-card-text>
            <div class="text-h4">{{ stats.completed_orders }}</div>
            <div class="text-caption">Completed Orders</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="warning" dark>
          <v-card-text>
            <div class="text-h4">{{ stats.pending_orders }}</div>
            <div class="text-caption">Pending Orders</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="info" dark>
          <v-card-text>
            <div class="text-h4">₦{{ formatNumber(stats.wallet_balance) }}</div>
            <div class="text-caption">Wallet Balance</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Recent Orders -->
    <v-row class="mt-4">
      <v-col cols="12">
        <v-card>
          <v-card-title>Recent Orders</v-card-title>
          <v-card-text>
            <v-table>
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Date</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in recentOrders" :key="order.id">
                  <td>{{ order.order_number }}</td>
                  <td>{{ order.order_date }}</td>
                  <td>₦{{ formatNumber(order.total_amount) }}</td>
                  <td>
                    <v-chip :color="getStatusColor(order.status)" size="small">
                      {{ order.status }}
                    </v-chip>
                  </td>
                  <td>
                    <v-btn size="small" @click="viewOrder(order)">View</v-btn>
                  </td>
                </tr>
              </tbody>
            </v-table>
            <div v-if="recentOrders.length === 0" class="text-center text-grey py-4">
              No recent orders
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Quick Actions -->
    <v-row class="mt-4">
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title>Quick Actions</v-card-title>
          <v-card-text>
            <v-btn block color="primary" class="mb-2" to="/customer-portal/place-order">
              <v-icon left>mdi-cart-plus</v-icon>
              Place New Order
            </v-btn>
            <v-btn block color="info" to="/customer-portal/orders">
              <v-icon left>mdi-package-variant</v-icon>
              View All Orders
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title>Account Information</v-card-title>
          <v-card-text>
            <v-list>
              <v-list-item>
                <v-list-item-title>Customer Type</v-list-item-title>
                <v-list-item-subtitle>{{ authStore.user?.customer_type || 'Retail' }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Email</v-list-item-title>
                <v-list-item-subtitle>{{ authStore.user?.email }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Phone</v-list-item-title>
                <v-list-item-subtitle>{{ authStore.user?.phone || 'N/A' }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const router = useRouter();
const authStore = useAuthStore();
const stats = ref({
  total_orders: 0,
  completed_orders: 0,
  pending_orders: 0,
  wallet_balance: 0,
});
const recentOrders = ref([]);

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

const loadDashboardData = async () => {
  try {
    const [ordersRes, walletRes] = await Promise.all([
      axios.get('/api/orders', { params: { customer_id: authStore.user.id, per_page: 5 } }),
      axios.get(`/api/wallet/customers/${authStore.user.id}/balance`),
    ]);

    const orders = ordersRes.data.data || ordersRes.data;
    recentOrders.value = orders;

    stats.value = {
      total_orders: orders.length,
      completed_orders: orders.filter(o => o.status === 'completed').length,
      pending_orders: orders.filter(o => o.status === 'pending').length,
      wallet_balance: walletRes.data.balance,
    };
  } catch (error) {
    console.error('Failed to load dashboard data:', error);
  }
};

const viewOrder = (order) => {
  router.push(`/customer-portal/orders?order=${order.id}`);
};

onMounted(() => {
  loadDashboardData();
});
</script>

