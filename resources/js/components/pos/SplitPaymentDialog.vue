<template>
  <v-dialog v-model="dialog" max-width="700px" persistent>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center bg-primary">
        <span class="text-white">Split Payment</span>
        <v-btn icon variant="text" @click="close" color="white">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-6">
        <!-- Total Amount -->
        <v-alert type="info" variant="tonal" class="mb-4">
          <div class="d-flex justify-space-between align-center">
            <span class="text-h6">Total Amount:</span>
            <span class="text-h5 font-weight-bold">{{ formatCurrency(totalAmount) }}</span>
          </div>
          <div class="d-flex justify-space-between align-center mt-2">
            <span>Amount Paid:</span>
            <span class="font-weight-bold" :class="paidAmount >= totalAmount ? 'text-success' : 'text-warning'">
              {{ formatCurrency(paidAmount) }}
            </span>
          </div>
          <div class="d-flex justify-space-between align-center mt-2">
            <span>Remaining:</span>
            <span class="font-weight-bold" :class="remainingAmount > 0 ? 'text-error' : 'text-success'">
              {{ formatCurrency(remainingAmount) }}
            </span>
          </div>
        </v-alert>

        <!-- Payment Methods List -->
        <div class="mb-4">
          <div class="d-flex justify-space-between align-center mb-3">
            <h3 class="text-h6">Payment Methods</h3>
            <v-btn
              color="primary"
              size="small"
              prepend-icon="mdi-plus"
              @click="addPaymentMethod"
              :disabled="payments.length >= 4"
            >
              Add Payment
            </v-btn>
          </div>

          <v-card
            v-for="(payment, index) in payments"
            :key="index"
            class="mb-3"
            variant="outlined"
          >
            <v-card-text>
              <v-row>
                <v-col cols="12" md="5">
                  <v-select
                    v-model="payment.payment_method"
                    :items="paymentMethods"
                    label="Payment Method"
                    variant="outlined"
                    density="compact"
                    :rules="[v => !!v || 'Payment method is required']"
                  ></v-select>
                </v-col>
                <v-col cols="12" md="5">
                  <v-text-field
                    v-model.number="payment.amount"
                    label="Amount"
                    type="number"
                    variant="outlined"
                    density="compact"
                    prefix="₦"
                    :rules="[
                      v => !!v || 'Amount is required',
                      v => v > 0 || 'Amount must be greater than 0'
                    ]"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="2" class="d-flex align-center">
                  <v-btn
                    icon
                    size="small"
                    color="error"
                    variant="text"
                    @click="removePaymentMethod(index)"
                    :disabled="payments.length === 1"
                  >
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </v-col>
                <v-col v-if="payment.payment_method === 'bank_transfer'" cols="12">
                  <v-text-field
                    v-model="payment.reference"
                    label="Transfer Reference (Optional)"
                    variant="outlined"
                    density="compact"
                    prepend-inner-icon="mdi-receipt"
                  ></v-text-field>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </div>

        <!-- Quick Amount Buttons -->
        <div v-if="remainingAmount > 0" class="mb-4">
          <p class="text-caption mb-2">Quick Add Remaining:</p>
          <v-btn-group density="compact">
            <v-btn size="small" @click="addQuickPayment('cash')">
              <v-icon left size="small">mdi-cash</v-icon>
              Cash
            </v-btn>
            <v-btn size="small" @click="addQuickPayment('card')">
              <v-icon left size="small">mdi-credit-card</v-icon>
              Card
            </v-btn>
            <v-btn size="small" @click="addQuickPayment('bank_transfer')">
              <v-icon left size="small">mdi-bank-transfer</v-icon>
              Transfer
            </v-btn>
            <v-btn size="small" @click="addQuickPayment('wallet')">
              <v-icon left size="small">mdi-wallet</v-icon>
              Wallet
            </v-btn>
          </v-btn-group>
        </div>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn color="grey" variant="text" @click="close">Cancel</v-btn>
        <v-btn
          color="success"
          variant="flat"
          @click="confirmPayment"
          :disabled="!isValid"
          prepend-icon="mdi-check"
        >
          Confirm Payment
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  modelValue: Boolean,
  totalAmount: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const payments = ref([
  { payment_method: 'cash', amount: 0, reference: '' }
]);

const paymentMethods = [
  { title: 'Cash', value: 'cash' },
  { title: 'Card', value: 'card' },
  { title: 'Bank Transfer', value: 'bank_transfer' },
  { title: 'Wallet', value: 'wallet' },
];

const paidAmount = computed(() => {
  return payments.value.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
});

const remainingAmount = computed(() => {
  return Math.max(0, props.totalAmount - paidAmount.value);
});

const isValid = computed(() => {
  // Check if all payments have method and amount
  const allValid = payments.value.every(p => p.payment_method && p.amount > 0);
  // Check if total paid is at least equal to total amount
  const paidEnough = paidAmount.value >= props.totalAmount;
  return allValid && paidEnough;
});

const addPaymentMethod = () => {
  if (payments.value.length < 4) {
    payments.value.push({
      payment_method: 'cash',
      amount: remainingAmount.value,
      reference: ''
    });
  }
};

const removePaymentMethod = (index) => {
  if (payments.value.length > 1) {
    payments.value.splice(index, 1);
  }
};

const addQuickPayment = (method) => {
  const existingIndex = payments.value.findIndex(p => p.payment_method === method && p.amount === 0);
  if (existingIndex >= 0) {
    payments.value[existingIndex].amount = remainingAmount.value;
  } else {
    payments.value.push({
      payment_method: method,
      amount: remainingAmount.value,
      reference: ''
    });
  }
};

const confirmPayment = () => {
  if (isValid.value) {
    emit('confirm', payments.value);
    close();
  }
};

const close = () => {
  dialog.value = false;
  // Reset payments after a short delay
  setTimeout(() => {
    payments.value = [{ payment_method: 'cash', amount: 0, reference: '' }];
  }, 300);
};

const formatCurrency = (value) => {
  if (!value && value !== 0) return '₦0.00';
  return `₦${parseFloat(value).toLocaleString('en-NG', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
};

// Watch for dialog opening and set first payment to total amount
watch(() => props.modelValue, (newVal) => {
  if (newVal && payments.value.length === 1 && payments.value[0].amount === 0) {
    payments.value[0].amount = props.totalAmount;
  }
});
</script>

<style scoped>
.v-card {
  overflow: visible;
}
</style>

