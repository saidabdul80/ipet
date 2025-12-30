<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">User Management</h1>
      </v-col>
    </v-row>

    <!-- Filters and Actions -->
    <v-row>
      <v-col cols="12" md="4">
        <v-text-field
          v-model="search"
          label="Search users..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadUsers"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filterRole"
          :items="roles"
          item-title="name"
          item-value="name"
          label="Role"
          variant="outlined"
          density="compact"
          clearable
          @update:model-value="loadUsers"
        ></v-select>
      </v-col>
      <v-col cols="12" md="2">
        <v-select
          v-model="filterStatus"
          :items="[{ title: 'Active', value: true }, { title: 'Inactive', value: false }]"
          label="Status"
          variant="outlined"
          density="compact"
          clearable
          @update:model-value="loadUsers"
        ></v-select>
      </v-col>
      <v-col cols="12" md="3" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" v-if="authStore.hasPermission('create_users')">
          <v-icon left>mdi-plus</v-icon>
          Add User
        </v-btn>
      </v-col>
    </v-row>

    <!-- Users Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="users"
        :loading="loading"
        class="elevation-1"
      >
        <template v-slot:item.roles="{ item }">
          <v-chip v-for="role in item.roles" :key="role.id" size="small" color="primary" class="mr-1">
            {{ role.name }}
          </v-chip>
        </template>

        <template v-slot:item.branch="{ item }">
          <div v-if="item.branches && item.branches.length > 0">
            <v-chip v-for="branch in item.branches" :key="branch.id" size="small" class="mr-1 mb-1">
              {{ branch.name }}
            </v-chip>
          </div>
          <div v-else-if="item.branch">
            <v-chip size="small">{{ item.branch.name }}</v-chip>
          </div>
          <span v-else class="text-grey">All Branches</span>
        </template>

        <template v-slot:item.store="{ item }">
          <div v-if="item.stores && item.stores.length > 0">
            <v-chip v-for="store in item.stores" :key="store.id" size="small" class="mr-1 mb-1">
              {{ store.name }}
            </v-chip>
          </div>
          <div v-else-if="item.store">
            <v-chip size="small">{{ item.store.name }}</v-chip>
          </div>
          <span v-else class="text-grey">All Stores</span>
        </template>

        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn
            icon
            size="small"
            @click="editUser(item)"
            title="Edit"
            v-if="authStore.hasPermission('update_users')"
          >
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="deleteUser(item)"
            title="Delete"
            color="error"
            v-if="authStore.hasPermission('delete_users') && item.id !== authStore.user.id"
          >
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="600px" persistent>
      <v-card>
        <v-card-title>
          <span class="text-h5">{{ editMode ? 'Edit User' : 'Create User' }}</span>
        </v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-text-field
              v-model="formData.name"
              label="Full Name"
              variant="outlined"
              density="compact"
              required
            ></v-text-field>

            <v-text-field
              v-model="formData.email"
              label="Email"
              type="email"
              variant="outlined"
              density="compact"
              required
            ></v-text-field>

            <v-text-field
              v-model="formData.password"
              :label="editMode ? 'Password (leave blank to keep current)' : 'Password'"
              type="password"
              variant="outlined"
              density="compact"
              :required="!editMode"
            ></v-text-field>

            <v-select
              v-model="formData.role"
              :items="roles"
              item-title="name"
              item-value="name"
              label="Role"
              variant="outlined"
              density="compact"
              required
            ></v-select>

            <v-select
              v-model="formData.branch_id"
              :items="branches"
              item-title="name"
              item-value="id"
              label="Primary Branch (Optional)"
              variant="outlined"
              density="compact"
              clearable
              hint="Primary branch for the user"
              persistent-hint
            ></v-select>

            <v-select
              v-model="formData.branch_ids"
              :items="branches"
              item-title="name"
              item-value="id"
              label="Additional Branches (Optional)"
              variant="outlined"
              density="compact"
              multiple
              chips
              clearable
              hint="User can access these branches"
              persistent-hint
              class="mt-4"
            ></v-select>

            <v-select
              v-model="formData.store_id"
              :items="stores"
              item-title="name"
              item-value="id"
              label="Primary Store (Optional)"
              variant="outlined"
              density="compact"
              clearable
              hint="Primary store for POS and operations"
              persistent-hint
              class="mt-4"
            ></v-select>

            <v-select
              v-model="formData.store_ids"
              :items="stores"
              item-title="name"
              item-value="id"
              label="Additional Stores (Optional)"
              variant="outlined"
              density="compact"
              multiple
              chips
              clearable
              hint="User can access these stores"
              persistent-hint
              class="mt-4"
            ></v-select>

            <v-switch
              v-model="formData.is_active"
              label="Active"
              color="primary"
            ></v-switch>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" @click="saveUser" :loading="saving">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import axios from 'axios';

const authStore = useAuthStore();
const { success, handleError } = useToast();

const loading = ref(false);
const saving = ref(false);
const dialog = ref(false);
const editMode = ref(false);
const search = ref('');
const filterRole = ref(null);
const filterStatus = ref(null);
const users = ref([]);
const roles = ref([]);
const branches = ref([]);
const stores = ref([]);

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'roles' },
  { title: 'Branch', key: 'branch' },
  { title: 'Store', key: 'store' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formData = ref({
  name: '',
  email: '',
  password: '',
  role: '',
  branch_id: null,
  store_id: null,
  branch_ids: [],
  store_ids: [],
  is_active: true,
});

const loadUsers = async () => {
  loading.value = true;
  try {
    const params = {
      search: search.value,
      role: filterRole.value,
      is_active: filterStatus.value,
    };
    const response = await axios.get('/api/users', { params });
    users.value = response.data.data || response.data;
  } catch (error) {
    handleError(error, 'Failed to load users');
  } finally {
    loading.value = false;
  }
};

const loadRoles = async () => {
  try {
    const response = await axios.get('/api/roles');
    roles.value = response.data;
  } catch (error) {
    console.error('Failed to load roles:', error);
  }
};

const loadBranches = async () => {
  try {
    const response = await axios.get('/api/branches', { params: { per_page: 100 } });
    branches.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load branches:', error);
  }
};

const loadStores = async () => {
  try {
    const response = await axios.get('/api/stores', { params: { per_page: 100 } });
    stores.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load stores:', error);
  }
};

const openCreateDialog = () => {
  editMode.value = false;
  formData.value = {
    name: '',
    email: '',
    password: '',
    role: '',
    branch_id: null,
    store_id: null,
    branch_ids: [],
    store_ids: [],
    is_active: true,
  };
  dialog.value = true;
};

const editUser = (user) => {
  editMode.value = true;
  formData.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    password: '',
    role: user.roles[0]?.name || '',
    branch_id: user.branch_id,
    store_id: user.store_id,
    branch_ids: user.branches?.map(b => b.id) || [],
    store_ids: user.stores?.map(s => s.id) || [],
    is_active: user.is_active,
  };
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
};

const saveUser = async () => {
  saving.value = true;
  try {
    const payload = { ...formData.value };
    if (editMode.value && !payload.password) {
      delete payload.password;
    }

    if (editMode.value) {
      await axios.put(`/api/users/${formData.value.id}`, payload);
      success('User updated successfully');
    } else {
      await axios.post('/api/users', payload);
      success('User created successfully');
    }
    closeDialog();
    loadUsers();
  } catch (error) {
    handleError(error, 'Failed to save user');
  } finally {
    saving.value = false;
  }
};

const deleteUser = async (user) => {
  if (!confirm(`Are you sure you want to delete ${user.name}?`)) return;

  try {
    await axios.delete(`/api/users/${user.id}`);
    success('User deleted successfully');
    loadUsers();
  } catch (error) {
    handleError(error, 'Failed to delete user');
  }
};

onMounted(async () => {
  await Promise.all([loadUsers(), loadRoles(), loadBranches(), loadStores()]);
});
</script>


