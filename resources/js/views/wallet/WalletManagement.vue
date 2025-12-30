<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Wallet Management</h1>
      </v-col>
    </v-row>

    <v-tabs v-model="activeTab" color="primary">
      <v-tab value="transactions">Transactions</v-tab>
      <v-tab value="fund">Fund Wallet</v-tab>
      <v-tab value="debit">Debit Wallet</v-tab>
    </v-tabs>

    <v-window v-model="activeTab" class="mt-4">
      <!-- Transactions -->
      <v-window-item value="transactions">
        <v-card>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <v-select
                  v-model="filters.customer_id"
                  :items="customers"
                  item-title="name"
                  item-value="id"
                  label="Customer"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadTransactions"
                ></v-select>
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.transaction_type"
                  :items="transactionTypes"
                  label="Type"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadTransactions"
                ></v-select>
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.status"
                  :items="statusOptions"
                  label="Status"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadTransactions"
                ></v-select>
              </v-col>
            </v-row>

            <v-data-table
              :headers="transactionHeaders"
              :items="transactions"
              :loading="loading"
            >
              <template v-slot:item.customer="{ item }">
                {{ item.customer?.name }}
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

              <template v-slot:item.actions="{ item }">
                <v-btn
                  icon
                  size="small"
                  @click="approveTransaction(item)"
                  title="Approve"
                  color="success"
                  v-if="item.status === 'pending' && authStore.hasPermission('approve_wallet_transactions')"
                >
                  <v-icon>mdi-check</v-icon>
                </v-btn>
                <v-btn
                  icon
                  size="small"
                  @click="reverseTransaction(item)"
                  title="Reverse"
                  color="error"
                  v-if="item.status === 'completed' && authStore.hasPermission('reverse_wallet_transactions')"
                >
                  <v-icon>mdi-undo</v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Fund Wallet -->
      <v-window-item value="fund">
        <v-card>
          <v-card-text>
            <v-form ref="fundForm">
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="fundData.customer_id"
                    :items="customers"
                    item-title="name"
                    item-value="id"
                    label="Customer *"
                    variant="outlined"
                    :rules="[v => !!v || 'Required']"
                    @update:model-value="loadCustomerBalance"
                  ></v-select>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model.number="fundData.amount"
                    label="Amount *"
                    type="number"
                    min="0.01"
                    variant="outlined"
                    prefix="₦"
                    :rules="[v => v > 0 || 'Must be greater than 0']"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="fundData.payment_method"
                    :items="paymentMethods"
                    label="Payment Method *"
                    variant="outlined"
                    :rules="[v => !!v || 'Required']"
                  ></v-select>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="fundData.reference"
                    label="Payment Reference *"
                    variant="outlined"
                    :rules="[v => !!v || 'Required']"
                  ></v-text-field>
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="fundData.notes"
                    label="Notes"
                    variant="outlined"
                    rows="2"
                  ></v-textarea>
                </v-col>
                <v-col cols="12" v-if="customerBalance !== null">
                  <v-alert type="info">
                    Current Balance: ₦{{ formatNumber(customerBalance) }}
                  </v-alert>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="fundWallet" :loading="funding">
              <v-icon left>mdi-plus-circle</v-icon>
              Fund Wallet
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-window-item>

      <!-- Debit Wallet -->
      <v-window-item value="debit">
        <v-card>
          <v-card-text>
            <v-form ref="debitForm">
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="debitData.customer_id"
                    :items="customers"
                    item-title="name"
                    item-value="id"
                    label="Customer *"
                    variant="outlined"
                    :rules="[v => !!v || 'Required']"
                    @update:model-value="loadCustomerBalance"
                  ></v-select>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model.number="debitData.amount"
                    label="Amount *"
                    type="number"
                    min="0.01"
                    variant="outlined"
                    prefix="₦"
                    :rules="[v => v > 0 || 'Must be greater than 0']"
                  ></v-text-field>
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="debitData.description"
                    label="Description *"
                    variant="outlined"
                    rows="2"
                    :rules="[v => !!v || 'Required']"
                  ></v-textarea>
                </v-col>
                <v-col cols="12" v-if="customerBalance !== null">
                  <v-alert type="info">
                    Current Balance: ₦{{ formatNumber(customerBalance) }}
                  </v-alert>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="error" @click="debitWallet" :loading="debiting">
              <v-icon left>mdi-minus-circle</v-icon>
              Debit Wallet
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-window-item>
    </v-window>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const authStore = useAuthStore();
const activeTab = ref('transactions');
const loading = ref(false);
const funding = ref(false);
const debiting = ref(false);
const transactions = ref([]);
const customers = ref([]);
const customerBalance = ref(null);

const filters = ref({
  customer_id: null,
  transaction_type: null,
  status: null,
});

const fundData = ref({
  customer_id: null,
  amount: 0,
  payment_method: 'bank_transfer',
  reference: '',
  notes: '',
});

const debitData = ref({
  customer_id: null,
  amount: 0,
  description: '',
});

const transactionTypes = [
  { title: 'Credit', value: 'credit' },
  { title: 'Debit', value: 'debit' },
];

const statusOptions = [
  { title: 'Pending', value: 'pending' },
  { title: 'Completed', value: 'completed' },
  { title: 'Reversed', value: 'reversed' },
];

const paymentMethods = [
  { title: 'Cash', value: 'cash' },
  { title: 'Bank Transfer', value: 'bank_transfer' },
  { title: 'Card', value: 'card' },
  { title: 'Online Payment', value: 'online' },
];

const transactionHeaders = [
  { title: 'Date', key: 'transaction_date' },
  { title: 'Customer', key: 'customer' },
  { title: 'Type', key: 'transaction_type' },
  { title: 'Amount', key: 'amount' },
  { title: 'Balance After', key: 'balance_after' },
  { title: 'Reference', key: 'reference' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formatNumber = (num) => new Intl.NumberFormat('en-NG').format(num);

const getTypeColor = (type) => {
  return type === 'credit' ? 'success' : 'error';
};

const getStatusColor = (status) => {
  const colors = { pending: 'warning', completed: 'success', reversed: 'error' };
  return colors[status] || 'default';
};

const loadTransactions = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/wallet/transactions', { params: filters.value });
    transactions.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load transactions:', error);
  } finally {
    loading.value = false;
  }
};

const loadCustomerBalance = async () => {
  const customerId = fundData.value.customer_id || debitData.value.customer_id;
  if (!customerId) return;
  try {
    const response = await axios.get(`/api/wallet/customers/${customerId}/balance`);
    customerBalance.value = response.data.balance;
  } catch (error) {
    console.error('Failed to load balance:', error);
  }
};

const fundWallet = async () => {
  funding.value = true;
  try {
    await axios.post('/api/wallet/fund', fundData.value);
    fundData.value = {
      customer_id: null,
      amount: 0,
      payment_method: 'bank_transfer',
      reference: '',
      notes: '',
    };
    customerBalance.value = null;
    loadTransactions();
    alert('Wallet funded successfully');
  } catch (error) {
    console.error('Failed to fund wallet:', error);
    alert('Failed to fund wallet');
  } finally {
    funding.value = false;
  }
};

const debitWallet = async () => {
  debiting.value = true;
  try {
    await axios.post('/api/wallet/debit', debitData.value);
    debitData.value = {
      customer_id: null,
      amount: 0,
      description: '',
    };
    customerBalance.value = null;
    loadTransactions();
    alert('Wallet debited successfully');
  } catch (error) {
    console.error('Failed to debit wallet:', error);
    alert('Failed to debit wallet');
  } finally {
    debiting.value = false;
  }
};

const approveTransaction = async (transaction) => {
  if (confirm('Approve this transaction?')) {
    try {
      await axios.post(`/api/wallet/transactions/${transaction.id}/approve`);
      loadTransactions();
      alert('Transaction approved');
    } catch (error) {
      console.error('Failed to approve transaction:', error);
      alert('Failed to approve transaction');
    }
  }
};

const reverseTransaction = async (transaction) => {
  if (confirm('Reverse this transaction? This action cannot be undone.')) {
    try {
      await axios.post(`/api/wallet/transactions/${transaction.id}/reverse`);
      loadTransactions();
      alert('Transaction reversed');
    } catch (error) {
      console.error('Failed to reverse transaction:', error);
      alert('Failed to reverse transaction');
    }
  }
};

onMounted(async () => {
  const customersRes = await axios.get('/api/customers');
  customers.value = customersRes.data.data || customersRes.data;
  loadTransactions();
});
</script>

