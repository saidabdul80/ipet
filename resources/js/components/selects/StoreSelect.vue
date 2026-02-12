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

    <v-dialog v-model="dialog" max-width="700px" persistent>
      <v-card>
        <v-card-title>New Store</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.name"
                  label="Store Name *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="formData.code"
                  label="Store Code *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.branch_id"
                  :items="branches"
                  item-title="name"
                  item-value="id"
                  label="Branch *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                  :loading="loadingBranches"
                ></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="formData.type"
                  :items="storeTypes"
                  label="Store Type *"
                  variant="outlined"
                  :rules="[v => !!v || 'Required']"
                ></v-select>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="formData.address"
                  label="Address"
                  variant="outlined"
                  rows="2"
                ></v-textarea>
              </v-col>
              <v-col cols="12" md="6">
                <v-switch
                  v-model="formData.is_active"
                  label="Active"
                  color="primary"
                ></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveStore">Save</v-btn>
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
    default: 'Store',
  },
  addNewText: {
    type: String,
    default: 'Add New Store',
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
const branches = ref([]);
const loadingBranches = ref(false);

const storeTypes = ['warehouse', 'retail', 'showroom'];

const formData = ref({
  name: '',
  code: '',
  branch_id: null,
  type: null,
  address: '',
  is_active: true,
});

const openDialog = async () => {
  dialog.value = true;
  if (!branches.value.length) {
    await loadBranches();
  }
};

const closeDialog = () => {
  dialog.value = false;
  formData.value = {
    name: '',
    code: '',
    branch_id: null,
    type: null,
    address: '',
    is_active: true,
  };
  if (form.value?.resetValidation) form.value.resetValidation();
};

const loadBranches = async () => {
  loadingBranches.value = true;
  try {
    const response = await axios.get('/api/branches', { params: { per_page: 1000 } });
    branches.value = response.data.data || response.data;
  } catch (error) {
    handleError(error, 'Failed to load branches');
  } finally {
    loadingBranches.value = false;
  }
};

const saveStore = async () => {
  const validation = await form.value?.validate();
  if (validation === false || validation?.valid === false) return;

  saving.value = true;
  try {
    const response = await axios.post('/api/stores', formData.value);
    const store = response.data?.store || response.data;
    success('Store created successfully');
    emit('created', store);
    emit('update:modelValue', store.id);
    closeDialog();
  } catch (error) {
    handleError(error, 'Failed to create store');
  } finally {
    saving.value = false;
  }
};
</script>
