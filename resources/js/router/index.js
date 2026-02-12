import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('@/views/auth/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('@/components/layouts/DefaultLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                redirect: '/dashboard',
            },
            {
                path: 'dashboard',
                name: 'Dashboard',
                component: () => import('@/views/dashboard/Dashboard.vue'),
            },
            {
                path: 'pos',
                name: 'POS',
                component: () => import('@/views/pos/POSInterface.vue'),
                meta: { permission: 'create_sales' },
            },
            {
                path: 'products',
                name: 'Products',
                component: () => import('@/views/products/ProductList.vue'),
                meta: { permission: 'view_products' },
            },
            {
                path: 'product-units',
                name: 'ProductUnits',
                component: () => import('@/views/products/ProductUnits.vue'),
                meta: { permission: 'manage_products' },
            },
            {
                path: 'inventory',
                name: 'Inventory',
                component: () => import('@/views/inventory/InventoryList.vue'),
                meta: { permission: 'view_inventory' },
            },
            {
                path: 'sales',
                name: 'Sales',
                component: () => import('@/views/sales/SalesList.vue'),
                meta: { permission: 'view_sales' },
            },
            {
                path: 'orders',
                name: 'Orders',
                component: () => import('@/views/orders/OrderList.vue'),
                meta: { permission: 'view_orders' },
            },
            {
                path: 'customers',
                name: 'Customers',
                component: () => import('@/views/customers/CustomerList.vue'),
                meta: { permission: 'view_customers' },
            },
            {
                path: 'suppliers',
                name: 'Suppliers',
                component: () => import('@/views/suppliers/SupplierList.vue'),
                meta: { permission: 'view_suppliers' },
            },
            {
                path: 'purchase-orders',
                name: 'PurchaseOrders',
                component: () => import('@/views/procurement/PurchaseOrderList.vue'),
                meta: { permission: 'view_purchase_orders' },
            },
            {
                path: 'debtors',
                name: 'Debtors',
                component: () => import('@/views/debtors/DebtorsList.vue'),
                meta: { permission: 'view_debtors' },
            },
            {
                path: 'wallet',
                name: 'Wallet',
                component: () => import('@/views/wallet/WalletManagement.vue'),
                meta: { permission: 'view_wallet_transactions' },
            },
            {
                path: 'reports',
                name: 'Reports',
                component: () => import('@/views/reports/ReportsView.vue'),
                meta: { permission: 'view_reports' },
            },
            {
                path: 'analytics/profit-margin',
                name: 'ProfitMarginAnalytics',
                component: () => import('@/views/analytics/ProfitMarginAnalytics.vue'),
                meta: { permission: 'view_reports' },
            },
            {
                path: 'branches',
                name: 'Branches',
                component: () => import('@/views/branches/BranchList.vue'),
                meta: { permission: 'view_branches' },
            },
            {
                path: 'stores',
                name: 'Stores',
                component: () => import('@/views/stores/StoreList.vue'),
                meta: { permission: 'view_stores' },
            },
            {
                path: 'categories',
                name: 'Categories',
                component: () => import('@/views/categories/CategoryList.vue'),
                meta: { permission: 'view_product_categories' },
            },
            {
                path: 'units',
                name: 'Units',
                component: () => import('@/views/units/UnitList.vue'),
                meta: { permission: 'view_units' },
            },
            {
                path: 'users',
                name: 'Users',
                component: () => import('@/views/users/UserList.vue'),
                meta: { permission: 'view_users' },
            },
            {
                path: 'payment-gateways',
                name: 'PaymentGateways',
                component: () => import('@/views/settings/PaymentGatewaySettings.vue'),
                meta: { requiresSuperAdmin: true },
            },
            {
                path: 'app-settings',
                name: 'AppSettings',
                component: () => import('@/views/settings/AppSettings.vue'),
                meta: { requiresSuperAdmin: true },
            },
        ],
    },
    {
        path: '/customer-portal',
        component: () => import('@/components/layouts/CustomerPortalLayout.vue'),
        meta: { requiresAuth: true, customerOnly: true },
        children: [
            {
                path: '',
                name: 'CustomerDashboard',
                component: () => import('@/views/customer-portal/CustomerDashboard.vue'),
            },
            {
                path: 'place-order',
                name: 'PlaceOrder',
                component: () => import('@/views/customer-portal/PlaceOrder.vue'),
            },
            {
                path: 'orders',
                name: 'CustomerOrders',
                component: () => import('@/views/customer-portal/MyOrders.vue'),
            },
            {
                path: 'wallet',
                name: 'CustomerWallet',
                component: () => import('@/views/customer-portal/CustomerWallet.vue'),
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    if (authStore.token && !authStore.user) {
        await authStore.checkAuth();
    }
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'Login' });
    } else if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'Dashboard' });
    } else if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
        next({ name: 'Dashboard' });
    } else {
        next();
    }
});

export default router;
