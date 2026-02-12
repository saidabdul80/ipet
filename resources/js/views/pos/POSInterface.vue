<template>
  <div>
    <!-- Receipt Dialog -->
    <SaleReceipt v-model="showReceipt" :sale="completedSale" />

    <!-- Split Payment Dialog -->
    <SplitPaymentDialog
      v-model="showSplitPayment"
      :total-amount="total"
      @confirm="handleSplitPayment"
    />

    <v-row>
      <v-col cols="12" md="8">
        <!-- Product Search -->
        <v-card class="mb-4">
          <v-card-text>
            <v-text-field
              v-model="searchQuery"
              label="Search Product (Name, SKU, or Barcode)"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              autofocus
              @keyup.enter="searchProduct"
              @input="searchProduct"
            ></v-text-field>

            <!-- Product Results -->
            <v-list v-if="searchResults.length > 0" class="mt-2">
              <v-list-item
                v-for="product in searchResults"
                :key="product.id"
                @click="addToCart(product)"
                class="cursor-pointer"
              >
                <template v-slot:prepend>
                  <v-icon>mdi-package-variant</v-icon>
                </template>
                <v-list-item-title>{{ product.name }}</v-list-item-title>
                <v-list-item-subtitle>{{ product.sku }} - ₦{{ formatNumber(product.selling_price) }}</v-list-item-subtitle>
                <template v-slot:append>
                  <v-chip size="small" color="primary">Stock: {{ product.stock_quantity || 0 }}</v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>

        <!-- Quick Product Grid -->
        <v-card v-if="isMobile && quickCollapsed && lastQuickSelected" class="mb-4">
          <v-card-title class="d-flex align-center justify-space-between">
            <span>Quick Select</span>
            <v-btn variant="text" size="small" @click="quickCollapsed = false">Change</v-btn>
          </v-card-title>
          <v-card-text class="d-flex align-center justify-space-between">
            <div>
              <div class="text-subtitle-2">{{ lastQuickSelected.name }}</div>
              <div class="text-caption text-grey">
                ₦{{ formatNumber(lastQuickSelected.selling_price) }}
              </div>
            </div>
            <v-chip size="small" color="primary">
              Qty: {{ cart.find(item => item.id === lastQuickSelected.id)?.quantity || 0 }}
            </v-chip>
          </v-card-text>
        </v-card>

        <v-card v-else>
          <v-card-title>Quick Select</v-card-title>
          <v-card-text>
            <v-row>
              <v-col
                v-for="product in quickProducts"
                :key="product.id"
                cols="6"
                md="3"
              >
                <v-card
                  @click="addToCart(product)"
                  class="cursor-pointer hover:shadow-lg"
                  variant="outlined"
                >
                  <v-card-text class="text-center">
                    <v-icon size="48" color="primary">mdi-package-variant</v-icon>
                    <div class="text-subtitle-2 mt-2">{{ product.name }}</div>
                    <div class="text-caption text-grey">₦{{ formatNumber(product.selling_price) }}</div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Cart -->
      <v-col cols="12" md="4">
        <v-card>
          <v-card-title class="bg-primary text-white">
            Shopping Cart
            <v-spacer></v-spacer>
            <v-btn icon size="small" @click="clearCart" v-if="cart.length > 0">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
          </v-card-title>

          <v-card-text style="max-height: 400px; overflow-y: auto;">
            <v-list v-if="cart.length > 0">
              <v-list-item v-for="(item, index) in cart" :key="index">
                <v-list-item-title>
                  {{ item.name }}
                  <v-chip v-if="item.selling_price !== item.original_price" size="x-small" color="success" class="ml-2">
                    Special Price
                  </v-chip>
                </v-list-item-title>
                <v-list-item-subtitle>
                  <div v-if="item.selling_price !== item.original_price" class="text-caption text-grey mb-1">
                    <span class="text-decoration-line-through">₦{{ formatNumber(item.original_price) }}</span>
                    → ₦{{ formatNumber(item.selling_price) }}
                  </div>
                  <v-row align="center" class="mt-2">
                    <v-col cols="6">
                      <v-text-field
                        v-model.number="item.quantity"
                        type="number"
                        min="1"
                        density="compact"
                        variant="outlined"
                        hide-details
                        @input="updateCartItem(index)"
                      >
                        <template v-slot:append-inner v-if="item.unit">
                          <span class="text-caption text-grey">{{ item.unit?.short_name }}</span>
                        </template>
                      </v-text-field>
                    </v-col>
                    <v-col cols="6" class="text-right">
                      ₦{{ formatNumber(item.quantity * item.selling_price) }}
                    </v-col>
                  </v-row>
                </v-list-item-subtitle>
                <template v-slot:append>
                  <v-btn icon size="small" @click="removeFromCart(index)">
                    <v-icon>mdi-close</v-icon>
                  </v-btn>
                </template>
              </v-list-item>
            </v-list>
            <div v-else class="text-center text-grey py-8">
              <v-icon size="64">mdi-cart-outline</v-icon>
              <div class="mt-2">Cart is empty</div>
            </div>
          </v-card-text>

          <v-divider></v-divider>

          <!-- Cart Summary -->
          <v-card-text>
            <v-row>
              <v-col cols="6">Subtotal:</v-col>
              <v-col cols="6" class="text-right font-weight-bold">₦{{ formatNumber(subtotal) }}</v-col>
            </v-row>
            <v-row>
              <v-col cols="6">
                <v-text-field
                  v-model.number="discountAmount"
                  label="Discount"
                  type="number"
                  min="0"
                  density="compact"
                  variant="outlined"
                  hide-details
                  prefix="₦"
                ></v-text-field>
              </v-col>
              <v-col cols="6">
                <v-text-field
                  v-model.number="taxAmount"
                  label="Tax"
                  type="number"
                  min="0"
                  density="compact"
                  variant="outlined"
                  hide-details
                  prefix="₦"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row class="mt-4">
              <v-col cols="6" class="text-h6">Total:</v-col>
              <v-col cols="6" class="text-right text-h6 text-primary">₦{{ formatNumber(total) }}</v-col>
            </v-row>
          </v-card-text>

          <v-divider></v-divider>

          <!-- Payment Options -->
          <v-card-text>
            <v-select
              v-model="paymentMethod"
              :items="paymentMethods"
              label="Payment Method"
              variant="outlined"
              density="compact"
            ></v-select>

            <v-select
              v-model="selectedCustomer"
              :items="customers"
              item-title="name"
              item-value="id"
              label="Customer (Optional)"
              variant="outlined"
              density="compact"
              clearable
              class="mt-2"
            ></v-select>
          </v-card-text>

          <v-card-actions class="flex-column pa-4">
            <v-btn
              block
              color="success"
              size="large"
              :disabled="cart.length === 0"
              :loading="processing"
              @click="processSale"
              class="mb-2"
            >
              <v-icon left>mdi-cash-register</v-icon>
              Complete Sale
            </v-btn>
            <v-btn
              block
              color="primary"
              size="large"
              variant="outlined"
              :disabled="cart.length === 0"
              @click="showSplitPayment = true"
            >
              <v-icon left>mdi-cash-multiple</v-icon>
              Split Payment
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import axios from 'axios';
import SaleReceipt from '@/components/receipts/SaleReceipt.vue';
import SplitPaymentDialog from '@/components/pos/SplitPaymentDialog.vue';
import { useDisplay } from 'vuetify';

const authStore = useAuthStore();
const { success, handleError } = useToast();
const { smAndDown } = useDisplay();
const isMobile = computed(() => smAndDown.value);

const searchQuery = ref('');
const searchResults = ref([]);
const quickProducts = ref([]);
const cart = ref([]);
const discountAmount = ref(0);
const taxAmount = ref(0);
const paymentMethod = ref('cash');
const selectedCustomer = ref(null);
const walkInCustomer = ref(null);
const customers = ref([]);
const processing = ref(false);
const showReceipt = ref(false);
const completedSale = ref(null);
const showSplitPayment = ref(false);
const splitPayments = ref([]);
const quickCollapsed = ref(false);
const lastQuickSelected = ref(null);

const paymentMethods = [
  { title: 'Cash', value: 'cash' },
  { title: 'Bank Transfer', value: 'bank_transfer' },
  { title: 'Card', value: 'card' },
  { title: 'Wallet', value: 'wallet' },
];

const subtotal = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
});

const total = computed(() => {
  return subtotal.value - discountAmount.value + taxAmount.value;
});

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-NG').format(num);
};

const searchProduct = async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = [];
    return;
  }

  try {
    const response = await axios.get('/api/products', {
      params: { search: searchQuery.value }
    });
    searchResults.value = response.data.data || response.data;
  } catch (error) {
    console.error('Search failed:', error);
  }
};

const addToCart = async (product) => {
  // Apply customer pricing
  let finalPrice = product.selling_price;
  let priceSource = 'default';

  if (selectedCustomer.value) {
    try {
      const customer = customers.value.find(c => c.id === selectedCustomer.value);

      // Check for customer-specific pricing
      const pricingResponse = await axios.get(`/api/customers/${selectedCustomer.value}/pricing`);
      const customerPricing = pricingResponse.data.find(p => p.product_id === product.id);

      if (customerPricing && customerPricing.special_price) {
        finalPrice = customerPricing.special_price;
        priceSource = 'customer_special';
        console.log(`Applied customer special price for ${product.name}: ₦${finalPrice}`);
      }
      // Check for category pricing (wholesaler/retailer)
      else if (customer?.category === 'wholesaler' && product.wholesale_price) {
        finalPrice = product.wholesale_price;
        priceSource = 'wholesaler';
        console.log(`Applied wholesaler price for ${product.name}: ₦${finalPrice}`);
      } else if (customer?.category === 'retailer' && product.retailer_price) {
        finalPrice = product.retailer_price;
        priceSource = 'retailer';
        console.log(`Applied retailer price for ${product.name}: ₦${finalPrice}`);
      }
    } catch (error) {
      console.error('Failed to get customer pricing:', error);
      // Fall back to default selling price
    }
  }

  const existingItem = cart.value.find(item => item.id === product.id);
  if (existingItem) {
    existingItem.quantity++;
    // Update price if it changed (e.g., customer was selected after adding item)
    if (existingItem.selling_price !== finalPrice) {
      existingItem.selling_price = finalPrice;
      existingItem.original_price = product.selling_price;
      console.log(`Updated price for existing item ${product.name}: ₦${finalPrice}`);
    }
  } else {
    cart.value.push({
      ...product,
      quantity: 1,
      selling_price: finalPrice,
      original_price: product.selling_price,
      price_source: priceSource,
    });
    console.log(`Added ${product.name} to cart at ₦${finalPrice} (${priceSource})`);
  }
  lastQuickSelected.value = product;
  if (isMobile.value) {
    quickCollapsed.value = true;
  }
  searchQuery.value = '';
  searchResults.value = [];
};

const removeFromCart = (index) => {
  cart.value.splice(index, 1);
};

const updateCartItem = (index) => {
  if (cart.value[index].quantity < 1) {
    cart.value[index].quantity = 1;
  }
};

const clearCart = () => {
  cart.value = [];
  discountAmount.value = 0;
  taxAmount.value = 0;
  quickCollapsed.value = false;
  lastQuickSelected.value = null;
  // Reset to walk-in customer
  if (walkInCustomer.value) {
    selectedCustomer.value = walkInCustomer.value.id;
  }
};

const handleSplitPayment = (payments) => {
  splitPayments.value = payments;
  processSaleWithPayments(payments);
};

const processSale = async () => {
  // Single payment method
  processSaleWithPayments(null);
};

const processSaleWithPayments = async (payments = null) => {
  // Get user's store ID (primary or first accessible store)
  let userStoreId = authStore.user?.store_id;

  // If no primary store, try to get first accessible store
  if (!userStoreId && authStore.user?.stores && authStore.user.stores.length > 0) {
    userStoreId = authStore.user.stores[0].id;
  }

  // Validate store assignment
  if (!userStoreId) {
    handleError({ response: { data: { message: 'No store assigned to your account. Please contact administrator.' } } });
    return;
  }

  if (cart.value.length === 0) {
    handleError({ response: { data: { message: 'Cart is empty. Please add items to cart.' } } });
    return;
  }

  processing.value = true;
  try {
    const saleData = {
      customer_id: selectedCustomer.value,
      store_id: userStoreId,
      sale_type: 'pos',
      discount_amount: discountAmount.value,
      tax_amount: taxAmount.value,
      amount_paid: total.value,
      items: cart.value.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
        unit_id: item.unit_id || null,
        unit_price: item.selling_price,
      })),
    };

    // Add payment information
    if (payments && payments.length > 0) {
      // Split payment
      saleData.payments = payments;
      saleData.payment_method = 'mixed';
    } else {
      // Single payment
      saleData.payment_method = paymentMethod.value;
    }

    const response = await axios.post('/api/sales', saleData);
    success('Sale completed successfully!');

    // Store the completed sale and show receipt dialog
    completedSale.value = response.data.sale;
    showReceipt.value = true;

    clearCart();
    splitPayments.value = [];
  } catch (error) {
    console.error('Sale failed:', error);
    handleError(error, 'Failed to complete sale');
  } finally {
    processing.value = false;
  }
};

// Watch for customer changes and update cart prices
watch(selectedCustomer, async (newCustomerId, oldCustomerId) => {
  if (!newCustomerId || newCustomerId === oldCustomerId || cart.value.length === 0) {
    console.log('Customer watcher: skipping update', { newCustomerId, oldCustomerId, cartLength: cart.value.length });
    return;
  }

  console.log(`Customer changed from ${oldCustomerId} to ${newCustomerId}, updating ${cart.value.length} cart items...`);

  // Update prices for all items in cart
  for (const item of cart.value) {
    let finalPrice = item.original_price || item.selling_price;
    let priceSource = 'default';

    try {
      const customer = customers.value.find(c => c.id === newCustomerId);

      // Check for customer-specific pricing
      const pricingResponse = await axios.get(`/api/customers/${newCustomerId}/pricing`);
      const customerPricing = pricingResponse.data.find(p => p.product_id === item.id);

      if (customerPricing && customerPricing.special_price) {
        finalPrice = customerPricing.special_price;
        priceSource = 'customer_special';
      }
      // Check for category pricing (wholesaler/retailer)
      else if (customer?.category === 'wholesaler' && item.wholesale_price) {
        finalPrice = item.wholesale_price;
        priceSource = 'wholesaler';
      } else if (customer?.category === 'retailer' && item.retailer_price) {
        finalPrice = item.retailer_price;
        priceSource = 'retailer';
      }

      console.log(`Updated ${item.name}: ₦${item.selling_price} → ₦${finalPrice} (${priceSource})`);
    } catch (error) {
      console.error('Failed to get customer pricing:', error);
    }

    item.selling_price = finalPrice;
    item.price_source = priceSource;
  }
});

watch(cart, (items) => {
  if (!items.length) {
    quickCollapsed.value = false;
    lastQuickSelected.value = null;
  }
});

onMounted(async () => {
  // Load quick products
  const productsRes = await axios.get('/api/products', { params: { per_page: 8 } });
  quickProducts.value = productsRes.data.data || productsRes.data;

  // Load customers
  const customersRes = await axios.get('/api/customers');
  customers.value = customersRes.data.data || customersRes.data;

  // Find and set walk-in customer as default
  walkInCustomer.value = customers.value.find(c => c.code === 'CUST00000');
  if (walkInCustomer.value) {
    selectedCustomer.value = walkInCustomer.value.id;
  }
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
