<template>
  <v-menu
    v-model="menu"
    :close-on-content-click="false"
    transition="scale-transition"
  >
    <template #activator="{ props: activatorProps }">
      <v-text-field
        v-bind="mergeTextFieldProps(activatorProps)"
        :label="label"
        :model-value="displayValue"
        :variant="variant"
        :density="density"
        :clearable="clearable"
        :readonly="true"
        @click:clear="clearDate"
      />
    </template>
    <v-date-picker
      :model-value="modelValue"
      :min="min"
      :max="max"
      @update:model-value="selectDate"
    />
  </v-menu>
</template>

<script setup>
import { computed, ref, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  modelValue: {
    type: [String, null],
    default: null,
  },
  label: {
    type: String,
    default: '',
  },
  variant: {
    type: String,
    default: 'outlined',
  },
  density: {
    type: String,
    default: 'comfortable',
  },
  clearable: {
    type: Boolean,
    default: false,
  },
  min: {
    type: String,
    default: undefined,
  },
  max: {
    type: String,
    default: undefined,
  },
});

const emit = defineEmits(['update:modelValue', 'change']);
const attrs = useAttrs();
const menu = ref(false);

const textFieldAttrs = computed(() => {
  const { 'onUpdate:modelValue': _onUpdateModelValue, 'onUpdate:model-value': _onUpdateModelValueKebab, ...rest } = attrs;
  return rest;
});

const mergeTextFieldProps = (activatorProps) => ({
  ...textFieldAttrs.value,
  ...activatorProps,
});

const toYmd = (value) => {
  if (!value) return '';
  if (typeof value === 'string') {
    if (value.includes('T')) return value.split('T')[0];
    return value;
  }
  if (value instanceof Date) {
    return value.toISOString().split('T')[0];
  }
  return String(value);
};

const displayValue = computed(() => toYmd(props.modelValue));

const selectDate = (value) => {
  const raw = Array.isArray(value) ? value[0] : value;
  const nextValue = raw ? toYmd(raw) : null;
  emit('update:modelValue', nextValue);
  emit('change', nextValue);
  menu.value = false;
};

const clearDate = () => {
  emit('update:modelValue', null);
  emit('change', null);
};
</script>
