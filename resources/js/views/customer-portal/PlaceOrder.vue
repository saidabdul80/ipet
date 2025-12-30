<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Place Order</h1>
      </v-col>
    </v-row>

    <v-row>
      <!-- Product Catalog -->
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>
            <v-text-field
              v-model="searchQuery"
              label="Search products..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              clearable
              @input="searchProducts"
            ></v-text-field>
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col
                v-for="product in products"
                :key="product.id"
                cols="12"
                md="4"
              >
                <v-card variant="outlined" class="product-card">
                  <v-card-text>
                    <div class="text-h6">{{ product.name }}</div>
                    <div class="text-caption text-grey">{{ product.sku }}</div>
                    <div class="text-h6 text-primary mt-2">
                      ₦{{ formatNumber(product.selling_price) }}
                    </div>
                    <div class="text-caption" v-if="product.stock_quantity">
                      In Stock: {{ product.stock_quantity }}
                    </div>
                  </v-card-text>
                  <v-card-actions>
                    <v-btn
                      color="primary"
                      block
                      @click="addToCart(product)"
                      :disabled="!product.stock_quantity || product.stock_quantity === 0"
                    >
                      <v-icon left>mdi-cart-plus</v-icon>
                      Add to Cart
                    </v-btn>
                  </v-card-actions>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Shopping Cart -->
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
                <v-list-item-title>{{ item.name }}</v-list-item-title>
                <v-list-item-subtitle>
                  <v-row align="center" class="mt-2">
                    <v-col cols="6">
                      <v-text-field
                        v-model.number="item.quantity"
                        type="number"
                        min="1"
                        :max="item.stock_quantity"
                        density="compact"
                        variant="outlined"
                        hide-details
                      ></v-text-field>
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

          <v-card-text>
            <v-row>
              <v-col cols="6" class="text-h6">Total:</v-col>
              <v-col cols="6" class="text-right text-h6 text-primary">
                ₦{{ formatNumber(total) }}
              </v-col>
            </v-row>

            <v-text-field
              v-model="deliveryAddress"
              label="Delivery Address *"
              variant="outlined"
              density="compact"
              class="mt-2"
            ></v-text-field>

            <v-text-field
              v-model="deliveryDate"
              label="Preferred Delivery Date"
              type="date"
              variant="outlined"
              density="compact"
            ></v-text-field>

            <v-textarea
              v-model="notes"
              label="Order Notes"
              variant="outlined"
              rows="2"
            ></v-textarea>
          </v-card-text>

          <v-card-actions>
            <v-btn
              block
              color="success"
              size="large"
              :disabled="cart.length === 0 || !deliveryAddress"
              :loading="placing"
              @click="placeOrder"
            >
              <v-icon left>mdi-check</v-icon>
              Place Order
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const searchQuery = ref('');
const products = ref([]);
const cart = ref([]);
const deliveryAddress = ref('');
const deliveryDate = ref('');
const notes = ref('');
const placing = ref(false);

const total = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
});

const formatNumber = (num) => new Intl.NumberFormat('en-NG').format(num);

const searchProducts = async () => {
  try {
    const params = { search: searchQuery.value, per_page: 12 };
    const response = await axios.get('/api/products', { params });
    products.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to search products:', error);
  }
};

const addToCart = (product) => {
  const existingItem = cart.value.find(item => item.id === product.id);
  if (existingItem) {
    if (existingItem.quantity < product.stock_quantity) {
      existingItem.quantity++;
    } else {
      alert('Cannot add more than available stock');
    }
  } else {
    cart.value.push({ ...product, quantity: 1 });
  }
};

const removeFromCart = (index) => {
  cart.value.splice(index, 1);
};

const clearCart = () => {
  cart.value = [];
};

const placeOrder = async () => {
  if (!deliveryAddress.value) {
    alert('Please enter delivery address');
    return;
  }

  placing.value = true;
  try {
    const orderData = {
      delivery_address: deliveryAddress.value,
      delivery_date: deliveryDate.value,
      notes: notes.value,
      items: cart.value.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
        unit_price: item.selling_price,
      })),
    };

    await axios.post('/api/orders', orderData);
    alert('Order placed successfully!');
    clearCart();
    deliveryAddress.value = '';
    deliveryDate.value = '';
    notes.value = '';
    router.push('/customer-portal/orders');
  } catch (error) {
    console.error('Failed to place order:', error);
    alert('Failed to place order. Please try again.');
  } finally {
    placing.value = false;
  }
};

onMounted(() => {
  searchProducts();
});
</script>

<style scoped>
.product-card {
  height: 100%;
  display: flex;
  flex-direction: column;
}
</style>

