<template>
  <div class="sales-history-container">
    <!-- Header Section -->
    <v-card class="mb-6" elevation="2">
      <v-card-text class="pa-6">
        <div class="d-flex align-center justify-space-between mb-4">
          <div>
            <h1 class="text-h4 font-weight-bold text-primary">Sales History</h1>
            <p class="text-body-1 text-grey-darken-1 mt-2">Track and manage all sales transactions</p>
          </div>
          <v-chip variant="outlined" color="primary" class="px-4 py-2">
            <v-icon start>mdi-chart-line</v-icon>
            Total Sales: ₦{{ formatNumber(totalSales) }}
          </v-chip>
        </div>
      </v-card-text>
    </v-card>

    <!-- Summary Cards -->
    <v-row class="mb-6">
      <v-col cols="12" sm="6" md="3">
        <v-card elevation="2" class="summary-card" :class="{ 'border-left-success': true }">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between">
              <div>
                <p class="text-body-2 text-grey-darken-1 mb-1">Today's Sales</p>
                <h2 class="text-h5 font-weight-bold">₦{{ formatNumber(dailySales) }}</h2>
              </div>
              <v-icon color="success" size="40">mdi-cash-multiple</v-icon>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <v-card elevation="2" class="summary-card" :class="{ 'border-left-primary': true }">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between">
              <div>
                <p class="text-body-2 text-grey-darken-1 mb-1">Total Orders</p>
                <h2 class="text-h5 font-weight-bold">{{ totalOrders }}</h2>
              </div>
              <v-icon color="primary" size="40">mdi-shopping</v-icon>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <v-card elevation="2" class="summary-card" :class="{ 'border-left-warning': true }">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between">
              <div>
                <p class="text-body-2 text-grey-darken-1 mb-1">Pending Payments</p>
                <h2 class="text-h5 font-weight-bold">{{ pendingPayments }}</h2>
              </div>
              <v-icon color="warning" size="40">mdi-clock-outline</v-icon>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <v-card elevation="2" class="summary-card" :class="{ 'border-left-info': true }">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between">
              <div>
                <p class="text-body-2 text-grey-darken-1 mb-1">Average Order Value</p>
                <h2 class="text-h5 font-weight-bold">₦{{ formatNumber(averageOrderValue) }}</h2>
              </div>
              <v-icon color="info" size="40">mdi-chart-bar</v-icon>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Filters Section -->
    <v-card class="mb-6" elevation="2">
      <v-card-title class="bg-grey-lighten-4">
        <v-icon start>mdi-filter</v-icon>
        Filters
      </v-card-title>
      <v-card-text class="pa-4">
        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="filters.search"
              label="Search invoices or customers"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="comfortable"
              clearable
              hide-details
              @input="debouncedLoadSales"
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.store_id"
              :items="stores"
              item-title="name"
              item-value="id"
              label="Store"
              variant="outlined"
              density="comfortable"
              clearable
              hide-details
              @update:model-value="loadSales"
            ></v-select>
          </v-col>
          <v-col cols="12" md="2">
            <v-menu v-model="dateMenu" :close-on-content-click="false">
              <template v-slot:activator="{ props }">
                <v-text-field
                  v-model="dateRangeText"
                  label="Date Range"
                  prepend-inner-icon="mdi-calendar"
                  variant="outlined"
                  density="comfortable"
                  readonly
                  hide-details
                  v-bind="props"
                ></v-text-field>
              </template>
              <v-date-picker
                v-model="dateRange"
                range
                @update:model-value="handleDateRangeChange"
              ></v-date-picker>
            </v-menu>
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.sale_type"
              :items="saleTypes"
              label="Sale Type"
              variant="outlined"
              density="comfortable"
              clearable
              hide-details
              @update:model-value="loadSales"
            ></v-select>
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.payment_status"
              :items="paymentStatuses"
              label="Payment Status"
              variant="outlined"
              density="comfortable"
              clearable
              hide-details
              @update:model-value="loadSales"
            ></v-select>
          </v-col>
        </v-row>
        <div class="d-flex justify-end mt-4">
          <v-btn variant="text" color="grey" @click="resetFilters" prepend-icon="mdi-refresh">
            Reset Filters
          </v-btn>
          <v-btn color="primary" @click="loadSales" prepend-icon="mdi-filter-apply">
            Apply Filters
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- Sales Table Card -->
    <v-card elevation="2">
      <v-card-title class="bg-grey-lighten-4 d-flex align-center justify-space-between">
        <div>
          <span class="text-h6 font-weight-bold">Sales Transactions</span>
          <v-chip variant="tonal" color="primary" size="small" class="ml-2">
            {{ filteredSales.length }} records
          </v-chip>
        </div>
        <div class="d-flex align-center gap-2">
          <v-btn
            variant="tonal"
            color="success"
            prepend-icon="mdi-download"
            @click="exportToCSV"
            size="small"
          >
            Export
          </v-btn>
          <v-btn
            variant="tonal"
            color="primary"
            prepend-icon="mdi-printer"
            @click="printReport"
            size="small"
          >
            Print
          </v-btn>
        </div>
      </v-card-title>

      <v-data-table
        :headers="headers"
        :items="filteredSales"
        :loading="loading"
        :items-per-page="perPage"
        :page="page"
        :sort-by="[{ key: 'sale_date', order: 'desc' }]"
        class="elevation-1"
      >
        <!-- Loading State -->
        <template v-slot:loading>
          <v-skeleton-loader type="table-row@6"></v-skeleton-loader>
        </template>

        <!-- Invoice Number -->
        <template v-slot:item.invoice_number="{ item }">
          <div class="d-flex align-center">
            <v-icon color="primary" size="small" class="mr-2">mdi-receipt</v-icon>
            <a @click="viewSale(item)" class="text-primary text-decoration-none font-weight-medium cursor-pointer">
              {{ item.invoice_number }}
            </a>
          </div>
        </template>

        <!-- Date Column -->
        <template v-slot:item.sale_date="{ item }">
          <div class="d-flex flex-column">
            <span class="text-body-2">{{ formatDate(item.sale_date) }}</span>
            <span class="text-caption text-grey">{{ formatTime(item.sale_date) }}</span>
          </div>
        </template>

        <!-- Customer Column -->
        <template v-slot:item.customer="{ item }">
          <div class="d-flex align-center">
            <v-avatar size="28" color="primary" class="mr-2">
              <v-icon color="white" size="small">mdi-account</v-icon>
            </v-avatar>
            <span>{{ item.customer?.name || 'Walk-in Customer' }}</span>
          </div>
        </template>

        <!-- Sale Type -->
        <template v-slot:item.sale_type="{ item }">
          <v-chip
            :color="getSaleTypeColor(item.sale_type)"
            size="small"
            :variant="item.sale_type === 'pos' ? 'flat' : 'tonal'"
            class="font-weight-medium"
          >
            <v-icon start size="small">{{ getSaleTypeIcon(item.sale_type) }}</v-icon>
            {{ item.sale_type.toUpperCase() }}
          </v-chip>
        </template>

        <!-- Amount -->
        <template v-slot:item.total_amount="{ item }">
          <div class="d-flex flex-column align-end">
            <span class="text-body-1 font-weight-bold text-primary">
              ₦{{ formatNumber(item.total_amount) }}
            </span>
            <v-chip
              v-if="item.discount_amount > 0"
              size="x-small"
              color="error"
              variant="tonal"
              class="mt-1"
            >
              -₦{{ formatNumber(item.discount_amount) }}
            </v-chip>
          </div>
        </template>

        <!-- Payment Status -->
        <template v-slot:item.payment_status="{ item }">
          <v-chip
            :color="getPaymentStatusColor(item.payment_status)"
            size="small"
            :variant="item.payment_status === 'paid' ? 'flat' : 'tonal'"
            class="font-weight-medium"
          >
            <v-icon start size="small">{{ getPaymentStatusIcon(item.payment_status) }}</v-icon>
            {{ formatPaymentStatus(item.payment_status) }}
          </v-chip>
        </template>

        <!-- Status -->
        <template v-slot:item.is_voided="{ item }">
          <v-chip
            v-if="item.is_voided"
            color="error"
            size="small"
            variant="flat"
            class="font-weight-medium"
          >
            <v-icon start size="small">mdi-cancel</v-icon>
            VOIDED
          </v-chip>
          <v-chip
            v-else
            color="success"
            size="small"
            variant="tonal"
          >
            <v-icon start size="small">mdi-check-circle</v-icon>
            Active
          </v-chip>
        </template>

        <!-- Actions -->
        <template v-slot:item.actions="{ item }">
          <div class="d-flex gap-1">
            <v-tooltip text="View Details" location="top">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon
                  size="small"
                  variant="text"
                  color="primary"
                  @click="viewSale(item)"
                >
                  <v-icon>mdi-eye-outline</v-icon>
                </v-btn>
              </template>
            </v-tooltip>

            <v-tooltip text="Print Receipt" location="top">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon
                  size="small"
                  variant="text"
                  color="success"
                  @click="downloadReceipt(item)"
                >
                  <v-icon>mdi-printer-outline</v-icon>
                </v-btn>
              </template>
            </v-tooltip>

            <v-tooltip text="Void Sale" location="top" v-if="!item.is_voided && authStore.hasPermission('void_sales')">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon
                  size="small"
                  variant="text"
                  color="error"
                  @click="voidSale(item)"
                >
                  <v-icon>mdi-close-circle-outline</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
          </div>
        </template>

        <!-- Empty State -->
        <template v-slot:no-data>
          <div class="text-center py-8">
            <v-icon size="64" color="grey-lighten-1">mdi-receipt-text-off</v-icon>
            <h3 class="text-h6 mt-4 text-grey">No sales found</h3>
            <p class="text-grey mt-2">Try adjusting your filters or create a new sale</p>
            <v-btn color="primary" class="mt-4" prepend-icon="mdi-plus">
              Create New Sale
            </v-btn>
          </div>
        </template>
      </v-data-table>

      <!-- Table Footer -->
      <v-divider></v-divider>
      <div class="pa-4 d-flex justify-space-between align-center">
        <div class="text-body-2 text-grey">
          Showing {{ startIndex }}-{{ endIndex }} of {{ filteredSales.length }} records
        </div>
        <v-pagination
          v-model="page"
          :length="Math.ceil(filteredSales.length / perPage)"
          :total-visible="5"
          rounded="circle"
        ></v-pagination>
      </div>
    </v-card>

    <!-- Sale Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="900px" scrollable>
      <v-card v-if="selectedSale" class="elevation-3">
        <v-toolbar color="primary" dark>
          <v-toolbar-title>
            <v-icon start>mdi-receipt</v-icon>
            Sale Details - {{ selectedSale.invoice_number }}
          </v-toolbar-title>
          <v-spacer></v-spacer>
          <v-chip v-if="selectedSale.is_voided" color="error" variant="flat" class="mr-2">
            <v-icon start>mdi-cancel</v-icon>
            VOIDED
          </v-chip>
          <v-toolbar-items>
            <v-btn icon @click="detailDialog = false">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </v-toolbar-items>
        </v-toolbar>

        <v-card-text class="pa-6">
          <!-- Header Info -->
          <v-row class="mb-6">
            <v-col cols="12" md="4">
              <v-card variant="outlined" class="pa-3">
                <div class="text-caption text-grey mb-1">SALE DATE</div>
                <div class="text-h6">{{ formatDateLong(selectedSale.sale_date) }}</div>
              </v-card>
            </v-col>
            <v-col cols="12" md="4">
              <v-card variant="outlined" class="pa-3">
                <div class="text-caption text-grey mb-1">CUSTOMER</div>
                <div class="text-h6">{{ selectedSale.customer?.name || 'Walk-in Customer' }}</div>
              </v-card>
            </v-col>
            <v-col cols="12" md="4">
              <v-card variant="outlined" class="pa-3">
                <div class="text-caption text-grey mb-1">PAYMENT METHOD</div>
                <div class="text-h6">
                  <v-chip :color="getPaymentMethodColor(selectedSale.payment_method)" size="small">
                    {{ formatPaymentMethod(selectedSale.payment_method) }}
                  </v-chip>
                </div>
              </v-card>
            </v-col>
          </v-row>

          <!-- Split Payment Section -->
          <div v-if="isSplitPayment(selectedSale)" class="mb-6">
            <v-card variant="outlined">
              <v-card-title class="bg-grey-lighten-4">
                <v-icon start>mdi-credit-card-multiple</v-icon>
                Payment Breakdown
              </v-card-title>
              <v-card-text class="pa-4">
                <v-table>
                  <thead>
                    <tr>
                      <th>Method</th>
                      <th>Reference</th>
                      <th class="text-right">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="payment in selectedSale.payments" :key="payment.id">
                      <td>
                        <div class="d-flex align-center">
                          <v-icon :color="getPaymentMethodColor(payment.payment_method)" class="mr-2">
                            {{ getPaymentMethodIcon(payment.payment_method) }}
                          </v-icon>
                          {{ formatPaymentMethod(payment.payment_method) }}
                        </div>
                      </td>
                      <td class="text-grey">{{ payment.reference || 'N/A' }}</td>
                      <td class="text-right font-weight-bold">
                        ₦{{ formatNumber(payment.amount) }}
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="2" class="text-right font-weight-bold">Total Paid:</td>
                      <td class="text-right text-success text-h6">
                        ₦{{ formatNumber(selectedSale.amount_paid) }}
                      </td>
                    </tr>
                  </tfoot>
                </v-table>
              </v-card-text>
            </v-card>
          </div>

          <!-- Items Table -->
          <v-card variant="outlined" class="mb-6">
            <v-card-title class="bg-grey-lighten-4">
              <v-icon start>mdi-cart</v-icon>
              Items Purchased
            </v-card-title>
            <v-card-text class="pa-0">
              <v-table>
                <thead>
                  <tr class="bg-grey-lighten-3">
                    <th>PRODUCT</th>
                    <th class="text-center">QUANTITY</th>
                    <th class="text-right">UNIT PRICE</th>
                    <th class="text-right">SUBTOTAL</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in selectedSale.items" :key="item.id">
                    <td>
                      <div class="d-flex align-center">
                        <v-avatar size="36" color="grey-lighten-3" class="mr-3">
                          <v-icon>mdi-package-variant</v-icon>
                        </v-avatar>
                        <div>
                          <div class="font-weight-medium">{{ item.product?.name }}</div>
                          <div class="text-caption text-grey">{{ item.product?.sku || 'N/A' }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <v-chip size="small" variant="outlined">
                        {{ item.quantity }}
                      </v-chip>
                    </td>
                    <td class="text-right font-weight-medium">
                      ₦{{ formatNumber(item.unit_price) }}
                    </td>
                    <td class="text-right font-weight-bold">
                      ₦{{ formatNumber(item.subtotal) }}
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </v-card-text>
          </v-card>

          <!-- Totals -->
          <v-row>
            <v-col cols="12" md="8"></v-col>
            <v-col cols="12" md="4">
              <v-card variant="outlined">
                <v-card-text class="pa-4">
                  <div class="d-flex justify-space-between mb-3">
                    <span class="text-grey">Subtotal:</span>
                    <span class="font-weight-medium">₦{{ formatNumber(selectedSale.subtotal_amount) }}</span>
                  </div>
                  <div v-if="selectedSale.discount_amount > 0" class="d-flex justify-space-between mb-3">
                    <span class="text-grey">Discount:</span>
                    <span class="font-weight-medium text-error">-₦{{ formatNumber(selectedSale.discount_amount) }}</span>
                  </div>
                  <div v-if="selectedSale.tax_amount > 0" class="d-flex justify-space-between mb-3">
                    <span class="text-grey">Tax:</span>
                    <span class="font-weight-medium">₦{{ formatNumber(selectedSale.tax_amount) }}</span>
                  </div>
                  <v-divider class="my-3"></v-divider>
                  <div class="d-flex justify-space-between text-h6 mb-4">
                    <span>Total:</span>
                    <span class="text-primary font-weight-bold">₦{{ formatNumber(selectedSale.total_amount) }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span>Amount Paid:</span>
                    <span class="text-success font-weight-bold">₦{{ formatNumber(selectedSale.amount_paid) }}</span>
                  </div>
                  <div v-if="selectedSale.change_amount > 0" class="d-flex justify-space-between mt-2">
                    <span>Change:</span>
                    <span class="text-success">₦{{ formatNumber(selectedSale.change_amount) }}</span>
                  </div>
                  <div v-if="selectedSale.outstanding_amount > 0" class="d-flex justify-space-between mt-2">
                    <span>Outstanding:</span>
                    <span class="text-warning font-weight-bold">₦{{ formatNumber(selectedSale.outstanding_amount) }}</span>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions class="pa-6">
          <v-btn color="primary" variant="flat" @click="downloadReceipt(selectedSale)" prepend-icon="mdi-printer">
            Print Receipt
          </v-btn>
          <v-btn variant="outlined" @click="detailDialog = false">Close</v-btn>
          <v-spacer></v-spacer>
          <v-btn
            v-if="!selectedSale.is_voided && authStore.hasPermission('void_sales')"
            color="error"
            variant="tonal"
            @click="voidSale(selectedSale)"
            prepend-icon="mdi-cancel"
          >
            Void Sale
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Receipt Component -->
    <SaleReceipt
      v-if="receiptSale"
      :sale="receiptSale"
      :show="showReceipt"
      @close="showReceipt = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';
import SaleReceipt from '@/components/receipts/SaleReceipt.vue';

const authStore = useAuthStore();
const loading = ref(false);
const detailDialog = ref(false);
const sales = ref([]);
const stores = ref([]);
const selectedSale = ref(null);
const perPage = ref(15);
const page = ref(1);
const showReceipt = ref(false);
const receiptSale = ref(null);
const dateMenu = ref(false);
const dateRange = ref([]);

const filters = ref({
  search: '',
  store_id: null,
  date_from: '',
  date_to: '',
  sale_type: null,
  payment_status: null,
});

const saleTypes = [
  { title: 'POS Sale', value: 'pos' },
  { title: 'Online Order', value: 'order' },
  { title: 'Invoice', value: 'invoice' },
];

const paymentStatuses = [
  { title: 'Paid', value: 'paid' },
  { title: 'Pending', value: 'pending' },
  { title: 'Partial', value: 'partial' },
  { title: 'Verified', value: 'verified' },
];

const headers = [
  { 
    title: 'INVOICE #', 
    key: 'invoice_number',
    sortable: true,
    width: '180px'
  },
  { 
    title: 'DATE & TIME', 
    key: 'sale_date',
    sortable: true,
    width: '160px'
  },
  { 
    title: 'CUSTOMER', 
    key: 'customer',
    sortable: true
  },
  { 
    title: 'TYPE', 
    key: 'sale_type',
    sortable: true,
    width: '120px'
  },
  { 
    title: 'AMOUNT', 
    key: 'total_amount',
    sortable: true,
    align: 'end',
    width: '150px'
  },
  { 
    title: 'PAYMENT', 
    key: 'payment_status',
    sortable: true,
    width: '130px'
  },
  { 
    title: 'STATUS', 
    key: 'is_voided',
    sortable: true,
    width: '120px'
  },
  { 
    title: 'ACTIONS', 
    key: 'actions', 
    sortable: false,
    align: 'center',
    width: '140px'
  },
];

// Computed Properties
const filteredSales = computed(() => {
  return sales.value.filter(sale => {
    const matchesSearch = !filters.value.search || 
      sale.invoice_number?.toLowerCase().includes(filters.value.search.toLowerCase()) ||
      sale.customer?.name?.toLowerCase().includes(filters.value.search.toLowerCase());
    
    const matchesStore = !filters.value.store_id || sale.store_id === filters.value.store_id;
    const matchesType = !filters.value.sale_type || sale.sale_type === filters.value.sale_type;
    const matchesStatus = !filters.value.payment_status || sale.payment_status === filters.value.payment_status;
    
    return matchesSearch && matchesStore && matchesType && matchesStatus;
  });
});

const totalSales = computed(() => {
  return filteredSales.value.reduce((sum, sale) => sum + (parseFloat(sale.total_amount) || 0), 0);
});

const dailySales = computed(() => {
  const today = new Date().toISOString().split('T')[0];
  return sales.value
    .filter(sale => sale.sale_date?.split('T')[0] === today)
    .reduce((sum, sale) => sum + (parseFloat(sale.total_amount) || 0), 0);
});

const totalOrders = computed(() => filteredSales.value.length);

const pendingPayments = computed(() => {
  return sales.value.filter(sale => ['pending', 'partial'].includes(sale.payment_status)).length;
});

const averageOrderValue = computed(() => {
  return totalOrders.value > 0 ? totalSales.value / totalOrders.value : 0;
});

const dateRangeText = computed(() => {
  if (dateRange.value.length === 2) {
    const [from, to] = dateRange.value;
    return `${formatDate(from)} - ${formatDate(to)}`;
  }
  return 'Select date range';
});

const startIndex = computed(() => (page.value - 1) * perPage.value + 1);
const endIndex = computed(() => Math.min(page.value * perPage.value, filteredSales.value.length));

// Utility Functions
const formatNumber = (num) => {
  if (num === null || num === undefined || isNaN(num)) return '0.00';
  return new Intl.NumberFormat('en-NG', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(parseFloat(num));
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-NG');
};

const formatTime = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleTimeString('en-NG', { hour: '2-digit', minute: '2-digit' });
};

const formatDateLong = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-NG', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatPaymentStatus = (status) => {
  const statusMap = {
    'paid': 'Paid',
    'pending': 'Pending',
    'partial': 'Partial',
    'verified': 'Verified'
  };
  return statusMap[status] || status;
};

// Styling Functions
const getSaleTypeColor = (type) => {
  const colors = { 
    pos: 'primary', 
    order: 'success', 
    invoice: 'info' 
  };
  return colors[type] || 'grey';
};

const getSaleTypeIcon = (type) => {
  const icons = {
    pos: 'mdi-cash-register',
    order: 'mdi-cart',
    invoice: 'mdi-file-document'
  };
  return icons[type] || 'mdi-receipt';
};

const getPaymentStatusColor = (status) => {
  const colors = { 
    paid: 'success', 
    pending: 'warning', 
    partial: 'info', 
    verified: 'success' 
  };
  return colors[status] || 'grey';
};

const getPaymentStatusIcon = (status) => {
  const icons = {
    paid: 'mdi-check-circle',
    pending: 'mdi-clock',
    partial: 'mdi-progress-clock',
    verified: 'mdi-shield-check'
  };
  return icons[status] || 'mdi-help-circle';
};

const getPaymentMethodColor = (method) => {
  const colors = {
    'cash': 'success',
    'card': 'primary',
    'bank_transfer': 'info',
    'wallet': 'purple',
    'mixed': 'orange',
  };
  return colors[method] || 'grey';
};

const getPaymentMethodIcon = (method) => {
  const icons = {
    'cash': 'mdi-cash',
    'card': 'mdi-credit-card',
    'bank_transfer': 'mdi-bank-transfer',
    'wallet': 'mdi-wallet',
    'mixed': 'mdi-cash-multiple',
  };
  return icons[method] || 'mdi-currency-usd';
};

const formatPaymentMethod = (method) => {
  const methods = {
    'cash': 'Cash',
    'card': 'Card',
    'bank_transfer': 'Bank Transfer',
    'wallet': 'Wallet',
    'mixed': 'Mixed Payment',
  };
  return methods[method] || method;
};

// Business Logic
const isSplitPayment = (sale) => {
  return sale.payments && sale.payments.length > 1;
};

const debouncedLoadSales = debounce(() => loadSales(), 300);

function debounce(fn, delay) {
  let timeoutId;
  return function(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn.apply(this, args), delay);
  };
}

const handleDateRangeChange = (dates) => {
  if (dates.length === 2) {
    filters.value.date_from = dates[0];
    filters.value.date_to = dates[1];
    loadSales();
    dateMenu.value = false;
  }
};

const resetFilters = () => {
  filters.value = {
    search: '',
    store_id: null,
    date_from: '',
    date_to: '',
    sale_type: null,
    payment_status: null,
  };
  dateRange.value = [];
  loadSales();
};

const loadSales = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/sales', { params: filters.value });
    sales.value = response.data.data || response.data;
    
    // Normalize data
    sales.value = sales.value.map(sale => ({
      ...sale,
      subtotal_amount: parseFloat(sale.subtotal_amount || sale.subtotal || 0),
      discount_amount: parseFloat(sale.discount_amount || 0),
      tax_amount: parseFloat(sale.tax_amount || 0),
      total_amount: parseFloat(sale.total_amount || 0),
      amount_paid: parseFloat(sale.amount_paid || 0),
      change_amount: parseFloat(sale.change_amount || 0),
      outstanding_amount: parseFloat(sale.outstanding_amount || 0),
    }));
  } catch (error) {
    console.error('Failed to load sales:', error);
  } finally {
    loading.value = false;
  }
};

const viewSale = async (sale) => {
  try {
    const response = await axios.get(`/api/sales/${sale.id}`);
    selectedSale.value = response.data;
    
    // Normalize data
    if (selectedSale.value) {
      selectedSale.value.subtotal_amount = parseFloat(selectedSale.value.subtotal_amount || selectedSale.value.subtotal || 0);
      selectedSale.value.discount_amount = parseFloat(selectedSale.value.discount_amount || 0);
      selectedSale.value.tax_amount = parseFloat(selectedSale.value.tax_amount || 0);
      selectedSale.value.total_amount = parseFloat(selectedSale.value.total_amount || 0);
      selectedSale.value.amount_paid = parseFloat(selectedSale.value.amount_paid || 0);
      selectedSale.value.change_amount = parseFloat(selectedSale.value.change_amount || 0);
      selectedSale.value.outstanding_amount = parseFloat(selectedSale.value.outstanding_amount || 0);
      
      if (selectedSale.value.items) {
        selectedSale.value.items = selectedSale.value.items.map(item => ({
          ...item,
          subtotal: parseFloat(item.subtotal || (item.quantity * item.unit_price) || 0),
        }));
      }
    }
    
    detailDialog.value = true;
  } catch (error) {
    console.error('Failed to load sale details:', error);
  }
};

const downloadReceipt = async (sale) => {
  try {
    let fullSale = sale;
    if (!sale.items || !sale.payments) {
      const response = await axios.get(`/api/sales/${sale.id}`);
      fullSale = response.data;
    }

    fullSale = {
      ...fullSale,
      subtotal: fullSale.subtotal_amount || fullSale.subtotal || 0,
      subtotal_amount: parseFloat(fullSale.subtotal_amount || fullSale.subtotal || 0),
      discount_amount: parseFloat(fullSale.discount_amount || 0),
      tax_amount: parseFloat(fullSale.tax_amount || 0),
      total_amount: parseFloat(fullSale.total_amount || 0),
      amount_paid: parseFloat(fullSale.amount_paid || 0),
      change_amount: parseFloat(fullSale.change_amount || 0),
      outstanding_amount: parseFloat(fullSale.outstanding_amount || 0),
      items: fullSale.items?.map(item => ({
        ...item,
        line_total: parseFloat(item.line_total || item.subtotal || (item.quantity * item.unit_price) || 0),
        subtotal: parseFloat(item.subtotal || (item.quantity * item.unit_price) || 0),
      })) || [],
    };

    receiptSale.value = fullSale;
    showReceipt.value = true;
  } catch (error) {
    console.error('Failed to load receipt:', error);
    alert('Failed to load receipt');
  }
};

const voidSale = async (sale) => {
  if (confirm(`Are you sure you want to void sale ${sale.invoice_number}? This action cannot be undone.`)) {
    try {
      await axios.post(`/api/sales/${sale.id}/void`);
      loadSales();
      detailDialog.value = false;
    } catch (error) {
      console.error('Failed to void sale:', error);
      alert('Failed to void sale');
    }
  }
};

const exportToCSV = () => {
  // Implement CSV export logic
  alert('CSV export feature would be implemented here');
};

const printReport = () => {
  window.print();
};

onMounted(async () => {
  try {
    const storesRes = await axios.get('/api/stores');
    stores.value = storesRes.data.data || storesRes.data;
  } catch (error) {
    console.error('Failed to load stores:', error);
    stores.value = [];
  }
  await loadSales();
});
</script>

<style scoped>
.sales-history-container {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
  padding: 24px;
}

.summary-card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  border-left: 4px solid transparent;
}

.summary-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 20px -10px rgba(0,0,0,0.2) !important;
}

.border-left-success {
  border-left-color: #4CAF50 !important;
}

.border-left-primary {
  border-left-color: #2196F3 !important;
}

.border-left-warning {
  border-left-color: #FF9800 !important;
}

.border-left-info {
  border-left-color: #00BCD4 !important;
}

.cursor-pointer {
  cursor: pointer;
  transition: color 0.2s ease;
}

.cursor-pointer:hover {
  color: #1976D2 !important;
}

.v-data-table {
  border-radius: 8px;
  overflow: hidden;
}

.v-data-table :deep(.v-table__wrapper) {
  border-radius: 8px;
}

.v-data-table :deep(thead) {
  background-color: #f8f9fa;
}

.v-data-table :deep(th) {
  font-weight: 600 !important;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.75rem;
  color: #5f6368;
}

.v-data-table :deep(tbody tr:hover) {
  background-color: #f5f7fa !important;
}

.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}

.text-decoration-none {
  text-decoration: none;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}

/* Print styles */
@media print {
  .sales-history-container {
    padding: 0;
    background: white;
  }
  
  .v-card,
  .v-data-table {
    box-shadow: none !important;
  }
}
</style>