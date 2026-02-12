<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Customers</h1>
      </v-col>
    </v-row>

    <!-- Filters and Actions -->
    <v-row>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="search"
          label="Search customers..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadCustomers"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filterType"
          :items="customerTypes"
          label="Customer Type"
          variant="outlined"
          density="compact"
          clearable
          @update:model-value="loadCustomers"
        ></v-select>
      </v-col>
      <v-col cols="12" md="3" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" v-if="authStore.hasPermission('create_customers')">
          <v-icon left>mdi-plus</v-icon>
          Add Customer
        </v-btn>
      </v-col>
    </v-row>

    <!-- Customers Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="customers"
        :loading="loading"
      >
        <template v-slot:item.name="{ item }">
          <div>
            <div class="font-weight-bold">{{ item.name }}</div>
            <div class="text-caption text-grey">{{ item.email }}</div>
          </div>
        </template>

        <template v-slot:item.type="{ item }">
          <v-chip :color="getTypeColor(item.type)" size="small">
            {{ item.type === 'walk_in' ? 'Walk-in' : 'Registered' }}
          </v-chip>
        </template>

        <template v-slot:item.category="{ item }">
          <v-chip v-if="item.category" :color="getCategoryColor(item.category)" size="small">
            {{ item.category === 'wholesaler' ? 'Wholesaler' : 'Retailer' }}
          </v-chip>
          <span v-else class="text-grey">-</span>
        </template>

        <template v-slot:item.wallet_balance="{ item }">
          ₦{{ formatNumber(item.wallet_balance || 0) }}
        </template>

        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn icon size="small" @click="viewCustomer(item)" title="View">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="editCustomer(item)"
            title="Edit"
            v-if="authStore.hasPermission('update_customers')"
          >
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="managePricing(item)"
            title="Manage Pricing"
            color="info"
            v-if="authStore.hasPermission('manage_customer_pricing')"
          >
            <v-icon>mdi-currency-usd</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="700px" persistent>
      <v-card>
        <v-card-title>{{ editMode ? 'Edit Customer' : 'New Customer' }}</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.name"
                  label="Name *"
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
                <v-select
                  v-model="formData.customer_type"
                  :items="customerTypes"
                  label="Customer Type *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.category"
                  :items="customerCategories"
                  label="Category"
                  variant="outlined"
                ></v-select>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.address"
                  label="Address"
                  variant="outlined"
                  rows="2"
                ></v-textarea>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="formData.city"
                  label="City"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="formData.state"
                  label="State"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="formData.country"
                  label="Country"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model.number="formData.credit_limit"
                  label="Credit Limit"
                  type="number"
                  min="0"
                  variant="outlined"
                  prefix="₦"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch
                  v-model="formData.is_active"
                  label="Active"
                  color="primary"
                ></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" @click="saveCustomer" :loading="saving">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Pricing Dialog -->
    <v-dialog v-model="pricingDialog" max-width="800px" persistent>
      <v-card v-if="selectedCustomer">
        <v-card-title>Manage Pricing - {{ selectedCustomer.name }}</v-card-title>
        <v-card-text>
          <v-row class="mb-4">
            <v-col cols="12" md="6">
              <ProductSelect
                v-model="pricingData.product_id"
                :items="products"
                item-title="name"
                item-value="id"
                label="Product *"
                variant="outlined"
                @created="product => products.push(product)"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model.number="pricingData.special_price"
                label="Special Price *"
                type="number"
                min="0"
                variant="outlined"
                prefix="₦"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="2">
              <v-btn color="primary" @click="addPricing" :loading="savingPricing">
                Add
              </v-btn>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <h3 class="mb-2">Current Special Pricing</h3>
          <v-table v-if="customerPricing.length > 0">
            <thead>
              <tr>
                <th>Product</th>
                <th>Special Price</th>
                <th>Default Price</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="pricing in customerPricing" :key="pricing.id">
                <td>{{ pricing.product?.name }}</td>
                <td>₦{{ formatNumber(pricing.special_price) }}</td>
                <td>₦{{ formatNumber(pricing.product?.selling_price) }}</td>
                <td>
                  <v-btn icon size="small" color="error" @click="removePricing(pricing)">
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>
          <p v-else class="text-grey">No special pricing configured</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="pricingDialog = false">Close</v-btn>
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
import ProductSelect from '@/components/selects/ProductSelect.vue';
import { useDialog } from '@/composables/useDialog';

const { success, handleError } = useToast();
const { confirm } = useDialog();

const authStore = useAuthStore();
const loading = ref(false);
const saving = ref(false);
const savingPricing = ref(false);
const dialog = ref(false);
const pricingDialog = ref(false);
const editMode = ref(false);
const search = ref('');
const filterType = ref(null);
const customers = ref([]);
const products = ref([]);
const selectedCustomer = ref(null);
const customerPricing = ref([]);

const customerTypes = [
  { title: 'Walk-in', value: 'walk_in' },
  { title: 'Registered', value: 'registered' },
];

const customerCategories = [
  { title: 'General', value: 'general' },
  { title: 'Retailer', value: 'retailer' },
  { title: 'Wholesaler', value: 'wholesaler' },
];

const headers = [
  { title: 'Customer', key: 'name' },
  { title: 'Phone', key: 'phone' },
  { title: 'Type', key: 'type' },
  { title: 'Category', key: 'category' },
  { title: 'Wallet Balance', key: 'wallet_balance' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formData = ref({
  name: '',
  email: '',
  phone: '',
  customer_type: 'registered',
  category: 'retailer',
  address: '',
  city: '',
  state: '',
  country: '',
  credit_limit: 0,
  is_active: true,
});

const pricingData = ref({
  product_id: null,
  special_price: 0,
});

const formatNumber = (num) => new Intl.NumberFormat('en-NG').format(num);

const getTypeColor = (type) => {
  const colors = { walk_in: 'warning', registered: 'primary' };
  return colors[type] || 'default';
};

const getCategoryColor = (category) => {
  const colors = { wholesaler: 'success', retailer: 'info' };
  return colors[category] || 'default';
};

const loadCustomers = async () => {
  loading.value = true;
  try {
    const params = { search: search.value, customer_type: filterType.value };
    const response = await axios.get('/api/customers', { params });
    customers.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load customers:', error);
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
    customer_type: 'registered',
    category: 'retailer',
    address: '',
    city: '',
    state: '',
    country: '',
    credit_limit: 0,
    is_active: true,
  };
  dialog.value = true;
};

const editCustomer = (customer) => {
  editMode.value = true;
  formData.value = { ...customer };
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
};

const saveCustomer = async () => {
  saving.value = true;
  try {
    if (editMode.value) {
      await axios.put(`/api/customers/${formData.value.id}`, formData.value);
      success('Customer updated successfully');
    } else {
      await axios.post('/api/customers', formData.value);
      success('Customer created successfully');
    }
    closeDialog();
    loadCustomers();
  } catch (error) {
    handleError(error, 'Failed to save customer');
  } finally {
    saving.value = false;
  }
};

const viewCustomer = (customer) => {
  console.log('View customer:', customer);
};

const managePricing = async (customer) => {
  selectedCustomer.value = customer;
  try {
    const response = await axios.get(`/api/customers/${customer.id}/pricing`);
    customerPricing.value = response.data.data || response.data;
  } catch (error) {
    handleError(error, 'Failed to load pricing');
  }
  pricingDialog.value = true;
};

const addPricing = async () => {
  if (!pricingData.value.product_id || !pricingData.value.special_price) {
    handleError({ message: 'Please fill all fields' }, 'Please fill all fields');
    return;
  }
  savingPricing.value = true;
  try {
    await axios.post(`/api/customers/${selectedCustomer.value.id}/pricing`, pricingData.value);
    const response = await axios.get(`/api/customers/${selectedCustomer.value.id}/pricing`);
    customerPricing.value = response.data.data || response.data;
    pricingData.value = { product_id: null, special_price: 0 };
    success('Special pricing added successfully');
  } catch (error) {
    handleError(error, 'Failed to add pricing');
  } finally {
    savingPricing.value = false;
  }
};

const removePricing = async (pricing) => {
  const confirmed = await confirm('Remove this special pricing?');
  if (confirmed) {
    try {
      await axios.delete(`/api/customers/${selectedCustomer.value.id}/pricing/${pricing.id}`);
      const response = await axios.get(`/api/customers/${selectedCustomer.value.id}/pricing`);
      customerPricing.value = response.data.data || response.data;
      success('Special pricing removed successfully');
    } catch (error) {
      handleError(error, 'Failed to remove pricing');
    }
  }
};

onMounted(async () => {
  const productsRes = await axios.get('/api/products');
  products.value = productsRes.data.data || productsRes.data;
  loadCustomers();
});
</script>
