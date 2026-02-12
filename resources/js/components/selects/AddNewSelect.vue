<template>
  <component
    :is="componentName"
    v-bind="attrs"
    :items="items"
    :label="label"
    :model-value="modelValue"
    @update:model-value="update"
  >
    <slot />
    <template v-if="showAddNew" #append-item>
      <v-divider />
      <v-list-item class="add-new-select__item" @click.stop="emit('add-new')">
        <template #prepend>
          <v-icon size="18" color="primary">mdi-plus</v-icon>
        </template>
        <v-list-item-title class="text-primary">
          {{ addNewText }}
        </v-list-item-title>
      </v-list-item>
    </template>
  </component>
</template>

<script setup>
import { computed, useAttrs } from 'vue';

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
    default: '',
  },
  addNewText: {
    type: String,
    default: 'Add New',
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

const emit = defineEmits(['update:modelValue', 'add-new']);
const attrs = useAttrs();

const componentName = computed(() => (props.control === 'autocomplete' ? 'v-autocomplete' : 'v-select'));
const isDisabled = computed(() => Boolean(attrs.disabled) || Boolean(attrs.readonly));

const update = (value) => {
  emit('update:modelValue', value);
};
</script>

<style scoped>
.add-new-select__item {
  cursor: pointer;
}
</style>
