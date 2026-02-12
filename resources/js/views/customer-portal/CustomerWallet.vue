<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">My Wallet</h1>
      </v-col>
    </v-row>

    <!-- Wallet Balance Card -->
    <v-row>
      <v-col cols="12" md="6">
        <v-card color="primary" dark>
          <v-card-text>
            <div class="text-caption">Current Balance</div>
            <div class="text-h3">₦{{ formatNumber(walletBalance) }}</div>
          </v-card-text>
          <v-card-actions>
            <v-btn variant="outlined" @click="fundDialog = true">
              <v-icon left>mdi-plus-circle</v-icon>
              Fund Wallet
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- Transaction History -->
    <v-row class="mt-4">
      <v-col cols="12">
        <v-card>
          <v-card-title>Transaction History</v-card-title>
          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="transactions"
              :loading="loading"
            >
              <template v-slot:item.transaction_date="{ item }">
                {{ item.transaction_date }}
              </template>

              <template v-slot:item.transaction_type="{ item }">
                <v-chip :color="getTypeColor(item.transaction_type)" size="small">
                  {{ item.transaction_type }}
                </v-chip>
              </template>

              <template v-slot:item.amount="{ item }">
                <span :class="item.transaction_type === 'credit' ? 'text-success' : 'text-error'">
                  {{ item.transaction_type === 'credit' ? '+' : '-' }}₦{{ formatNumber(item.amount) }}
                </span>
              </template>

              <template v-slot:item.balance_after="{ item }">
                ₦{{ formatNumber(item.balance_after) }}
              </template>

              <template v-slot:item.status="{ item }">
                <v-chip :color="getStatusColor(item.status)" size="small">
                  {{ item.status }}
                </v-chip>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Fund Wallet Dialog -->
    <v-dialog v-model="fundDialog" max-width="600px" persistent>
      <v-card>
        <v-card-title class="bg-primary text-white">
          <v-icon start>mdi-wallet-plus</v-icon>
          Fund Wallet
        </v-card-title>
        <v-card-text class="pt-6">
          <v-form ref="fundForm">
            <v-text-field
              v-model.number="fundAmount"
              label="Amount *"
              type="number"
              min="100"
              variant="outlined"
              prefix="₦"
              :rules="[
                v => v >= 100 || 'Minimum amount is ₦100',
                v => v <= 10000000 || 'Maximum amount is ₦10,000,000'
              ]"
              hint="Minimum: ₦100"
            ></v-text-field>

            <v-select
              v-model="paymentMethod"
              :items="paymentMethods"
              label="Payment Method *"
              variant="outlined"
              :rules="[v => !!v || 'Required']"
            >
              <template v-slot:item="{ props, item }">
                <v-list-item v-bind="props">
                  <template v-slot:prepend>
                    <v-icon :icon="item.raw.icon"></v-icon>
                  </template>
                </v-list-item>
              </template>
            </v-select>

            <!-- Online Payment (Paystack) -->
            <v-alert v-if="paymentMethod === 'online'" type="info" variant="tonal" class="mt-2">
              <div class="d-flex align-center">
                <v-icon start>mdi-information</v-icon>
                <div>
                  <strong>Secure Online Payment</strong>
                  <p class="text-caption mb-0">You will be redirected to our payment gateway to complete your transaction securely.</p>
                </div>
              </div>
            </v-alert>

            <!-- Manual Payment -->
            <v-text-field
              v-if="paymentMethod !== 'online'"
              v-model="paymentReference"
              label="Payment Reference *"
              variant="outlined"
              :rules="[v => !!v || 'Required']"
              hint="Enter your bank transfer reference or transaction ID"
            ></v-text-field>

            <v-alert v-if="paymentMethod !== 'online'" type="warning" variant="tonal" class="mt-2">
              Your wallet will be credited after payment verification by our team
            </v-alert>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeFundDialog">Cancel</v-btn>
          <v-btn
            color="primary"
            @click="submitFundRequest"
            :loading="funding"
            variant="flat"
          >
            <v-icon start>{{ paymentMethod === 'online' ? 'mdi-credit-card' : 'mdi-check' }}</v-icon>
            {{ paymentMethod === 'online' ? 'Pay Now' : 'Submit Request' }}
          </v-btn>
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
const { alert } = useDialog();
const loading = ref(false);
const funding = ref(false);
const fundDialog = ref(false);
const walletBalance = ref(0);
const transactions = ref([]);
const fundAmount = ref(0);
const paymentMethod = ref('bank_transfer');
const paymentReference = ref('');

const headers = [
  { title: 'Date', key: 'transaction_date' },
  { title: 'Type', key: 'transaction_type' },
  { title: 'Amount', key: 'amount' },
  { title: 'Balance After', key: 'balance_after' },
  { title: 'Reference', key: 'reference' },
  { title: 'Status', key: 'status' },
];

const paymentMethods = [
  { title: 'Online Payment (Paystack)', value: 'online', icon: 'mdi-credit-card' },
  { title: 'Bank Transfer', value: 'bank_transfer', icon: 'mdi-bank-transfer' },
  { title: 'Cash Deposit', value: 'cash', icon: 'mdi-cash' },
];

const formatNumber = (num) => new Intl.NumberFormat('en-NG').format(num);

const getTypeColor = (type) => {
  return type === 'credit' ? 'success' : 'error';
};

const getStatusColor = (status) => {
  const colors = { pending: 'warning', completed: 'success', reversed: 'error' };
  return colors[status] || 'default';
};

const loadWalletData = async () => {
  loading.value = true;
  try {
    const [balanceRes, transactionsRes] = await Promise.all([
      axios.get(`/api/wallet/customers/${authStore.user.id}/balance`),
      axios.get('/api/wallet/transactions', { params: { customer_id: authStore.user.id } }),
    ]);

    walletBalance.value = balanceRes.data.balance;
    transactions.value = transactionsRes.data.data || transactionsRes.data;
  } catch (error) {
    console.error('Failed to load wallet data:', error);
  } finally {
    loading.value = false;
  }
};

const closeFundDialog = () => {
  fundDialog.value = false;
  fundAmount.value = 100;
  paymentMethod.value = 'online';
  paymentReference.value = '';
};

const submitFundRequest = async () => {
  funding.value = true;
  try {
    // Online payment via payment gateway
    if (paymentMethod.value === 'online') {
      const response = await axios.post('/api/payments/initialize', {
        customer_id: authStore.user.id,
        amount: fundAmount.value,
        description: 'Wallet funding via online payment',
        metadata: {
          source: 'customer_portal',
        },
      });

      if (response.data.status) {
        // Redirect to payment gateway
        window.location.href = response.data.data.authorization_url;
      } else {
        alert(response.data.message || 'Failed to initialize payment');
      }
    } else {
      // Manual payment (bank transfer, cash)
      await axios.post('/api/wallet/fund', {
        customer_id: authStore.user.id,
        amount: fundAmount.value,
        payment_method: paymentMethod.value,
        reference: paymentReference.value,
        notes: 'Customer self-service wallet funding',
      });

      closeFundDialog();
      loadWalletData();
      alert('Funding request submitted successfully. Your wallet will be credited after verification.');
    }
  } catch (error) {
    console.error('Failed to submit funding request:', error);
    alert(error.response?.data?.message || 'Failed to submit funding request. Please try again.');
  } finally {
    funding.value = false;
  }
};

onMounted(() => {
  loadWalletData();

  // Check for payment callback
  const urlParams = new URLSearchParams(window.location.search);
  const reference = urlParams.get('reference');
  const status = urlParams.get('status');

  if (reference && status === 'success') {
    // Verify payment
    verifyPayment(reference);
  } else if (reference && status === 'cancelled') {
    alert('Payment was cancelled');
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});

const verifyPayment = async (reference) => {
  try {
    const response = await axios.post('/api/payments/verify', { reference });

    if (response.data.status) {
      alert(`Payment successful! ₦${formatNumber(response.data.data.amount)} has been credited to your wallet.`);
      loadWalletData();
    } else {
      alert('Payment verification failed. Please contact support if amount was deducted.');
    }
  } catch (error) {
    console.error('Payment verification error:', error);
    alert('Payment verification failed. Please contact support.');
  } finally {
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
};
</script>
