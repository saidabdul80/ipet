<template>
  <v-app>
    <!-- Customer Portal Sidebar -->
    <v-navigation-drawer
      v-model="drawer"
      app
      :rail="rail"
      permanent
      class="customer-sidebar-gradient"
      :width="280"
    >
      <!-- Logo Section -->
      <div class="pa-4 d-flex align-center justify-space-between">
        <div class="d-flex align-center" v-if="!rail">
          <v-avatar color="white" size="40" class="mr-3">
            <v-icon color="success" size="24">mdi-account-circle</v-icon>
          </v-avatar>
          <div>
            <div class="text-h6 font-weight-bold white--text">Customer Portal</div>
            <div class="text-caption text-grey-lighten-1">Shop & Track</div>
          </div>
        </div>
        <v-btn
          icon
          size="small"
          variant="text"
          @click="rail = !rail"
          class="ml-auto"
        >
          <v-icon color="white">{{ rail ? 'mdi-menu' : 'mdi-menu-open' }}</v-icon>
        </v-btn>
      </div>

      <v-divider class="border-opacity-25"></v-divider>

      <!-- Wallet Balance Card -->
      <div class="pa-4" v-if="!rail">
        <v-card class="wallet-card" elevation="0">
          <v-card-text class="pa-4">
            <div class="text-caption text-grey-lighten-1 mb-1">Wallet Balance</div>
            <div class="text-h5 font-weight-bold">₦{{ walletBalance }}</div>
            <v-btn
              size="small"
              color="white"
              variant="outlined"
              class="mt-3"
              block
              to="/customer-portal/wallet"
            >
              <v-icon left size="small">mdi-plus</v-icon>
              Fund Wallet
            </v-btn>
          </v-card-text>
        </v-card>
      </div>

      <!-- Navigation Menu -->
      <v-list density="compact" nav class="px-2">
        <v-list-item
          v-for="item in menuItems"
          :key="item.title"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          rounded="lg"
          class="mb-1 menu-item"
          :class="{ 'active-menu-item': isActive(item.to) }"
        >
        </v-list-item>
      </v-list>

      <!-- Bottom Actions -->
      <template v-slot:append>
        <div class="pa-4">
          <v-btn
            v-if="!rail"
            block
            color="white"
            variant="outlined"
            prepend-icon="mdi-headset"
            size="small"
          >
            Contact Support
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- Customer Portal App Bar -->
    <v-app-bar
      app
      elevation="0"
      class="app-bar-glass"
      height="70"
    >
      <v-app-bar-title class="d-flex align-center">
        <v-icon class="mr-2" color="success">{{ currentPageIcon }}</v-icon>
        <span class="text-h6 font-weight-bold">{{ pageTitle }}</span>
      </v-app-bar-title>

      <v-spacer></v-spacer>

      <!-- Wallet Balance Chip -->
      <v-chip color="success" variant="flat" class="mr-4 px-4">
        <v-icon left>mdi-wallet</v-icon>
        <span class="font-weight-bold">₦{{ walletBalance }}</span>
      </v-chip>

      <!-- User Menu -->
      <v-menu offset-y>
        <template v-slot:activator="{ props }">
          <v-btn variant="text" v-bind="props" class="user-menu-btn">
            <v-avatar color="success" size="36" class="mr-2">
              <span class="text-subtitle-2">{{ userInitials }}</span>
            </v-avatar>
            <div class="text-left d-none d-sm-block">
              <div class="text-subtitle-2">{{ authStore.user?.name }}</div>
              <div class="text-caption text-grey">Customer</div>
            </div>
            <v-icon class="ml-2">mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        <v-card min-width="250">
          <v-list>
            <v-list-item>
              <v-list-item-title class="font-weight-bold">{{ authStore.user?.name }}</v-list-item-title>
              <v-list-item-subtitle>{{ authStore.user?.email }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <v-divider></v-divider>
          <v-list density="compact">
            <v-list-item prepend-icon="mdi-account">
              <v-list-item-title>My Profile</v-list-item-title>
            </v-list-item>
            <v-list-item prepend-icon="mdi-cog">
              <v-list-item-title>Settings</v-list-item-title>
            </v-list-item>
          </v-list>
          <v-divider></v-divider>
          <v-list density="compact">
            <v-list-item @click="logout" prepend-icon="mdi-logout" class="text-error">
              <v-list-item-title>Logout</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </v-menu>
    </v-app-bar>

    <!-- Main Content Area -->
    <v-main class="customer-main-content">
      <v-container fluid class="pa-6">
        <transition name="fade" mode="out-in">
          <router-view></router-view>
        </transition>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const drawer = ref(true);
const rail = ref(false);
const walletBalance = ref('0');

const userInitials = computed(() => {
  if (!authStore.user?.name) return 'U';
  return authStore.user.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
});

const menuItems = [
  { title: 'Dashboard', icon: 'mdi-view-dashboard', to: '/customer-portal' },
  { title: 'Place Order', icon: 'mdi-cart-plus', to: '/customer-portal/place-order' },
  { title: 'My Orders', icon: 'mdi-package-variant', to: '/customer-portal/orders' },
  { title: 'Wallet', icon: 'mdi-wallet', to: '/customer-portal/wallet' },
];

const currentPageIcon = computed(() => {
  const item = menuItems.find(i => i.to === route.path);
  return item?.icon || 'mdi-view-dashboard';
});

const pageTitle = computed(() => {
  const item = menuItems.find(i => i.to === route.path);
  return item?.title || 'Dashboard';
});

const isActive = (path) => {
  return route.path === path;
};

const logout = async () => {
  await authStore.logout();
  router.push('/login');
};

const loadWalletBalance = async () => {
  try {
    const response = await axios.get(`/api/wallet/customers/${authStore.user.id}/balance`);
    walletBalance.value = new Intl.NumberFormat('en-NG').format(response.data.balance);
  } catch (error) {
    console.error('Failed to load wallet balance:', error);
  }
};

onMounted(() => {
  loadWalletBalance();
});
</script>

<style scoped>
/* Customer Sidebar Gradient Background */
.customer-sidebar-gradient {
  background: linear-gradient(180deg, #0f9d58 0%, #0b8043 50%, #0f9d58 100%) !important;
  color: white !important;
}

.customer-sidebar-gradient :deep(.v-list-item) {
  color: rgba(255, 255, 255, 0.9) !important;
}

.customer-sidebar-gradient :deep(.v-list-item__prepend .v-icon) {
  color: rgba(255, 255, 255, 0.8) !important;
}

/* Wallet Card in Sidebar */
.wallet-card {
  background: rgba(255, 255, 255, 0.15) !important;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white !important;
}

/* Menu Items */
.menu-item {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  margin-bottom: 4px;
}

.menu-item:hover {
  background: rgba(255, 255, 255, 0.15) !important;
  transform: translateX(4px);
}

.active-menu-item {
  background: rgba(255, 255, 255, 0.2) !important;
  border-left: 4px solid #fff;
  font-weight: 600;
}

/* Glass Morphism App Bar */
.app-bar-glass {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

/* User Menu Button */
.user-menu-btn {
  border-radius: 12px;
  padding: 4px 12px;
  transition: all 0.3s ease;
}

.user-menu-btn:hover {
  background: rgba(0, 0, 0, 0.04);
}

/* Main Content */
.customer-main-content {
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
  min-height: 100vh;
}

/* Fade Transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>

