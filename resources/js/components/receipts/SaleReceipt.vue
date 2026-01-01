<template>
  <v-dialog v-model="dialog" max-width="400px" persistent>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center bg-grey-lighten-4">
        <span class="text-body-1">Receipt Preview</span>
        <v-btn icon variant="text" size="small" @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text class="pa-0">
        <!-- Thermal Receipt Design -->
        <div id="receipt-content" class="thermal-receipt">
          <!-- Company Header -->
          <div class="receipt-header">
            <div class="company-name">{{ appSettings.app_name }}</div>
            <div v-if="appSettings.company_address" class="info-line">{{ appSettings.company_address }}</div>
            <div v-if="appSettings.company_phone" class="info-line">Tel: {{ appSettings.company_phone }}</div>
            <div v-if="appSettings.company_email" class="info-line">{{ appSettings.company_email }}</div>
          </div>
          <div class="divider-double"></div>
          
          <!-- Receipt Details -->
          <div class="receipt-info">
            <div class="info-row">
              <span class="label">Invoice #:</span>
              <span class="value bold">{{ sale.invoice_number }}</span>
            </div>
            <div class="info-row">
              <span class="label">Date:</span>
              <span class="value">{{ formatDate(sale.sale_date) }}</span>
            </div>
            <div class="info-row">
              <span class="label">Cashier:</span>
              <span class="value">{{ sale.cashier?.name || 'N/A' }}</span>
            </div>
            <div class="info-row">
              <span class="label">Customer:</span>
              <span class="value">{{ sale.customer?.name || 'Walk-in' }}</span>
            </div>
          </div>

          <div class="divider-double"></div>

          <!-- Items Header -->
          <div class="items-header">
            <span class="header-item">ITEM</span>
            <span class="header-qty">QTY</span>
            <span class="header-price">PRICE</span>
            <span class="header-total">TOTAL</span>
          </div>
          <div class="divider-thin">----------------------------------------------------</div>

          <!-- Items -->
          <div class="items-section">
            <div v-for="item in sale.items" :key="item.id" class="item-row">
              <div class="item-row-content">
                <span class="item-name">{{ item.product?.name || 'N/A' }}</span>
                <span class="item-qty">{{ item.quantity }} {{ item.unit?.short_name || '' }}</span>
                <span class="item-price">{{ formatCurrency(item.unit_price) }}</span>
                <span class="item-total bold">{{ formatCurrency(item.line_total) }}</span>
              </div>
            </div>
          </div>

          <div class="divider-double"></div>

          <!-- Totals -->
          <div class="totals-section">
            <div class="total-row">
              <span class="label">Subtotal:</span>
              <span class="value">{{ formatCurrency(sale.subtotal) }}</span>
            </div>
            <div v-if="sale.discount_amount > 0" class="total-row discount">
              <span class="label">Discount:</span>
              <span class="value">-{{ formatCurrency(sale.discount_amount) }}</span>
            </div>
            <div v-if="sale.tax_amount > 0" class="total-row">
              <span class="label">Tax:</span>
              <span class="value">{{ formatCurrency(sale.tax_amount) }}</span>
            </div>
            <div class="divider-thin">----------------------------------------------------</div>
            <div class="total-row grand-total">
              <span class="label">TOTAL:</span>
              <span class="value">{{ formatCurrency(sale.total_amount) }}</span>
            </div>
          </div>

          <div class="divider-double"></div>

          <!-- Payment Details -->
          <div class="payment-section">
            <div v-if="isSplitPayment" class="split-payment">
              <div class="section-title">════ PAYMENT BREAKDOWN ════</div>
              <div v-for="(payment, index) in sale.payments" :key="payment.id" class="payment-item">
                <div class="payment-row">
                  <span class="label">{{ formatPaymentMethod(payment.payment_method) }}:</span>
                  <span class="value bold">{{ formatCurrency(payment.amount) }}</span>
                </div>
                <div v-if="payment.reference" class="payment-ref">
                  Ref: {{ payment.reference }}
                </div>
              </div>
              <div class="divider-thin">--------------------------------</div>
            </div>

            <div class="total-row payment-total">
              <span class="label">Amount Paid:</span>
              <span class="value bold">{{ formatCurrency(sale.amount_paid) }}</span>
            </div>
            <div v-if="sale.change_amount > 0" class="total-row change">
              <span class="label">Change:</span>
              <span class="value bold">{{ formatCurrency(sale.change_amount) }}</span>
            </div>
            <div v-if="sale.outstanding_amount > 0" class="total-row outstanding">
              <span class="label">Outstanding:</span>
              <span class="value bold">{{ formatCurrency(sale.outstanding_amount) }}</span>
            </div>
          </div>

          <div class="divider-double"></div>

          <!-- Footer -->
          <div class="receipt-footer">
            <div class="thank-you">THANK YOU FOR YOUR BUSINESS!</div>
            <div class="info-line">Items are non-returnable once opened</div>
            <div class="info-line">Come again!</div>
            <div v-if="appSettings.company_website" class="info-line">{{ appSettings.company_website }}</div>
            <div class="info-line receipt-date">{{ formatReceiptDate() }}</div>
          </div>
        </div>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn color="grey" variant="text" @click="close">Close</v-btn>
        <v-btn color="primary" variant="flat" @click="printReceipt" prepend-icon="mdi-printer">
          Print Receipt
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAppSettingsStore } from '@/stores/appSettings';

const props = defineProps({
  modelValue: Boolean,
  sale: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['update:modelValue']);

const appSettingsStore = useAppSettingsStore();
const appSettings = computed(() => appSettingsStore.settings);

const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const isSplitPayment = computed(() => {
  return props.sale.payments && props.sale.payments.length > 1;
});

const close = () => {
  dialog.value = false;
};

const printReceipt = () => {
  const printContent = document.getElementById('receipt-content');

  // Create a new window for printing
  const printWindow = window.open('', '_blank');
  printWindow.document.write(`
    <html>
      <head>
        <title>Receipt - ${props.sale.invoice_number}</title>
        <style>
          * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
          }
          body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.2;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm 2mm;
            background: white;
          }
          .thermal-receipt {
            width: 100%;
          }
          .receipt-header {
            text-align: center;
            margin-bottom: 8px;
          }
          
          .company-name {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
          }
          .info-line {
            font-size: 9px;
            margin-bottom: 1px;
          }
          .divider-double {
            text-align: center;
            margin: 6px 0;
            font-size: 10px;
            letter-spacing: -1px;
          }
          .divider-thin {
            text-align: center;
            margin: 4px 0;
            font-size: 10px;
            letter-spacing: -0.5px;
          }
          .receipt-info, .payment-section {
            margin-bottom: 6px;
          }
          .info-row, .total-row, .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 11px;
          }
          .label {
            text-align: left;
          }
          .value {
            text-align: right;
          }
          .bold {
            font-weight: bold;
          }
          /* Fixed Items Section */
          .items-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
          }
          .header-item { 
            flex: 1; 
            text-align: left;
            min-width: 120px;
          }
          .header-qty { 
            width: 40px; 
            text-align: center;
          }
          .header-price { 
            width: 60px; 
            text-align: right;
          }
          .header-total { 
            width: 60px; 
            text-align: right;
          }
          .items-section {
            margin-bottom: 6px;
          }
          .item-row {
            margin-bottom: 4px;
          }
          .item-row-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 11px;
          }
          .item-name {
            flex: 1;
            text-align: left;
            min-width: 120px;
            word-wrap: break-word;
          }
          .item-qty {
            width: 40px;
            text-align: center;
          }
          .item-price {
            width: 60px;
            text-align: right;
          }
          .item-total {
            width: 60px;
            text-align: right;
            font-weight: bold;
          }
          .totals-section {
            margin-bottom: 6px;
          }
          .grand-total {
            font-size: 13px;
            font-weight: bold;
            margin-top: 3px;
            padding-top: 3px;
            border-top: 1px dotted #000;
          }
          .grand-total .label,
          .grand-total .value {
            font-size: 13px;
          }
          .discount .value {
            color: #d32f2f;
          }
          .section-title {
            font-weight: bold;
            margin-bottom: 4px;
            text-align: center;
            font-size: 10px;
            letter-spacing: 0.5px;
          }
          .split-payment {
            margin-bottom: 6px;
            background: #f5f5f5;
            padding: 6px;
            border: 1px dashed #999;
          }
          .payment-item {
            margin-bottom: 3px;
          }
          .payment-ref {
            font-size: 9px;
            margin-left: 10px;
            color: #666;
            font-style: italic;
          }
          .payment-total {
            font-weight: bold;
          }
          .change .value {
            color: #2e7d32;
            font-weight: bold;
          }
          .outstanding {
            font-weight: bold;
          }
          .outstanding .value {
            color: #f57c00;
          }
          .receipt-footer {
            text-align: center;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #999;
          }
          .thank-you {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 1px;
          }
          .receipt-date {
            font-size: 8px;
            color: #666;
            margin-top: 4px;
          }
          @media print {
            body {
              padding: 0;
              width: 80mm;
            }
            @page {
              size: 80mm auto;
              margin: 0;
            }
          }
        </style>
      </head>
      <body>
        ${printContent.innerHTML}
      </body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();

  // Wait for content to load then print
  setTimeout(() => {
    printWindow.print();
    printWindow.close();
  }, 250);
};

const formatCurrency = (value) => {
  if (!value && value !== 0) return `${appSettings.value.currency_symbol || '₦'}0.00`;
  return `${appSettings.value.currency_symbol || '₦'}${parseFloat(value).toLocaleString('en-NG', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleString('en-NG', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatReceiptDate = () => {
  return new Date().toLocaleString('en-NG', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
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
</script>

<style scoped>
.thermal-receipt {
  font-family: 'Courier New', monospace;
  font-size: 11px;
  line-height: 1.2;
  background: white;
  padding: 12px 8px;
  max-width: 80mm;
  margin: 0 auto;
  color: #000;
}

.receipt-header {
  text-align: center;
  margin-bottom: 6px;
}

.company-name {
  font-size: 15px;
  font-weight: bold;
  margin-bottom: 2px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  line-height: 1.1;
}

.info-line {
  font-size: 9px;
  margin-bottom: 1px;
  color: #333;
  line-height: 1.1;
}

.divider-double {
  text-align: center;
  margin: 6px 0;
  color: #000;
  font-size: 10px;
  letter-spacing: -1px;
  line-height: 1;
}

.divider-thin {
  text-align: center;
  margin: 4px 0;
  color: #666;
  font-size: 10px;
  letter-spacing: -0.5px;
  line-height: 1;
}

.receipt-info,
.payment-section {
  margin-bottom: 6px;
}

.info-row,
.total-row,
.payment-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 2px;
  font-size: 11px;
  line-height: 1.2;
}

.label {
  text-align: left;
}

.value {
  text-align: right;
}

.bold {
  font-weight: bold;
}

/* Fixed Items Section with Flexbox */
.items-header {
  display: flex;
  justify-content: space-between;
  font-weight: bold;
  font-size: 10px;
  margin-bottom: 2px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.header-item {
  flex: 1;
  text-align: left;
  min-width: 120px;
}

.header-qty {
  width: 40px;
  text-align: center;
}

.header-price {
  width: 60px;
  text-align: right;
}

.header-total {
  width: 60px;
  text-align: right;
}

.items-section {
  margin-bottom: 6px;
}

.item-row {
  margin-bottom: 4px;
}

.item-row-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  font-size: 11px;
  line-height: 1.2;
}

.item-name {
  flex: 1;
  text-align: left;
  min-width: 120px;
  word-wrap: break-word;
  padding-right: 4px;
}

.item-qty {
  width: 40px;
  text-align: center;
  font-family: 'Courier New', monospace;
}

.item-price {
  width: 60px;
  text-align: right;
  font-family: 'Courier New', monospace;
}

.item-total {
  width: 60px;
  text-align: right;
  font-weight: bold;
  font-family: 'Courier New', monospace;
}

/* Totals Section */
.totals-section {
  margin-bottom: 6px;
}

.grand-total {
  font-size: 13px;
  font-weight: bold;
  margin-top: 3px;
  padding-top: 3px;
  border-top: 1px dotted #000;
}

.grand-total .label,
.grand-total .value {
  font-size: 13px;
}

.discount .value {
  color: #d32f2f;
}

/* Payment Section */
.section-title {
  font-weight: bold;
  margin-bottom: 4px;
  text-align: center;
  font-size: 10px;
  letter-spacing: 0.5px;
}

.split-payment {
  margin-bottom: 6px;
  background: #f5f5f5;
  padding: 6px;
  border: 1px dashed #999;
}

.payment-item {
  margin-bottom: 3px;
}

.payment-ref {
  font-size: 9px;
  margin-left: 10px;
  color: #666;
  font-style: italic;
}

.payment-total {
  font-weight: bold;
}

.change .value {
  color: #2e7d32;
  font-weight: bold;
}

.outstanding {
  font-weight: bold;
}

.outstanding .value {
  color: #f57c00;
}

/* Footer */
.receipt-footer {
  text-align: center;
  margin-top: 8px;
  padding-top: 6px;
  border-top: 1px dashed #999;
}

.thank-you {
  font-size: 12px;
  font-weight: bold;
  margin-bottom: 3px;
  text-transform: uppercase;
  letter-spacing: 1px;
  line-height: 1.2;
}

.receipt-date {
  font-size: 8px;
  color: #666;
  margin-top: 4px;
  font-family: 'Courier New', monospace;
}
</style>