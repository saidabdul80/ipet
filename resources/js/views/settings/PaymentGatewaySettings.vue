<template>
  <v-container fluid class="pa-6">
    <!-- Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold mb-2">Payment Gateway Settings</h1>
        <p class="text-subtitle-1 text-grey-darken-1">Configure payment gateways for online wallet funding</p>
      </div>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        @click="openDialog()"
        size="large"
      >
        Add Gateway
      </v-btn>
    </div>

    <!-- Gateways List -->
    <v-row>
      <v-col
        v-for="gateway in gateways"
        :key="gateway.id"
        cols="12"
        md="6"
        lg="4"
      >
        <v-card
          :class="['gateway-card', { 'active-gateway': gateway.is_active, 'default-gateway': gateway.is_default }]"
          elevation="2"
        >
          <v-card-title class="d-flex justify-space-between align-center">
            <div class="d-flex align-center">
              <v-icon :color="gateway.is_active ? 'success' : 'grey'" class="mr-2">
                {{ getGatewayIcon(gateway.driver) }}
              </v-icon>
              <span>{{ gateway.display_name }}</span>
            </div>
            <v-chip
              v-if="gateway.is_default"
              color="primary"
              size="small"
              variant="flat"
            >
              Default
            </v-chip>
          </v-card-title>

          <v-card-text>
            <p class="text-body-2 mb-3">{{ gateway.description }}</p>
            
            <div class="mb-2">
              <v-chip
                :color="gateway.is_active ? 'success' : 'error'"
                size="small"
                variant="tonal"
              >
                {{ gateway.is_active ? 'Active' : 'Inactive' }}
              </v-chip>
            </div>

            <div class="text-caption text-grey-darken-1">
              <div><strong>Currency:</strong> {{ gateway.currency }}</div>
              <div><strong>Channels:</strong> {{ gateway.supported_channels?.join(', ') || 'N/A' }}</div>
            </div>
          </v-card-text>

          <v-card-actions>
            <v-btn
              size="small"
              variant="text"
              @click="openDialog(gateway)"
            >
              <v-icon start>mdi-pencil</v-icon>
              Edit
            </v-btn>
            <v-btn
              size="small"
              variant="text"
              :color="gateway.is_active ? 'error' : 'success'"
              @click="toggleGateway(gateway)"
            >
              <v-icon start>{{ gateway.is_active ? 'mdi-pause' : 'mdi-play' }}</v-icon>
              {{ gateway.is_active ? 'Deactivate' : 'Activate' }}
            </v-btn>
            <v-btn
              v-if="!gateway.is_default && gateway.is_active"
              size="small"
              variant="text"
              color="primary"
              @click="setDefault(gateway)"
            >
              <v-icon start>mdi-star</v-icon>
              Set Default
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- Gateway Dialog -->
    <v-dialog v-model="dialog" max-width="800px" persistent>
      <v-card>
        <v-card-title class="bg-primary text-white">
          <span class="text-h5">{{ editMode ? 'Edit' : 'Add' }} Payment Gateway</span>
        </v-card-title>

        <v-card-text class="pt-6">
          <v-form ref="form" v-model="valid">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.display_name"
                  label="Display Name"
                  :rules="[v => !!v || 'Display name is required']"
                  required
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.driver"
                  :items="['paystack', 'monnify', 'palmpay']"
                  label="Gateway Driver"
                  :rules="[v => !!v || 'Driver is required']"
                  :disabled="editMode"
                  required
                />
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="formData.description"
                  label="Description"
                  rows="2"
                />
              </v-col>

              <v-col cols="12">
                <h3 class="text-h6 mb-3">Credentials</h3>
              </v-col>

              <!-- Paystack Credentials -->
              <template v-if="formData.driver === 'paystack'">
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="formData.credentials.public_key"
                    label="Public Key"
                    :rules="[v => !!v || 'Public key is required']"
                    required
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="formData.credentials.secret_key"
                    label="Secret Key"
                    type="password"
                    :rules="[v => !!v || 'Secret key is required']"
                    required
                  />
                </v-col>
              </template>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn color="grey" variant="text" @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" variant="flat" @click="saveGateway" :loading="saving">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const gateways = ref([]);
const dialog = ref(false);
const editMode = ref(false);
const valid = ref(false);
const saving = ref(false);
const form = ref(null);

const formData = ref({
  display_name: '',
  driver: 'paystack',
  description: '',
  credentials: {
    public_key: '',
    secret_key: '',
  },
  currency: 'NGN',
  supported_channels: [],
  is_active: true,
  is_default: false,
  priority: 0,
});

const fetchGateways = async () => {
  try {
    const response = await axios.get('/api/payment-gateways');
    gateways.value = response.data;
  } catch (error) {
    console.error('Error fetching gateways:', error);
  }
};

const openDialog = (gateway = null) => {
  editMode.value = !!gateway;

  if (gateway) {
    formData.value = {
      id: gateway.id,
      display_name: gateway.display_name,
      driver: gateway.driver,
      description: gateway.description,
      credentials: gateway.credentials || { public_key: '', secret_key: '' },
      currency: gateway.currency,
      supported_channels: gateway.supported_channels || [],
      is_active: gateway.is_active,
      is_default: gateway.is_default,
      priority: gateway.priority,
    };
  } else {
    formData.value = {
      display_name: '',
      driver: 'paystack',
      description: '',
      credentials: { public_key: '', secret_key: '' },
      currency: 'NGN',
      supported_channels: ['card', 'bank', 'ussd'],
      is_active: true,
      is_default: false,
      priority: 0,
    };
  }

  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
  form.value?.reset();
};

const saveGateway = async () => {
  if (!form.value?.validate()) return;

  saving.value = true;
  try {
    if (editMode.value) {
      await axios.put(`/api/payment-gateways/${formData.value.id}`, formData.value);
    } else {
      await axios.post('/api/payment-gateways', {
        ...formData.value,
        name: formData.value.driver.charAt(0).toUpperCase() + formData.value.driver.slice(1),
      });
    }

    await fetchGateways();
    closeDialog();
  } catch (error) {
    console.error('Error saving gateway:', error);
    alert(error.response?.data?.message || 'Failed to save gateway');
  } finally {
    saving.value = false;
  }
};

const toggleGateway = async (gateway) => {
  try {
    await axios.post(`/api/payment-gateways/${gateway.id}/toggle`, {
      is_active: !gateway.is_active,
    });
    await fetchGateways();
  } catch (error) {
    console.error('Error toggling gateway:', error);
    alert(error.response?.data?.message || 'Failed to toggle gateway');
  }
};

const setDefault = async (gateway) => {
  try {
    await axios.post(`/api/payment-gateways/${gateway.id}/set-default`);
    await fetchGateways();
  } catch (error) {
    console.error('Error setting default gateway:', error);
    alert(error.response?.data?.message || 'Failed to set default gateway');
  }
};

const getGatewayIcon = (driver) => {
  const icons = {
    paystack: 'mdi-credit-card',
    monnify: 'mdi-bank',
    palmpay: 'mdi-wallet',
  };
  return icons[driver] || 'mdi-credit-card';
};

onMounted(() => {
  fetchGateways();
});
</script>

<style scoped>
.gateway-card {
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.gateway-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
}

.active-gateway {
  border-color: #4caf50;
}

.default-gateway {
  background: linear-gradient(135deg, #f5f5f5 0%, #e3f2fd 100%);
}
</style>

