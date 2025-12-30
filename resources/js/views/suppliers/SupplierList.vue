<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Suppliers</h1>
      </v-col>
    </v-row>

    <!-- Filters and Actions -->
    <v-row>
      <v-col cols="12" md="8">
        <v-text-field
          v-model="search"
          label="Search suppliers..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadSuppliers"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="4" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" v-if="authStore.hasPermission('create_suppliers')">
          <v-icon left>mdi-plus</v-icon>
          Add Supplier
        </v-btn>
      </v-col>
    </v-row>

    <!-- Suppliers Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="suppliers"
        :loading="loading"
      >
        <template v-slot:item.name="{ item }">
          <div>
            <div class="font-weight-bold">{{ item.name }}</div>
            <div class="text-caption text-grey">{{ item.email }}</div>
          </div>
        </template>

        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn icon size="small" @click="viewSupplier(item)" title="View">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="editSupplier(item)"
            title="Edit"
            v-if="authStore.hasPermission('edit_suppliers')"
          >
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="deleteSupplier(item)"
            title="Delete"
            color="error"
            v-if="authStore.hasPermission('delete_suppliers')"
          >
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="700px" persistent>
      <v-card>
        <v-card-title>{{ editMode ? 'Edit Supplier' : 'New Supplier' }}</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.name"
                  label="Supplier Name *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.email"
                  label="Email *"
                  type="email"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.phone"
                  label="Phone *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.contact_person"
                  label="Contact Person"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.address"
                  label="Address"
                  variant="outlined"
                  rows="2"
                ></v-textarea>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.tax_id"
                  label="Tax ID"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.bank_account"
                  label="Bank Account"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.payment_terms_days"
                  label="Payment Terms (Days)"
                  type="number"
                  min="0"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch
                  v-model="formData.is_active"
                  label="Active"
                  color="primary"
                ></v-switch>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.notes"
                  label="Notes"
                  variant="outlined"
                  rows="2"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" @click="saveSupplier" :loading="saving">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- View Dialog -->
    <v-dialog v-model="viewDialog" max-width="700px">
      <v-card v-if="selectedSupplier">
        <v-card-title class="bg-primary text-white">
          {{ selectedSupplier.name }}
          <v-chip :color="selectedSupplier.is_active ? 'success' : 'error'" class="ml-2">
            {{ selectedSupplier.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </v-card-title>
        <v-card-text class="mt-4">
          <v-row>
            <v-col cols="6">
              <strong>Email:</strong> {{ selectedSupplier.email }}
            </v-col>
            <v-col cols="6">
              <strong>Phone:</strong> {{ selectedSupplier.phone }}
            </v-col>
            <v-col cols="6">
              <strong>Contact Person:</strong> {{ selectedSupplier.contact_person || 'N/A' }}
            </v-col>
            <v-col cols="6">
              <strong>Payment Terms:</strong> {{ selectedSupplier.payment_terms_days || 0 }} days
            </v-col>
            <v-col cols="12">
              <strong>Address:</strong> {{ selectedSupplier.address || 'N/A' }}
            </v-col>
            <v-col cols="6">
              <strong>Tax ID:</strong> {{ selectedSupplier.tax_id || 'N/A' }}
            </v-col>
            <v-col cols="6">
              <strong>Bank Account:</strong> {{ selectedSupplier.bank_account || 'N/A' }}
            </v-col>
            <v-col cols="12" v-if="selectedSupplier.notes">
              <strong>Notes:</strong>
              <p>{{ selectedSupplier.notes }}</p>
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

const authStore = useAuthStore();
const loading = ref(false);
const saving = ref(false);
const dialog = ref(false);
const viewDialog = ref(false);
const editMode = ref(false);
const search = ref('');
const suppliers = ref([]);
const selectedSupplier = ref(null);

const headers = [
  { title: 'Supplier', key: 'name' },
  { title: 'Phone', key: 'phone' },
  { title: 'Contact Person', key: 'contact_person' },
  { title: 'Payment Terms', key: 'payment_terms_days' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formData = ref({
  name: '',
  email: '',
  phone: '',
  contact_person: '',
  address: '',
  tax_id: '',
  bank_account: '',
  payment_terms_days: 30,
  is_active: true,
  notes: '',
});

const loadSuppliers = async () => {
  loading.value = true;
  try {
    const params = { search: search.value };
    const response = await axios.get('/api/suppliers', { params });
    suppliers.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load suppliers:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateDialog = () => {
  editMode.value = false;
  formData.value = {
    name: '',
    email: '',
    phone: '',
    contact_person: '',
    address: '',
    tax_id: '',
    bank_account: '',
    payment_terms_days: 30,
    is_active: true,
    notes: '',
  };
  dialog.value = true;
};

const editSupplier = (supplier) => {
  editMode.value = true;
  formData.value = { ...supplier };
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
};

const saveSupplier = async () => {
  saving.value = true;
  try {
    if (editMode.value) {
      await axios.put(`/api/suppliers/${formData.value.id}`, formData.value);
    } else {
      await axios.post('/api/suppliers', formData.value);
    }
    closeDialog();
    loadSuppliers();
  } catch (error) {
    console.error('Failed to save supplier:', error);
    alert('Failed to save supplier');
  } finally {
    saving.value = false;
  }
};

const viewSupplier = (supplier) => {
  selectedSupplier.value = supplier;
  viewDialog.value = true;
};

const deleteSupplier = async (supplier) => {
  if (confirm(`Are you sure you want to delete ${supplier.name}?`)) {
    try {
      await axios.delete(`/api/suppliers/${supplier.id}`);
      loadSuppliers();
    } catch (error) {
      console.error('Failed to delete supplier:', error);
      alert('Failed to delete supplier');
    }
  }
};

onMounted(() => {
  loadSuppliers();
});
</script>

