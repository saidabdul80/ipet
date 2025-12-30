<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Debtors Management</span>
            <v-btn color="primary" @click="loadData">
              <v-icon left>mdi-refresh</v-icon>
              Refresh
            </v-btn>
          </v-card-title>

          <!-- Summary Cards -->
          <v-card-text>
            <v-row>
              <v-col cols="12" md="3">
                <v-card color="primary" dark>
                  <v-card-text>
                    <div class="text-h6">Total Debtors</div>
                    <div class="text-h4">{{ summary.total_debtors || 0 }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="3">
                <v-card color="warning" dark>
                  <v-card-text>
                    <div class="text-h6">Total Outstanding</div>
                    <div class="text-h4">₦{{ formatNumber(summary.total_outstanding || 0) }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="3">
                <v-card color="error" dark>
                  <v-card-text>
                    <div class="text-h6">Total Overdue</div>
                    <div class="text-h4">₦{{ formatNumber(summary.total_overdue || 0) }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="3">
                <v-card color="info" dark>
                  <v-card-text>
                    <div class="text-h6">Outstanding Invoices</div>
                    <div class="text-h4">{{ summary.total_invoices || 0 }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>

          <!-- Filters -->
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="filters.search"
                  label="Search (Name, Code, Phone)"
                  prepend-inner-icon="mdi-magnify"
                  clearable
                  @input="loadData"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="filters.category"
                  :items="categories"
                  label="Customer Category"
                  clearable
                  @update:modelValue="loadData"
                ></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-btn color="primary" @click="showAgingReport = true" block>
                  <v-icon left>mdi-chart-bar</v-icon>
                  View Aging Report
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>

          <!-- Debtors Table -->
          <v-data-table
            :headers="headers"
            :items="debtors"
            :loading="loading"
            class="elevation-1"
          >
            <template v-slot:item.customer.name="{ item }">
              <div>
                <div class="font-weight-bold">{{ item.customer.name }}</div>
                <div class="text-caption">{{ item.customer.code }}</div>
              </div>
            </template>

            <template v-slot:item.total_outstanding="{ item }">
              <v-chip :color="item.total_outstanding > 0 ? 'warning' : 'success'" size="small">
                ₦{{ formatNumber(item.total_outstanding) }}
              </v-chip>
            </template>

            <template v-slot:item.total_overdue="{ item }">
              <v-chip :color="item.total_overdue > 0 ? 'error' : 'success'" size="small">
                ₦{{ formatNumber(item.total_overdue) }}
              </v-chip>
            </template>

            <template v-slot:item.credit_available="{ item }">
              <span>₦{{ formatNumber(item.credit_available) }}</span>
            </template>

            <template v-slot:item.oldest_invoice_date="{ item }">
              {{ item.oldest_invoice_date ? formatDate(item.oldest_invoice_date) : 'N/A' }}
            </template>

            <template v-slot:item.actions="{ item }">
              <v-btn icon size="small" @click="viewDetails(item.customer)">
                <v-icon>mdi-eye</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>

    <!-- Debtor Details Dialog -->
    <v-dialog v-model="detailsDialog" max-width="1200px">
      <v-card v-if="selectedDebtor">
        <v-card-title class="d-flex justify-space-between">
          <span>Debtor Details: {{ selectedDebtor.customer?.name }}</span>
          <v-btn icon @click="detailsDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text>
          <!-- Customer Info -->
          <v-row>
            <v-col cols="12" md="6">
              <v-list>
                <v-list-item>
                  <v-list-item-title>Customer Code</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedDebtor.customer?.code }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Phone</v-list-item-title>
                  <v-list-item-subtitle>{{ selectedDebtor.customer?.phone || 'N/A' }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-col>
            <v-col cols="12" md="6">
              <v-list>
                <v-list-item>
                  <v-list-item-title>Total Outstanding</v-list-item-title>
                  <v-list-item-subtitle class="text-h6 text-warning">₦{{ formatNumber(selectedDebtor.total_outstanding) }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Credit Limit</v-list-item-title>
                  <v-list-item-subtitle>₦{{ formatNumber(selectedDebtor.credit_limit) }}</v-list-item-subtitle>
                </v-list-item>
                <v-list-item>
                  <v-list-item-title>Credit Available</v-list-item-title>
                  <v-list-item-subtitle class="text-success">₦{{ formatNumber(selectedDebtor.credit_available) }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-col>
          </v-row>

          <!-- Outstanding Sales -->
          <v-divider class="my-4"></v-divider>
          <h3 class="mb-3">Outstanding Invoices</h3>
          <v-data-table
            :headers="salesHeaders"
            :items="selectedDebtor.sales"
            :items-per-page="5"
          >
            <template v-slot:item.invoice_number="{ item }">
              <div>
                <div class="font-weight-bold">{{ item.invoice_number }}</div>
                <div class="text-caption">{{ formatDate(item.sale_date) }}</div>
              </div>
            </template>

            <template v-slot:item.outstanding_amount="{ item }">
              <v-chip :color="item.is_overdue ? 'error' : 'warning'" size="small">
                ₦{{ formatNumber(item.outstanding_amount) }}
              </v-chip>
            </template>

            <template v-slot:item.due_date="{ item }">
              <div>
                <div>{{ item.due_date ? formatDate(item.due_date) : 'N/A' }}</div>
                <div v-if="item.is_overdue" class="text-caption text-error">
                  {{ item.days_overdue }} days overdue
                </div>
              </div>
            </template>

            <template v-slot:item.actions="{ item }">
              <v-btn color="primary" size="small" @click="openPaymentDialog(item)">
                Record Payment
              </v-btn>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Payment Dialog -->
    <v-dialog v-model="paymentDialog" max-width="600px">
      <v-card>
        <v-card-title>Record Payment</v-card-title>
        <v-card-text>
          <v-form ref="paymentForm">
            <v-row>
              <v-col cols="12">
                <div class="mb-4">
                  <div><strong>Invoice:</strong> {{ selectedSale?.invoice_number }}</div>
                  <div><strong>Outstanding:</strong> ₦{{ formatNumber(selectedSale?.outstanding_amount || 0) }}</div>
                </div>
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model.number="paymentForm.amount"
                  label="Payment Amount"
                  type="number"
                  prefix="₦"
                  :rules="[v => !!v || 'Amount is required', v => v > 0 || 'Amount must be greater than 0', v => v <= (selectedSale?.outstanding_amount || 0) || 'Amount cannot exceed outstanding']"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-select
                  v-model="paymentForm.payment_method"
                  :items="paymentMethods"
                  label="Payment Method"
                  :rules="[v => !!v || 'Payment method is required']"
                  required
                ></v-select>
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="paymentForm.reference"
                  label="Reference (Optional)"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="paymentForm.notes"
                  label="Notes (Optional)"
                  rows="3"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="paymentDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="submitPayment" :loading="submitting">Submit Payment</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Aging Report Dialog -->
    <v-dialog v-model="showAgingReport" max-width="900px">
      <v-card>
        <v-card-title>Aging Report</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="3" v-for="(value, key) in agingSummary" :key="key">
              <v-card>
                <v-card-text>
                  <div class="text-caption">{{ getAgingLabel(key) }}</div>
                  <div class="text-h6">₦{{ formatNumber(value) }}</div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showAgingReport = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const submitting = ref(false);
const debtors = ref([]);
const summary = ref({});
const agingSummary = ref({});
const detailsDialog = ref(false);
const paymentDialog = ref(false);
const showAgingReport = ref(false);
const selectedDebtor = ref(null);
const selectedSale = ref(null);

const filters = ref({
  search: '',
  category: null,
});

const paymentForm = ref({
  amount: 0,
  payment_method: '',
  reference: '',
  notes: '',
});

const categories = ['general', 'retailer', 'wholesaler'];
const paymentMethods = [
  { title: 'Cash', value: 'cash' },
  { title: 'Card', value: 'card' },
  { title: 'Bank Transfer', value: 'bank_transfer' },
  { title: 'Wallet', value: 'wallet' },
];

const headers = [
  { title: 'Customer', key: 'customer.name' },
  { title: 'Total Outstanding', key: 'total_outstanding' },
  { title: 'Total Overdue', key: 'total_overdue' },
  { title: 'Credit Limit', key: 'credit_limit' },
  { title: 'Credit Available', key: 'credit_available' },
  { title: 'Invoices', key: 'outstanding_invoices_count' },
  { title: 'Oldest Invoice', key: 'oldest_invoice_date' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const salesHeaders = [
  { title: 'Invoice', key: 'invoice_number' },
  { title: 'Total Amount', key: 'total_amount' },
  { title: 'Paid', key: 'amount_paid' },
  { title: 'Outstanding', key: 'outstanding_amount' },
  { title: 'Due Date', key: 'due_date' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const loadData = async () => {
  loading.value = true;
  try {
    const [debtorsRes, summaryRes] = await Promise.all([
      axios.get('/api/debtors', { params: filters.value }),
      axios.get('/api/debtors/summary', { params: filters.value }),
    ]);
    debtors.value = debtorsRes.data.data || [];
    summary.value = summaryRes.data || {};
    agingSummary.value = summaryRes.data.aging_summary || {};
  } catch (error) {
    console.error('Failed to load debtors:', error);
  } finally {
    loading.value = false;
  }
};

const viewDetails = async (customer) => {
  try {
    const response = await axios.get(`/api/debtors/${customer.id}`);
    selectedDebtor.value = response.data;
    detailsDialog.value = true;
  } catch (error) {
    console.error('Failed to load debtor details:', error);
  }
};

const openPaymentDialog = (sale) => {
  selectedSale.value = sale;
  paymentForm.value = {
    amount: sale.outstanding_amount,
    payment_method: '',
    reference: '',
    notes: '',
  };
  paymentDialog.value = true;
};

const submitPayment = async () => {
  submitting.value = true;
  try {
    await axios.post(`/api/debtors/sales/${selectedSale.value.id}/payment`, paymentForm.value);
    paymentDialog.value = false;
    // Reload details
    await viewDetails(selectedDebtor.value.customer);
    await loadData();
  } catch (error) {
    console.error('Failed to record payment:', error);
    alert(error.response?.data?.message || 'Failed to record payment');
  } finally {
    submitting.value = false;
  }
};

const formatNumber = (value) => {
  return new Intl.NumberFormat('en-NG').format(value || 0);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-NG');
};

const getAgingLabel = (key) => {
  const labels = {
    current: '0-30 Days',
    '31_60': '31-60 Days',
    '61_90': '61-90 Days',
    over_90: 'Over 90 Days',
  };
  return labels[key] || key;
};

onMounted(() => {
  loadData();
});
</script>


