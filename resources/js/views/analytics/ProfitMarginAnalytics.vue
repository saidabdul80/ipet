<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Profit Margin Analytics</h1>
      </v-col>
    </v-row>

    <!-- Summary Cards -->
    <v-row>
      <v-col cols="12" md="3">
        <v-card>
          <v-card-text>
            <div class="text-caption text-grey">Total Products</div>
            <div class="text-h4">{{ summary.total_products || 0 }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="success">
          <v-card-text>
            <div class="text-caption">Profitable Products</div>
            <div class="text-h4">{{ summary.profitable_count || 0 }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="error">
          <v-card-text>
            <div class="text-caption">Loss-Making Products</div>
            <div class="text-h4">{{ summary.loss_count || 0 }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card color="primary">
          <v-card-text>
            <div class="text-caption">Average Margin</div>
            <div class="text-h4">{{ summary.average_margin || 0 }}%</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Filters -->
    <v-row class="mt-4">
      <v-col cols="12" md="4">
        <v-select
          v-model="selectedPeriod"
          :items="periods"
          label="Time Period"
          variant="outlined"
          @update:modelValue="loadAnalytics"
        ></v-select>
      </v-col>
      <v-col cols="12" md="4">
        <CategorySelect
          v-model="selectedCategory"
          :items="categories"
          item-title="name"
          item-value="id"
          label="Category (All)"
          variant="outlined"
          clearable
          @update:modelValue="loadAnalytics"
          @created="category => categories.push(category)"
        />
      </v-col>
      <v-col cols="12" md="4">
        <v-text-field
          v-model="marginThreshold"
          label="Low Margin Threshold (%)"
          type="number"
          variant="outlined"
          @update:modelValue="loadLowMarginProducts"
        ></v-text-field>
      </v-col>
    </v-row>

    <!-- Top and Worst Performers -->
    <v-row class="mt-4">
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="bg-success">
            <v-icon left>mdi-trophy</v-icon>
            Top 10 Performers
          </v-card-title>
          <v-card-text>
            <v-table density="compact">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Margin %</th>
                  <th>Profit</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="product in topPerformers" :key="product.id">
                  <td>
                    <div class="font-weight-bold">{{ product.name }}</div>
                    <div class="text-caption text-grey">{{ product.sku }}</div>
                  </td>
                  <td>
                    <v-chip :color="getMarginColor(product.profit_margin)" size="small">
                      {{ product.profit_margin }}%
                    </v-chip>
                  </td>
                  <td>₦{{ formatNumber(product.profit_amount) }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="bg-error">
            <v-icon left>mdi-alert</v-icon>
            Worst 10 Performers
          </v-card-title>
          <v-card-text>
            <v-table density="compact">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Margin %</th>
                  <th>Profit</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="product in worstPerformers" :key="product.id">
                  <td>
                    <div class="font-weight-bold">{{ product.name }}</div>
                    <div class="text-caption text-grey">{{ product.sku }}</div>
                  </td>
                  <td>
                    <v-chip :color="getMarginColor(product.profit_margin)" size="small">
                      {{ product.profit_margin }}%
                    </v-chip>
                  </td>
                  <td>₦{{ formatNumber(product.profit_amount) }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Low Margin Products Alert -->
    <v-row class="mt-4" v-if="lowMarginProducts.length > 0">
      <v-col cols="12">
        <v-card color="warning">
          <v-card-title>
            <v-icon left>mdi-alert-circle</v-icon>
            Products Below {{ marginThreshold }}% Margin ({{ lowMarginProducts.length }})
          </v-card-title>
          <v-card-text>
            <v-table density="compact">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Cost Price</th>
                  <th>Selling Price</th>
                  <th>Margin %</th>
                  <th>Profit</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in lowMarginProducts" :key="item.product.id">
                  <td>
                    <div class="font-weight-bold">{{ item.product.name }}</div>
                    <div class="text-caption text-grey">{{ item.product.sku }}</div>
                  </td>
                  <td>₦{{ formatNumber(item.cost_price) }}</td>
                  <td>₦{{ formatNumber(item.selling_price) }}</td>
                  <td>
                    <v-chip :color="getMarginColor(item.profit_margin)" size="small">
                      {{ item.profit_margin }}%
                    </v-chip>
                  </td>
                  <td :class="item.is_loss ? 'text-error' : ''">
                    ₦{{ formatNumber(item.profit_amount) }}
                  </td>
                  <td>
                    <v-btn size="small" color="primary" @click="viewProductHistory(item.product.id)">
                      View History
                    </v-btn>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Product Price History Dialog -->
    <v-dialog v-model="historyDialog" max-width="1000px" persistent>
      <v-card v-if="selectedProduct">
        <v-card-title>
          Price History - {{ selectedProduct.name }}
          <v-spacer></v-spacer>
          <v-btn icon @click="historyDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <!-- Current Margin -->
          <v-row class="mb-4">
            <v-col cols="3">
              <div class="text-caption text-grey">Cost Price</div>
              <div class="text-h6">₦{{ formatNumber(currentMargin.cost_price) }}</div>
            </v-col>
            <v-col cols="3">
              <div class="text-caption text-grey">Selling Price</div>
              <div class="text-h6">₦{{ formatNumber(currentMargin.selling_price) }}</div>
            </v-col>
            <v-col cols="3">
              <div class="text-caption text-grey">Profit Margin</div>
              <div class="text-h6">
                <v-chip :color="getMarginColor(currentMargin.profit_margin)">
                  {{ currentMargin.profit_margin }}%
                </v-chip>
              </div>
            </v-col>
            <v-col cols="3">
              <div class="text-caption text-grey">Profit Amount</div>
              <div class="text-h6">₦{{ formatNumber(currentMargin.profit_amount) }}</div>
            </v-col>
          </v-row>

          <!-- Price Trends Chart Placeholder -->
          <div class="mb-4">
            <h3>Price Trends</h3>
            <div v-if="priceTrends.labels && priceTrends.labels.length > 0" class="chart-container">
              <canvas ref="priceChart"></canvas>
            </div>
            <div v-else class="text-center text-grey pa-8">
              No price history available
            </div>
          </div>

          <!-- Price History Table -->
          <v-table density="compact">
            <thead>
              <tr>
                <th>Date</th>
                <th>Change Type</th>
                <th>Old Cost</th>
                <th>New Cost</th>
                <th>Old Selling</th>
                <th>New Selling</th>
                <th>Margin Change</th>
                <th>Changed By</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="history in priceHistory" :key="history.id">
                <td>{{ formatDate(history.changed_at) }}</td>
                <td>
                  <v-chip size="small" :color="getChangeTypeColor(history.change_type)">
                    {{ formatChangeType(history.change_type) }}
                  </v-chip>
                </td>
                <td>₦{{ formatNumber(history.old_cost_price) }}</td>
                <td>₦{{ formatNumber(history.new_cost_price) }}</td>
                <td>₦{{ formatNumber(history.old_selling_price) }}</td>
                <td>₦{{ formatNumber(history.new_selling_price) }}</td>
                <td>
                  <span :class="history.new_profit_margin > history.old_profit_margin ? 'text-success' : 'text-error'">
                    {{ history.old_profit_margin }}% → {{ history.new_profit_margin }}%
                  </span>
                </td>
                <td>{{ history.changed_by?.name || 'System' }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';
import CategorySelect from '@/components/selects/CategorySelect.vue';

Chart.register(...registerables);

const summary = ref({});
const topPerformers = ref([]);
const worstPerformers = ref([]);
const lowMarginProducts = ref([]);
const categories = ref([]);
const selectedPeriod = ref('30days');
const selectedCategory = ref(null);
const marginThreshold = ref(20);
const historyDialog = ref(false);
const selectedProduct = ref(null);
const priceHistory = ref([]);
const priceTrends = ref({});
const currentMargin = ref({});
const priceChart = ref(null);
let chartInstance = null;

const periods = [
  { title: 'Last 7 Days', value: '7days' },
  { title: 'Last 30 Days', value: '30days' },
  { title: 'Last 90 Days', value: '90days' },
  { title: 'Last 6 Months', value: '6months' },
  { title: 'Last Year', value: '1year' },
];

const loadAnalytics = async () => {
  try {
    const params = { period: selectedPeriod.value };
    if (selectedCategory.value) {
      params.category_id = selectedCategory.value;
    }

    const response = await axios.get('/api/price-analytics/overall', { params });
    summary.value = response.data.summary;
    topPerformers.value = response.data.top_performers;
    worstPerformers.value = response.data.worst_performers;
  } catch (error) {
    console.error('Failed to load analytics:', error);
  }
};

const loadLowMarginProducts = async () => {
  try {
    const response = await axios.get('/api/price-analytics/low-margin', {
      params: { threshold: marginThreshold.value }
    });
    lowMarginProducts.value = response.data.products;
  } catch (error) {
    console.error('Failed to load low margin products:', error);
  }
};

const loadCategories = async () => {
  try {
    const response = await axios.get('/api/categories');
    categories.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load categories:', error);
  }
};

const viewProductHistory = async (productId) => {
  try {
    const [historyResponse, trendsResponse] = await Promise.all([
      axios.get(`/api/price-analytics/products/${productId}/history`),
      axios.get(`/api/price-analytics/products/${productId}/trends`, {
        params: { period: selectedPeriod.value }
      })
    ]);

    selectedProduct.value = historyResponse.data.product;
    priceHistory.value = historyResponse.data.history;
    priceTrends.value = trendsResponse.data.trends;
    currentMargin.value = trendsResponse.data.current_margin;

    historyDialog.value = true;

    // Render chart after dialog opens
    setTimeout(() => renderChart(), 100);
  } catch (error) {
    console.error('Failed to load product history:', error);
  }
};

const renderChart = () => {
  if (!priceChart.value || !priceTrends.value.labels) return;

  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy();
  }

  const ctx = priceChart.value.getContext('2d');
  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: priceTrends.value.labels,
      datasets: [
        {
          label: 'Cost Price',
          data: priceTrends.value.cost_prices,
          borderColor: 'rgb(255, 99, 132)',
          backgroundColor: 'rgba(255, 99, 132, 0.1)',
          tension: 0.1
        },
        {
          label: 'Selling Price',
          data: priceTrends.value.selling_prices,
          borderColor: 'rgb(54, 162, 235)',
          backgroundColor: 'rgba(54, 162, 235, 0.1)',
          tension: 0.1
        },
        {
          label: 'Profit Margin %',
          data: priceTrends.value.profit_margins,
          borderColor: 'rgb(75, 192, 192)',
          backgroundColor: 'rgba(75, 192, 192, 0.1)',
          yAxisID: 'y1',
          tension: 0.1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      scales: {
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          title: {
            display: true,
            text: 'Price (₦)'
          }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          title: {
            display: true,
            text: 'Margin (%)'
          },
          grid: {
            drawOnChartArea: false,
          },
        },
      }
    }
  });
};

const getMarginColor = (margin) => {
  if (margin < 0) return 'error';
  if (margin < 10) return 'warning';
  if (margin < 20) return 'orange';
  return 'success';
};

const getChangeTypeColor = (type) => {
  const colors = {
    'manual_update': 'primary',
    'goods_receipt': 'success',
    'price_adjustment': 'warning',
    'bulk_update': 'info'
  };
  return colors[type] || 'default';
};

const formatChangeType = (type) => {
  const labels = {
    'manual_update': 'Manual Update',
    'goods_receipt': 'Goods Receipt',
    'price_adjustment': 'Price Adjustment',
    'bulk_update': 'Bulk Update'
  };
  return labels[type] || type;
};

const formatNumber = (value) => {
  if (!value && value !== 0) return '0.00';
  return parseFloat(value).toLocaleString('en-NG', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleString('en-NG', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  loadAnalytics();
  loadLowMarginProducts();
  loadCategories();
});
</script>

<style scoped>
.chart-container {
  position: relative;
  height: 300px;
  margin: 20px 0;
}
</style>
