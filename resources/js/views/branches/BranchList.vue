<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Branches</h1>
      </v-col>
    </v-row>

    <!-- Filters and Actions -->
    <v-row>
      <v-col cols="12" md="8">
        <v-text-field
          v-model="search"
          label="Search branches..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadBranches"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="4" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" v-if="authStore.hasPermission('create_branches')">
          <v-icon left>mdi-plus</v-icon>
          Add Branch
        </v-btn>
      </v-col>
    </v-row>

    <!-- Branches Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="branches"
        :loading="loading"
      >
        <template v-slot:item.name="{ item }">
          <div>
            <div class="font-weight-bold">{{ item.name }}</div>
            <div class="text-caption text-grey">{{ item.code }}</div>
          </div>
        </template>

        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn icon size="small" @click="viewBranch(item)" title="View">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="editBranch(item)"
            title="Edit"
            v-if="authStore.hasPermission('update_branches')"
          >
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="deleteBranch(item)"
            title="Delete"
            color="error"
            v-if="authStore.hasPermission('delete_branches')"
          >
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="600px" persistent>
      <v-card>
        <v-card-title>{{ editMode ? 'Edit Branch' : 'New Branch' }}</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-text-field
              v-model="formData.name"
              label="Branch Name *"
              variant="outlined"
              :rules="[v => !!v || 'Required']"
            ></v-text-field>
            <v-text-field
              v-model="formData.code"
              label="Branch Code *"
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
            <v-text-field
              v-model="formData.email"
              label="Email"
              type="email"
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
          <v-btn color="primary" @click="saveBranch" :loading="saving">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- View Dialog -->
    <v-dialog v-model="viewDialog" max-width="600px">
      <v-card v-if="selectedBranch">
        <v-card-title class="bg-primary text-white">
          {{ selectedBranch.name }}
          <v-chip :color="selectedBranch.is_active ? 'success' : 'error'" class="ml-2">
            {{ selectedBranch.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </v-card-title>
        <v-card-text class="mt-4">
          <v-row>
            <v-col cols="12">
              <strong>Code:</strong> {{ selectedBranch.code }}
            </v-col>
            <v-col cols="12">
              <strong>Address:</strong> {{ selectedBranch.address || 'N/A' }}
            </v-col>
            <v-col cols="6">
              <strong>Phone:</strong> {{ selectedBranch.phone || 'N/A' }}
            </v-col>
            <v-col cols="6">
              <strong>Email:</strong> {{ selectedBranch.email || 'N/A' }}
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
const branches = ref([]);
const selectedBranch = ref(null);

const headers = [
  { title: 'Branch', key: 'name' },
  { title: 'Address', key: 'address' },
  { title: 'Phone', key: 'phone' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formData = ref({
  name: '',
  code: '',
  address: '',
  phone: '',
  email: '',
  is_active: true,
});

const loadBranches = async () => {
  loading.value = true;
  try {
    const params = { search: search.value };
    const response = await axios.get('/api/branches', { params });
    branches.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load branches:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateDialog = () => {
  editMode.value = false;
  formData.value = {
    name: '',
    code: '',
    address: '',
    phone: '',
    email: '',
    is_active: true,
  };
  dialog.value = true;
};

const editBranch = (branch) => {
  editMode.value = true;
  formData.value = { ...branch };
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
};

const saveBranch = async () => {
  saving.value = true;
  try {
    if (editMode.value) {
      await axios.put(`/api/branches/${formData.value.id}`, formData.value);
    } else {
      await axios.post('/api/branches', formData.value);
    }
    closeDialog();
    loadBranches();
  } catch (error) {
    console.error('Failed to save branch:', error);
    alert('Failed to save branch');
  } finally {
    saving.value = false;
  }
};

const viewBranch = (branch) => {
  selectedBranch.value = branch;
  viewDialog.value = true;
};

const deleteBranch = async (branch) => {
  const confirmed = await confirm(`Are you sure you want to delete ${branch.name}?`);
  if (!confirmed) return;
  try {
    await axios.delete(`/api/branches/${branch.id}`);
    loadBranches();
  } catch (error) {
    console.error('Failed to delete branch:', error);
    alert('Failed to delete branch');
  }
};

onMounted(() => {
  loadBranches();
});
</script>
