<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Stores / Warehouses</h1>
      </v-col>
    </v-row>

    <!-- Filters and Actions -->
    <v-row>
      <v-col cols="12" md="4">
        <v-text-field
          v-model="search"
          label="Search stores..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadStores"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-select
          v-model="filterBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          label="Branch"
          variant="outlined"
          density="compact"
          clearable
          @update:model-value="loadStores"
        ></v-select>
      </v-col>
      <v-col cols="12" md="4" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" v-if="authStore.hasPermission('create_stores')">
          <v-icon left>mdi-plus</v-icon>
          Add Store
        </v-btn>
      </v-col>
    </v-row>

    <!-- Stores Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="stores"
        :loading="loading"
      >
        <template v-slot:item.name="{ item }">
          <div>
            <div class="font-weight-bold">{{ item.name }}</div>
            <div class="text-caption text-grey">{{ item.code }}</div>
          </div>
        </template>

        <template v-slot:item.branch="{ item }">
          {{ item.branch?.name }}
        </template>

        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn icon size="small" @click="viewStore(item)" title="View">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="editStore(item)"
            title="Edit"
            v-if="authStore.hasPermission('update_stores')"
          >
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="deleteStore(item)"
            title="Delete"
            color="error"
            v-if="authStore.hasPermission('delete_stores')"
          >
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="600px" persistent>
      <v-card>
        <v-card-title>{{ editMode ? 'Edit Store' : 'New Store' }}</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-select
              v-model="formData.branch_id"
              :items="branches"
              item-title="name"
              item-value="id"
              label="Branch *"
              variant="outlined"
              :rules="[v => !!v || 'Required']"
            ></v-select>
            <v-text-field
              v-model="formData.name"
              label="Store Name *"
              variant="outlined"
              :rules="[v => !!v || 'Required']"
            ></v-text-field>
            <v-text-field
              v-model="formData.code"
              label="Store Code *"
              variant="outlined"
              :rules="[v => !!v || 'Required']"
            ></v-text-field>
            <v-textarea
              v-model="formData.address"
              label="Address"
              variant="outlined"
              rows="2"
            ></v-textarea>
            <v-text-field
              v-model="formData.phone"
              label="Phone"
              variant="outlined"
            ></v-text-field>
            <v-switch
              v-model="formData.is_active"
              label="Active"
              color="primary"
            ></v-switch>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" @click="saveStore" :loading="saving">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- View Dialog -->
    <v-dialog v-model="viewDialog" max-width="600px">
      <v-card v-if="selectedStore">
        <v-card-title class="bg-primary text-white">
          {{ selectedStore.name }}
          <v-chip :color="selectedStore.is_active ? 'success' : 'error'" class="ml-2">
            {{ selectedStore.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </v-card-title>
        <v-card-text class="mt-4">
          <v-row>
            <v-col cols="12">
              <strong>Code:</strong> {{ selectedStore.code }}
            </v-col>
            <v-col cols="12">
              <strong>Branch:</strong> {{ selectedStore.branch?.name }}
            </v-col>
            <v-col cols="12">
              <strong>Address:</strong> {{ selectedStore.address || 'N/A' }}
            </v-col>
            <v-col cols="12">
              <strong>Phone:</strong> {{ selectedStore.phone || 'N/A' }}
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="viewDialog = false">Close</v-btn>
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
const saving = ref(false);
const dialog = ref(false);
const viewDialog = ref(false);
const editMode = ref(false);
const search = ref('');
const filterBranch = ref(null);
const stores = ref([]);
const branches = ref([]);
const selectedStore = ref(null);

const headers = [
  { title: 'Store', key: 'name' },
  { title: 'Branch', key: 'branch' },
  { title: 'Address', key: 'address' },
  { title: 'Phone', key: 'phone' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formData = ref({
  branch_id: null,
  name: '',
  code: '',
  address: '',
  phone: '',
  is_active: true,
});

const loadStores = async () => {
  loading.value = true;
  try {
    const params = { search: search.value, branch_id: filterBranch.value };
    const response = await axios.get('/api/stores', { params });
    stores.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load stores:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateDialog = () => {
  editMode.value = false;
  formData.value = {
    branch_id: null,
    name: '',
    code: '',
    address: '',
    phone: '',
    is_active: true,
  };
  dialog.value = true;
};

const editStore = (store) => {
  editMode.value = true;
  formData.value = { ...store };
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
};

const saveStore = async () => {
  saving.value = true;
  try {
    if (editMode.value) {
      await axios.put(`/api/stores/${formData.value.id}`, formData.value);
    } else {
      await axios.post('/api/stores', formData.value);
    }
    closeDialog();
    loadStores();
  } catch (error) {
    console.error('Failed to save store:', error);
    alert('Failed to save store');
  } finally {
    saving.value = false;
  }
};

const viewStore = (store) => {
  selectedStore.value = store;
  viewDialog.value = true;
};

const deleteStore = async (store) => {
  const confirmed = await confirm(`Are you sure you want to delete ${store.name}?`);
  if (!confirmed) return;
  try {
    await axios.delete(`/api/stores/${store.id}`);
    loadStores();
  } catch (error) {
    console.error('Failed to delete store:', error);
    alert('Failed to delete store');
  }
};

onMounted(async () => {
  const branchesRes = await axios.get('/api/branches');
  branches.value = branchesRes.data.data || branchesRes.data;
  loadStores();
});
</script>
