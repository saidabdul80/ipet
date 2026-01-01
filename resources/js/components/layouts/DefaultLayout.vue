<template>
  <v-app>
    <!-- Modern Sidebar with Gradient -->
    <v-navigation-drawer
      v-model="drawer"
      app
      :rail="rail"
      permanent
      class="sidebar-gradient"
      :width="280"
    >
      <!-- Logo Section -->
      <div class="pa-4 d-flex align-center justify-space-between">
        <div class="d-flex align-center" v-if="!rail">
          <!-- Custom Logo with Circular White Wrapper -->
          <div v-if="appSettingsStore.logoUrl" class="logo-wrapper mr-3">
            <img
              :src="appSettingsStore.logoUrl"
              :alt="appSettingsStore.appName"
              class="sidebar-logo-img"
            />
          </div>
          <v-avatar v-else color="white" size="48" class="mr-3">
            <v-icon color="primary" size="28">mdi-package-variant</v-icon>
          </v-avatar>
        
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

      <!-- User Profile Card -->
     

      <!-- Navigation Menu -->
      <v-list density="compact" nav class="px-2">
        <template v-for="(section, index) in menuSections" :key="index">
          <v-list-subheader v-if="!rail && section.title" class="text-grey-lighten-1 text-uppercase text-caption font-weight-bold mt-2">
            {{ section.title }}
          </v-list-subheader>

          <v-list-item
            v-for="item in section.items"
            :key="item.title"
            :to="item.to"
            :prepend-icon="item.icon"
            :title="item.title"
            rounded="lg"
            class="mb-1 menu-item"
            :class="{ 'active-menu-item': isActive(item.to) }"
          >
            <template v-slot:append v-if="item.badge && !rail">
              <v-chip size="x-small" :color="item.badgeColor || 'error'" variant="flat">
                {{ item.badge }}
              </v-chip>
            </template>
          </v-list-item>
        </template>
      </v-list>

      <!-- User Profile Card -->
      <div class="pa-4" v-if="!rail">
        <v-card class="user-card" elevation="0">
          <v-card-text class="pa-3">
            <div class="d-flex align-center">
              <v-avatar color="primary" size="48" class="mr-3">
                <span class="text-h6">{{ userInitials }}</span>
              </v-avatar>
              <div class="flex-grow-1">
                <div class="text-subtitle-2 font-weight-bold">{{ user?.name }}</div>
                <v-chip size="x-small" color="success" variant="flat" class="mt-1">
                  {{ userRole }}
                </v-chip>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </div>
      <!-- Bottom Actions -->
      <template v-slot:append>
        <div class="pa-4">
          <v-btn
            v-if="!rail"
            block
            color="white"
            variant="outlined"
            prepend-icon="mdi-help-circle"
            size="small"
          >
            Help & Support
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- Modern App Bar -->
    <v-app-bar
      app
      elevation="0"
      class="app-bar-glass"
      height="70"
    >
      <v-app-bar-title class="d-flex align-center">
        <v-icon class="mr-2" color="primary">{{ currentPageIcon }}</v-icon>
        <span class="text-h6 font-weight-bold">{{ pageTitle }}</span>
      </v-app-bar-title>

      <v-spacer></v-spacer>

      <!-- Search Bar -->
      <v-text-field
        v-model="searchQuery"
        prepend-inner-icon="mdi-magnify"
        placeholder="Search anything..."
        variant="solo"
        density="compact"
        hide-details
        class="search-field mr-4"
        style="max-width: 400px;"
      ></v-text-field>

      <!-- Quick Actions -->
      <v-btn icon variant="text" class="mr-2">
        <v-icon>mdi-apps</v-icon>
      </v-btn>

      <!-- Notifications -->
      <v-menu offset-y>
        <template v-slot:activator="{ props }">
          <v-btn icon variant="text" v-bind="props" class="mr-2">
            <v-badge color="error" content="3" offset-x="10" offset-y="10">
              <v-icon>mdi-bell</v-icon>
            </v-badge>
          </v-btn>
        </template>
        <v-card min-width="350" max-width="400">
          <v-card-title class="d-flex align-center justify-space-between">
            <span>Notifications</span>
            <v-chip size="small" color="primary">3 New</v-chip>
          </v-card-title>
          <v-divider></v-divider>
          <v-list>
            <v-list-item
              v-for="n in 3"
              :key="n"
              :title="`Notification ${n}`"
              :subtitle="`This is notification message ${n}`"
              prepend-icon="mdi-information"
            ></v-list-item>
          </v-list>
        </v-card>
      </v-menu>

      <!-- User Menu -->
      <v-menu offset-y>
        <template v-slot:activator="{ props }">
          <v-btn variant="text" v-bind="props" class="user-menu-btn">
            <v-avatar color="primary" size="36" class="mr-2">
              <span class="text-subtitle-2">{{ userInitials }}</span>
            </v-avatar>
            <div class="text-left d-none d-sm-block">
              <div class="text-subtitle-2">{{ user?.name }}</div>
              <div class="text-caption text-grey">{{ userRole }}</div>
            </div>
            <v-icon class="ml-2">mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        <v-card min-width="250">
          <v-list>
            <v-list-item>
              <v-list-item-title class="font-weight-bold">{{ user?.name }}</v-list-item-title>
              <v-list-item-subtitle>{{ user?.email }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <v-divider></v-divider>
          <v-list density="compact">
            <v-list-item to="/profile" prepend-icon="mdi-account">
              <v-list-item-title>My Profile</v-list-item-title>
            </v-list-item>
            <v-list-item prepend-icon="mdi-cog">
              <v-list-item-title>Settings</v-list-item-title>
            </v-list-item>
            <v-list-item prepend-icon="mdi-theme-light-dark">
              <v-list-item-title>Dark Mode</v-list-item-title>
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
    <v-main class="main-content">
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
import { useAppSettingsStore } from '@/stores/appSettings';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const appSettingsStore = useAppSettingsStore();

const drawer = ref(true);
const rail = ref(false);
const searchQuery = ref('');
const user = computed(() => authStore.user);

const userInitials = computed(() => {
  if (!user.value?.name) return 'U';
  return user.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
});

const userRole = computed(() => {
  if (authStore.roles && authStore.roles.length > 0) {
    return authStore.roles[0];
  }
  return 'User';
});

// Organized menu structure with sections
const allMenuItems = [
  // Main Section
  {
    title: 'Main',
    items: [
      { title: 'Dashboard', icon: 'mdi-view-dashboard', to: '/dashboard', permission: null },
      { title: 'POS', icon: 'mdi-cash-register', to: '/pos', permission: 'create_sales', badge: 'New', badgeColor: 'success' },
    ]
  },
  // Sales & Orders
  {
    title: 'Sales & Orders',
    items: [
      { title: 'Sales', icon: 'mdi-cart', to: '/sales', permission: 'view_sales' },
      { title: 'Orders', icon: 'mdi-clipboard-list', to: '/orders', permission: 'view_orders' },
      { title: 'Customers', icon: 'mdi-account-group', to: '/customers', permission: 'view_customers' },
    ]
  },
  // Inventory
  {
    title: 'Inventory',
    items: [
      { title: 'Products', icon: 'mdi-package-variant', to: '/products', permission: 'view_products' },
      { title: 'Inventory', icon: 'mdi-warehouse', to: '/inventory', permission: 'view_inventory' },
      { title: 'Categories', icon: 'mdi-tag-multiple', to: '/categories', permission: 'view_product_categories' },
      { title: 'Units', icon: 'mdi-ruler', to: '/units', permission: 'view_units' },
    ]
  },
  // Procurement
  {
    title: 'Procurement',
    items: [
      { title: 'Purchase Orders', icon: 'mdi-file-document', to: '/purchase-orders', permission: 'view_purchase_orders' },
      { title: 'Suppliers', icon: 'mdi-truck', to: '/suppliers', permission: 'view_suppliers' },
    ]
  },
  // Finance
  {
    title: 'Finance',
    items: [
      { title: 'Debtors', icon: 'mdi-account-cash', to: '/debtors', permission: 'view_debtors' },
      { title: 'Wallet', icon: 'mdi-wallet', to: '/wallet', permission: 'view_wallet_transactions' },
      { title: 'Reports', icon: 'mdi-chart-bar', to: '/reports', permission: 'view_reports' },
    ]
  },
  // Analytics
  {
    title: 'Analytics',
    items: [
      { title: 'Profit Margin', icon: 'mdi-chart-line', to: '/analytics/profit-margin', permission: 'view_reports' },
    ]
  },
  // Settings
  {
    title: 'Settings',
    items: [
      { title: 'Users', icon: 'mdi-account-multiple', to: '/users', permission: 'view_users' },
      { title: 'Branches', icon: 'mdi-office-building', to: '/branches', permission: 'view_branches' },
      { title: 'Stores', icon: 'mdi-store', to: '/stores', permission: 'view_stores' },
      { title: 'Payment Gateways', icon: 'mdi-credit-card-settings', to: '/payment-gateways', superAdminOnly: true },
      { title: 'App Settings', icon: 'mdi-cog', to: '/app-settings', superAdminOnly: true },
    ]
  },
];

const menuSections = computed(() => {
  return allMenuItems.map(section => ({
    ...section,
    items: section.items.filter(item => {
      // Check super admin only items
      if (item.superAdminOnly && !authStore.isSuperAdmin) return false;
      // Check permission
      if (!item.permission) return true;
      return authStore.hasPermission(item.permission);
    })
  })).filter(section => section.items.length > 0);
});

const currentPageIcon = computed(() => {
  for (const section of allMenuItems) {
    const item = section.items.find(i => i.to === route.path);
    if (item) return item.icon;
  }
  return 'mdi-view-dashboard';
});

const pageTitle = computed(() => {
  for (const section of allMenuItems) {
    const item = section.items.find(i => i.to === route.path);
    if (item) return item.title;
  }
  return 'Dashboard';
});

const isActive = (path) => {
  return route.path === path;
};

const logout = async () => {
  await authStore.logout();
  router.push('/login');
};

// Load app settings on mount
onMounted(async () => {
  await appSettingsStore.loadSettings();
  appSettingsStore.updateDocumentTitle(pageTitle.value);
});
</script>

<style scoped>
/* Sidebar Logo with Circular White Wrapper */
.logo-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background-color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);  
  

}

.sidebar-logo-img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

/* Sidebar Gradient Background - Dynamic colors from settings */
.sidebar-gradient {
  background: linear-gradient(180deg,
    v-bind('appSettingsStore.primaryColor') 0%,
    v-bind('appSettingsStore.settings.secondary_color') 50%,
    v-bind('appSettingsStore.primaryColor') 100%) !important;
  color: white !important;
}

.sidebar-gradient :deep(.v-list-item) {
  color: rgba(255, 255, 255, 0.9) !important;
}

.sidebar-gradient :deep(.v-list-item__prepend .v-icon) {
  color: rgba(255, 255, 255, 0.8) !important;
}

/* User Card in Sidebar */
.user-card {
  background: rgba(255, 255, 255, 0.1) !important;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
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

.active-menu-item :deep(.v-list-item__prepend .v-icon) {
  color: #fff !important;
}

/* Glass Morphism App Bar */
.app-bar-glass {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

/* Search Field */
.search-field :deep(.v-field) {
  background: rgba(0, 0, 0, 0.02);
  border-radius: 12px;
  box-shadow: none;
}

.search-field :deep(.v-field:hover) {
  background: rgba(0, 0, 0, 0.04);
}

.search-field :deep(.v-field--focused) {
  background: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
.main-content {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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

/* Scrollbar Styling */
:deep(.v-navigation-drawer__content)::-webkit-scrollbar {
  width: 6px;
}

:deep(.v-navigation-drawer__content)::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
}

:deep(.v-navigation-drawer__content)::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

:deep(.v-navigation-drawer__content)::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

/* Elevation and Shadows */
.v-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.v-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
}

/* Badge Animations */
.v-badge :deep(.v-badge__badge) {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}
</style>

