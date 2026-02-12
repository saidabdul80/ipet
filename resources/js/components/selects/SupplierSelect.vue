<template>
  <div>
    <AddNewSelect
      v-bind="forwardedAttrs"
      :model-value="modelValue"
      :items="items"
      :label="label"
      :add-new-text="addNewText"
      :control="control"
      :show-add-new="showAddNew"
      @update:model-value="value => emit('update:modelValue', value)"
      @add-new="openDialog"
    />

    <v-dialog v-model="dialog" max-width="600px" persistent>
      <v-card>
        <v-card-title>New Supplier</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.name"
                  label="Supplier Name *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.phone"
                  label="Phone *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.email"
                  label="Email"
                  variant="outlined"
                  type="email"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch
                  v-model="formData.is_active"
                  label="Active"
                  color="primary"
                ></v-switch>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.address"
                  label="Address"
                  variant="outlined"
                  rows="2"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveSupplier">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, ref, useAttrs } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
import AddNewSelect from './AddNewSelect.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  modelValue: {
    type: [String, Number, Array, Object, Boolean, null],
    default: null,
  },
  items: {
    type: Array,
    default: () => [],
  },
  label: {
    type: String,
    default: 'Supplier',
  },
  addNewText: {
    type: String,
    default: 'Add New Supplier',
  },
  control: {
    type: String,
    default: 'select',
  },
  showAddNew: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:modelValue', 'created']);
const attrs = useAttrs();
const forwardedAttrs = computed(() => {
  const { 'onUpdate:modelValue': _onUpdateModelValue, 'onUpdate:model-value': _onUpdateModelValueKebab, ...rest } = attrs;
  return rest;
});
const { success, handleError } = useToast();

const dialog = ref(false);
const saving = ref(false);
const form = ref(null);
const formData = ref({
  name: '',
  phone: '',
  email: '',
  address: '',
  is_active: true,
});

const openDialog = () => {
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
  formData.value = {
    name: '',
    phone: '',
    email: '',
    address: '',
    is_active: true,
  };
  if (form.value?.resetValidation) form.value.resetValidation();
};

const saveSupplier = async () => {
  const validation = await form.value?.validate();
  if (validation === false || validation?.valid === false) return;

  saving.value = true;
  try {
    const response = await axios.post('/api/suppliers', formData.value);
    const supplier = response.data?.supplier || response.data;
    success('Supplier created successfully');
    emit('created', supplier);
    emit('update:modelValue', supplier.id);
    closeDialog();
  } catch (error) {
    handleError(error, 'Failed to create supplier');
  } finally {
    saving.value = false;
  }
};
</script>
