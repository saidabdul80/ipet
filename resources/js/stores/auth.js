import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(localStorage.getItem('token') || null);
    const permissions = ref([]);
    const roles = ref([]);

    const isAuthenticated = computed(() => !!token.value);
    const isSuperAdmin = computed(() => roles.value.includes('Super Admin'));
    const isCustomer = computed(() => roles.value.includes('Customer'));

    function setToken(newToken) {
        token.value = newToken;
        if (newToken) {
            localStorage.setItem('token', newToken);
            axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
        } else {
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
        }
    }

    async function login(credentials) {
        try {
            const response = await axios.post('/api/login', credentials);
            const { token: authToken, user: userData } = response.data;

            setToken(authToken);
            user.value = userData;

            // Handle permissions - can be array or collection
            if (Array.isArray(userData.permissions)) {
                permissions.value = userData.permissions;
            } else if (userData.permissions && typeof userData.permissions === 'object') {
                permissions.value = Object.values(userData.permissions);
            } else {
                permissions.value = [];
            }

            // Handle roles - can be array or collection
            if (Array.isArray(userData.roles)) {
                roles.value = userData.roles;
            } else if (userData.roles && typeof userData.roles === 'object') {
                roles.value = Object.values(userData.roles);
            } else {
                roles.value = [];
            }

            console.log('Login successful:', {
                user: userData.name,
                roles: roles.value,
                permissions: permissions.value,
                isSuperAdmin: roles.value.includes('Super Admin')
            });

            return { success: true };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Login failed',
            };
        }
    }

    async function logout() {
        try {
            await axios.post('/api/logout');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            setToken(null);
            user.value = null;
            permissions.value = [];
            roles.value = [];
        }
    }

    async function checkAuth() {
        if (!token.value) return;

        try {
            const response = await axios.get('/api/user');
            const userData = response.data.user;
            user.value = userData;

            // Handle permissions - can be array or collection
            if (Array.isArray(userData.permissions)) {
                permissions.value = userData.permissions;
            } else if (userData.permissions && typeof userData.permissions === 'object') {
                permissions.value = Object.values(userData.permissions);
            } else {
                permissions.value = [];
            }

            // Handle roles - can be array or collection
            if (Array.isArray(userData.roles)) {
                roles.value = userData.roles;
            } else if (userData.roles && typeof userData.roles === 'object') {
                roles.value = Object.values(userData.roles);
            } else {
                roles.value = [];
            }
        } catch (error) {
            setToken(null);
            user.value = null;
            permissions.value = [];
            roles.value = [];
        }
    }

    function hasPermission(permission) {
        if (isSuperAdmin.value) return true;
        return permissions.value.includes(permission);
    }

    function hasRole(role) {
        return roles.value.includes(role);
    }

    function canAccessBranch(branchId) {
        if (isSuperAdmin.value) return true;

        // Check primary branch
        if (user.value?.branch_id === branchId) return true;

        // Check additional branches
        if (user.value?.branches && Array.isArray(user.value.branches)) {
            return user.value.branches.some(branch => branch.id === branchId);
        }

        return false;
    }

    function canAccessStore(storeId) {
        if (isSuperAdmin.value) return true;

        // Check primary store
        if (user.value?.store_id === storeId) return true;

        // Check additional stores
        if (user.value?.stores && Array.isArray(user.value.stores)) {
            if (user.value.stores.some(store => store.id === storeId)) {
                return true;
            }
        }

        // Branch managers can access all stores in their branches
        if (hasRole('Branch Manager')) {
            // Would need to check if store belongs to user's accessible branches
            // For now, return true for branch managers
            return true;
        }

        return false;
    }

    // Get all accessible branch IDs
    function getAccessibleBranchIds() {
        if (isSuperAdmin.value) {
            // Super admin has access to all - return empty array to indicate "all"
            return null;
        }

        const branchIds = [];

        if (user.value?.branch_id) {
            branchIds.push(user.value.branch_id);
        }

        if (user.value?.branches && Array.isArray(user.value.branches)) {
            user.value.branches.forEach(branch => {
                if (!branchIds.includes(branch.id)) {
                    branchIds.push(branch.id);
                }
            });
        }

        return branchIds;
    }

    // Get all accessible store IDs
    function getAccessibleStoreIds() {
        if (isSuperAdmin.value) {
            // Super admin has access to all - return empty array to indicate "all"
            return null;
        }

        const storeIds = [];

        if (user.value?.store_id) {
            storeIds.push(user.value.store_id);
        }

        if (user.value?.stores && Array.isArray(user.value.stores)) {
            user.value.stores.forEach(store => {
                if (!storeIds.includes(store.id)) {
                    storeIds.push(store.id);
                }
            });
        }

        return storeIds;
    }

    // Initialize axios with token if it exists
    if (token.value) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
    }

    return {
        user,
        token,
        permissions,
        roles,
        isAuthenticated,
        isSuperAdmin,
        isCustomer,
        login,
        logout,
        checkAuth,
        hasPermission,
        hasRole,
        canAccessBranch,
        canAccessStore,
        getAccessibleBranchIds,
        getAccessibleStoreIds,
    };
});

