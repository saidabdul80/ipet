<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Reports</h1>
      </v-col>
    </v-row>

    <v-tabs v-model="activeTab" color="primary">
      <v-tab value="sales">Sales Report</v-tab>
      <v-tab value="inventory">Inventory Report</v-tab>
      <v-tab value="profitability">Profitability Report</v-tab>
    </v-tabs>

    <v-window v-model="activeTab" class="mt-4">
      <!-- Sales Report -->
      <v-window-item value="sales">
        <v-card>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="3">
                <DatePickerField
                  v-model="salesFilters.date_from"
                  label="From Date"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="12" md="3">
                <DatePickerField
                  v-model="salesFilters.date_to"
                  label="To Date"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="12" md="3">
                <StoreSelect
                  v-model="salesFilters.store_id"
                  :items="stores"
                  item-title="name"
                  item-value="id"
                  label="Store"
                  variant="outlined"
                  density="compact"
                  clearable
                  @created="store => stores.push(store)"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-btn color="primary" @click="loadSalesReport" :loading="loadingSales">
                  Generate Report
                </v-btn>
                <v-btn color="success" @click="exportSalesPDF" class="ml-2" :disabled="!salesData">
                  <v-icon left>mdi-file-pdf-box</v-icon>
                  PDF
                </v-btn>
              </v-col>
            </v-row>

            <!-- Sales Summary -->
            <v-row v-if="salesData" class="mt-4">
              <v-col cols="12" md="3">
                <v-card color="primary" dark>
                  <v-card-text>
                    <div class="text-caption">Total Sales</div>
                    <div class="text-h5">{{ salesData.summary.total_sales }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="3">
                <v-card color="success" dark>
                  <v-card-text>
                    <div class="text-caption">Total Amount</div>
                    <div class="text-h5">₦{{ formatNumber(salesData.summary.total_amount) }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="3">
                <v-card color="info" dark>
                  <v-card-text>
                    <div class="text-caption">Gross Profit</div>
                    <div class="text-h5">₦{{ formatNumber(salesData.summary.gross_profit) }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="3">
                <v-card color="warning" dark>
                  <v-card-text>
                    <div class="text-caption">Total Discount</div>
                    <div class="text-h5">₦{{ formatNumber(salesData.summary.total_discount) }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <!-- Sales Table -->
            <v-data-table
              v-if="salesData"
              :headers="salesHeaders"
              :items="salesData.sales"
              class="mt-4"
            >
              <template v-slot:item.total_amount="{ item }">
                ₦{{ formatNumber(item.total_amount) }}
              </template>
              <template v-slot:item.discount_amount="{ item }">
                ₦{{ formatNumber(item.discount_amount) }}
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Inventory Report -->
      <v-window-item value="inventory">
        <v-card>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <StoreSelect
                  v-model="inventoryFilters.store_id"
                  :items="stores"
                  item-title="name"
                  item-value="id"
                  label="Store *"
                  variant="outlined"
                  density="compact"
                  @created="store => stores.push(store)"
                />
              </v-col>
              <v-col cols="12" md="3">
                <CategorySelect
                  v-model="inventoryFilters.category_id"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  label="Category"
                  variant="outlined"
                  density="compact"
                  clearable
                  @created="category => categories.push(category)"
                />
              </v-col>
              <v-col cols="12" md="2">
                <v-switch
                  v-model="inventoryFilters.low_stock_only"
                  label="Low Stock Only"
                  color="primary"
                ></v-switch>
              </v-col>
              <v-col cols="12" md="3">
                <v-btn color="primary" @click="loadInventoryReport" :loading="loadingInventory">
                  Generate Report
                </v-btn>
                <v-btn color="success" @click="exportInventoryPDF" class="ml-2" :disabled="!inventoryData">
                  <v-icon left>mdi-file-pdf-box</v-icon>
                  PDF
                </v-btn>
              </v-col>
            </v-row>

            <!-- Inventory Summary -->
            <v-row v-if="inventoryData" class="mt-4">
              <v-col cols="12" md="4">
                <v-card color="primary" dark>
                  <v-card-text>
                    <div class="text-caption">Total Products</div>
                    <div class="text-h5">{{ inventoryData.summary.total_products }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="4">
                <v-card color="success" dark>
                  <v-card-text>
                    <div class="text-caption">Total Stock Value</div>
                    <div class="text-h5">₦{{ formatNumber(inventoryData.summary.total_stock_value) }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="4">
                <v-card color="warning" dark>
                  <v-card-text>
                    <div class="text-caption">Low Stock Items</div>
                    <div class="text-h5">{{ inventoryData.summary.low_stock_items }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <!-- Inventory Table -->
            <v-data-table
              v-if="inventoryData"
              :headers="inventoryHeaders"
              :items="inventoryData.inventory"
              class="mt-4"
            >
              <template v-slot:item.stock_value="{ item }">
                ₦{{ formatNumber(item.stock_value) }}
              </template>
              <template v-slot:item.status="{ item }">
                <v-chip :color="item.is_low_stock ? 'warning' : 'success'" size="small">
                  {{ item.status }}
                </v-chip>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- Profitability Report -->
      <v-window-item value="profitability">
        <v-card>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="3">
                <DatePickerField
                  v-model="profitFilters.date_from"
                  label="From Date"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="12" md="3">
                <DatePickerField
                  v-model="profitFilters.date_to"
                  label="To Date"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="12" md="3">
                <StoreSelect
                  v-model="profitFilters.store_id"
                  :items="stores"
                  item-title="name"
                  item-value="id"
                  label="Store"
                  variant="outlined"
                  density="compact"
                  clearable
                  @created="store => stores.push(store)"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-btn color="primary" @click="loadProfitabilityReport" :loading="loadingProfit">
                  Generate Report
                </v-btn>
              </v-col>
            </v-row>

            <!-- Profitability Summary -->
            <v-row v-if="profitData" class="mt-4">
              <v-col cols="12" md="3">
                <v-card color="primary" dark>
                  <v-card-text>
                    <div class="text-caption">Total Revenue</div>
                    <div class="text-h5">₦{{ formatNumber(profitData.summary.total_revenue) }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="3">
                <v-card color="error" dark>
                  <v-card-text>
                    <div class="text-caption">Total COGS</div>
                    <div class="text-h5">₦{{ formatNumber(profitData.summary.total_cogs) }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="3">
                <v-card color="success" dark>
                  <v-card-text>
                    <div class="text-caption">Gross Profit</div>
                    <div class="text-h5">₦{{ formatNumber(profitData.summary.gross_profit) }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="3">
                <v-card color="info" dark>
                  <v-card-text>
                    <div class="text-caption">Gross Margin</div>
                    <div class="text-h5">{{ profitData.summary.gross_margin_percentage }}%</div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-window-item>
    </v-window>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import StoreSelect from '@/components/selects/StoreSelect.vue';
import CategorySelect from '@/components/selects/CategorySelect.vue';
import DatePickerField from '@/components/inputs/DatePickerField.vue';
import { useDialog } from '@/composables/useDialog';

const activeTab = ref('sales');
const stores = ref([]);
const categories = ref([]);
const { alert } = useDialog();

const loadingSales = ref(false);
const loadingInventory = ref(false);
const loadingProfit = ref(false);

const salesData = ref(null);
const inventoryData = ref(null);
const profitData = ref(null);

const salesFilters = ref({
  date_from: new Date().toISOString().split('T')[0],
  date_to: new Date().toISOString().split('T')[0],
  store_id: null,
});

const inventoryFilters = ref({
  store_id: null,
  category_id: null,
  low_stock_only: false,
});

const profitFilters = ref({
  date_from: new Date().toISOString().split('T')[0],
  date_to: new Date().toISOString().split('T')[0],
  store_id: null,
});

const salesHeaders = [
  { title: 'Date', key: 'sale_date' },
  { title: 'Invoice #', key: 'invoice_number' },
  { title: 'Customer', key: 'customer.name' },
  { title: 'Amount', key: 'total_amount' },
  { title: 'Discount', key: 'discount_amount' },
];

const inventoryHeaders = [
  { title: 'SKU', key: 'sku' },
  { title: 'Product', key: 'name' },
  { title: 'Category', key: 'category' },
  { title: 'Quantity', key: 'current_quantity' },
  { title: 'Stock Value', key: 'stock_value' },
  { title: 'Status', key: 'status' },
];

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-NG').format(num);
};

const loadSalesReport = async () => {
  loadingSales.value = true;
  try {
    const response = await axios.get('/api/reports/sales', { params: salesFilters.value });
    salesData.value = response.data;
  } catch (error) {
    console.error('Failed to load sales report:', error);
  } finally {
    loadingSales.value = false;
  }
};

const loadInventoryReport = async () => {
  if (!inventoryFilters.value.store_id) {
    alert('Please select a store');
    return;
  }
  loadingInventory.value = true;
  try {
    const response = await axios.get('/api/reports/inventory', { params: inventoryFilters.value });
    inventoryData.value = response.data;
  } catch (error) {
    console.error('Failed to load inventory report:', error);
  } finally {
    loadingInventory.value = false;
  }
};

const loadProfitabilityReport = async () => {
  loadingProfit.value = true;
  try {
    const response = await axios.get('/api/reports/profitability', { params: profitFilters.value });
    profitData.value = response.data;
  } catch (error) {
    console.error('Failed to load profitability report:', error);
  } finally {
    loadingProfit.value = false;
  }
};

const exportSalesPDF = () => {
  const params = new URLSearchParams(salesFilters.value).toString();
  window.open(`/api/reports/sales/pdf?${params}`, '_blank');
};

const exportInventoryPDF = () => {
  const params = new URLSearchParams(inventoryFilters.value).toString();
  window.open(`/api/reports/inventory/pdf?${params}`, '_blank');
};

onMounted(async () => {
  const storesRes = await axios.get('/api/stores');
  stores.value = storesRes.data.data || storesRes.data;

  const categoriesRes = await axios.get('/api/categories');
  categories.value = categoriesRes.data.data || categoriesRes.data;
});
</script>
