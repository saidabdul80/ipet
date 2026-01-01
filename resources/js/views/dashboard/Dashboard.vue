<template>
  <div>
    <!-- Header Section -->
    <v-row class="mb-4">
      <v-col cols="12" md="6">
        <div class="d-flex align-center">
          <v-avatar color="primary" size="48" class="mr-4">
            <v-icon size="28">mdi-view-dashboard</v-icon>
          </v-avatar>
          <div>
            <h1 class="text-h4 font-weight-bold mb-1">Dashboard</h1>
            <p class="text-subtitle-1 text-grey mb-0">Welcome back! Here's what's happening today.</p>
          </div>
        </div>
      </v-col>
      <v-col cols="12" md="6" class="d-flex align-center justify-end">
        <v-chip color="success" variant="flat" class="mr-2">
          <v-icon left size="small">mdi-circle</v-icon>
          System Online
        </v-chip>
        <v-chip variant="outlined">
          <v-icon left size="small">mdi-clock-outline</v-icon>
          {{ currentTime }}
        </v-chip>
      </v-col>
    </v-row>

    <!-- Period Selector -->
    <v-row class="mb-2">
      <v-col cols="12" md="3">
        <v-select
          v-model="selectedPeriod"
          :items="periods"
          label="Period"
          variant="outlined"
          density="comfortable"
          prepend-inner-icon="mdi-calendar"
          @update:model-value="loadDashboardData"
          hide-details
        ></v-select>
      </v-col>
      <v-col cols="12" md="3" v-if="authStore.hasPermission('view_branches')">
        <v-select
          v-model="selectedBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          label="Branch"
          variant="outlined"
          density="comfortable"
          prepend-inner-icon="mdi-office-building"
          clearable
          @update:model-value="loadDashboardData"
          hide-details
        ></v-select>
      </v-col>
      <v-col cols="12" md="3" v-if="authStore.hasPermission('view_stores')">
        <v-select
          v-model="selectedStore"
          :items="stores"
          item-title="name"
          item-value="id"
          label="Store"
          variant="outlined"
          density="comfortable"
          prepend-inner-icon="mdi-store"
          clearable
          @update:model-value="loadDashboardData"
          hide-details
        ></v-select>
      </v-col>
      <v-col cols="12" md="3" class="d-flex align-center">
        <v-btn
          color="primary"
          variant="flat"
          prepend-icon="mdi-refresh"
          @click="loadDashboardData"
          block
        >
          Refresh Data
        </v-btn>
      </v-col>
    </v-row>

    <!-- Stats Cards -->
    <v-row>
      <v-col cols="12" sm="6" md="3">
        <v-card elevation="1" class="pa-4">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-grey mb-1">Total Sales</div>
              <div class="text-h5 font-weight-bold">{{ formatNumber(stats.sales?.total_sales || 0) }}</div>
            </div>
            <v-icon size="40" color="primary">mdi-cart</v-icon>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card elevation="1" class="pa-4">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-grey mb-1">Revenue</div>
              <div class="text-h5 font-weight-bold">₦{{ formatNumber(stats.sales?.total_revenue || 0) }}</div>
            </div>
            <v-icon size="40" color="success">mdi-cash-multiple</v-icon>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card elevation="1" class="pa-4">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-grey mb-1">Profit</div>
              <div class="text-h5 font-weight-bold">₦{{ formatNumber(stats.sales?.total_profit || 0) }}</div>
            </div>
            <v-icon size="40" color="info">mdi-chart-line</v-icon>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card elevation="1" class="pa-4">
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-caption text-grey mb-1">Low Stock Items</div>
              <div class="text-h5 font-weight-bold">{{ formatNumber(stats.inventory?.low_stock_items || 0) }}</div>
            </div>
            <v-icon size="40" color="warning">mdi-alert</v-icon>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Orders & Wallet Stats -->
    <v-row>
      <v-col cols="12" md="6">
        <v-card elevation="1">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-clipboard-list</v-icon>
            <span>Orders Overview</span>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text>
            <v-row>
              <v-col cols="6">
                <div class="text-caption text-grey">Total Orders</div>
                <div class="text-h6 font-weight-bold">{{ formatNumber(stats.orders?.total_orders || 0) }}</div>
              </v-col>
              <v-col cols="6">
                <div class="text-caption text-grey">Pending</div>
                <div class="text-h6 font-weight-bold text-warning">{{ formatNumber(stats.orders?.pending_orders || 0) }}</div>
              </v-col>
              <v-col cols="6">
                <div class="text-caption text-grey">Confirmed</div>
                <div class="text-h6 font-weight-bold text-info">{{ formatNumber(stats.orders?.confirmed_orders || 0) }}</div>
              </v-col>
              <v-col cols="6">
                <div class="text-caption text-grey">Completed</div>
                <div class="text-h6 font-weight-bold text-success">{{ formatNumber(stats.orders?.completed_orders || 0) }}</div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card class="detail-card" elevation="2">
          <v-card-title class="d-flex align-center pa-6 pb-4">
            <v-icon color="success" class="mr-2">mdi-wallet</v-icon>
            <span class="font-weight-bold">Wallet Overview</span>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-6">
            <v-row>
              <v-col cols="6" class="mb-4">
                <div class="wallet-stat-box wallet-credit">
                  <div class="d-flex align-center mb-2">
                    <v-icon color="success" size="small" class="mr-2">mdi-arrow-down-circle</v-icon>
                    <span class="text-caption text-grey">Total Credits</span>
                  </div>
                  <div class="text-h5 font-weight-bold text-success">₦{{ formatNumber(stats.wallet?.total_credits || 0) }}</div>
                </div>
              </v-col>
              <v-col cols="6" class="mb-4">
                <div class="wallet-stat-box wallet-debit">
                  <div class="d-flex align-center mb-2">
                    <v-icon color="error" size="small" class="mr-2">mdi-arrow-up-circle</v-icon>
                    <span class="text-caption text-grey">Total Debits</span>
                  </div>
                  <div class="text-h5 font-weight-bold text-error">₦{{ formatNumber(stats.wallet?.total_debits || 0) }}</div>
                </div>
              </v-col>
              <v-col cols="12">
                <div class="wallet-stat-box wallet-pending">
                  <div class="d-flex align-center justify-space-between">
                    <div>
                      <div class="d-flex align-center mb-2">
                        <v-icon color="warning" size="small" class="mr-2">mdi-clock-alert</v-icon>
                        <span class="text-caption text-grey">Pending Approvals</span>
                      </div>
                      <div class="text-h4 font-weight-bold text-warning">{{ stats.wallet?.pending_approvals || 0 }}</div>
                    </div>
                    <v-btn
                      v-if="stats.wallet?.pending_approvals > 0"
                      color="warning"
                      variant="flat"
                      size="small"
                      to="/wallet"
                    >
                      Review
                    </v-btn>
                  </div>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Loading Overlay -->
    <v-overlay :model-value="loading" class="align-center justify-center">
      <v-progress-circular indeterminate size="64" color="primary"></v-progress-circular>
    </v-overlay>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const authStore = useAuthStore();

const loading = ref(false);
const selectedPeriod = ref('today');
const selectedBranch = ref(null);
const selectedStore = ref(null);
const stats = ref({});
const branches = ref([]);
const stores = ref([]);
const currentTime = ref('');

const periods = [
  { title: 'Today', value: 'today' },
  { title: 'This Week', value: 'week' },
  { title: 'This Month', value: 'month' },
  { title: 'This Year', value: 'year' },
];

// Update current time
const updateTime = () => {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  });
};

let timeInterval;

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-NG').format(num);
};

const loadDashboardData = async () => {
  loading.value = true;
  try {
    const params = { period: selectedPeriod.value };
    if (selectedBranch.value) params.branch_id = selectedBranch.value;
    if (selectedStore.value) params.store_id = selectedStore.value;

    const response = await axios.get('/api/reports/dashboard', { params });
    stats.value = response.data;
  } catch (error) {
    console.error('Failed to load dashboard data:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  // Start time update
  updateTime();
  timeInterval = setInterval(updateTime, 1000);

  // Load branches and stores if user has permission
  if (authStore.hasPermission('view_branches')) {
    const branchesRes = await axios.get('/api/branches');
    branches.value = branchesRes.data;
  }
  if (authStore.hasPermission('view_stores')) {
    const storesRes = await axios.get('/api/stores');
    stores.value = storesRes.data;
  }

  loadDashboardData();
});

onUnmounted(() => {
  if (timeInterval) {
    clearInterval(timeInterval);
  }
});
</script>

<style scoped>
/* Stat Cards */
.stat-card {
  border-radius: 16px !important;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12) !important;
}

.stat-card-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.stat-card-success {
  background: linear-gradient(135deg, #0f9d58 0%, #0b8043 100%);
  color: white;
}

.stat-card-info {
  background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
  color: white;
}

.stat-card-warning {
  background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
  color: white;
}

.stat-icon {
  background: rgba(255, 255, 255, 0.2) !important;
  backdrop-filter: blur(10px);
}

/* Detail Cards */
.detail-card {
  border-radius: 16px !important;
  border: 1px solid rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.detail-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1) !important;
}

/* Order Stat Boxes */
.order-stat-box {
  padding: 16px;
  border-radius: 12px;
  background: rgba(0, 0, 0, 0.02);
  transition: all 0.3s ease;
}

.order-stat-box:hover {
  background: rgba(0, 0, 0, 0.04);
  transform: translateY(-2px);
}

/* Wallet Stat Boxes */
.wallet-stat-box {
  padding: 16px;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.wallet-credit {
  background: linear-gradient(135deg, rgba(15, 157, 88, 0.1) 0%, rgba(11, 128, 67, 0.05) 100%);
  border: 1px solid rgba(15, 157, 88, 0.2);
}

.wallet-debit {
  background: linear-gradient(135deg, rgba(244, 67, 54, 0.1) 0%, rgba(211, 47, 47, 0.05) 100%);
  border: 1px solid rgba(244, 67, 54, 0.2);
}

.wallet-pending {
  background: linear-gradient(135deg, rgba(255, 152, 0, 0.1) 0%, rgba(245, 124, 0, 0.05) 100%);
  border: 1px solid rgba(255, 152, 0, 0.2);
}

.wallet-stat-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.v-col {
  animation: fadeInUp 0.5s ease-out;
}

.v-col:nth-child(1) { animation-delay: 0.1s; }
.v-col:nth-child(2) { animation-delay: 0.2s; }
.v-col:nth-child(3) { animation-delay: 0.3s; }
.v-col:nth-child(4) { animation-delay: 0.4s; }
</style>

