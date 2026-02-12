<template>
  <v-dialog v-model="dialogOpen" max-width="420" persistent>
    <v-card>
      <v-card-title>{{ dialogState.title }}</v-card-title>
      <v-card-text>
        {{ dialogState.message }}
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn
          v-if="dialogState.type === 'confirm'"
          variant="text"
          @click="handleCancel"
        >
          {{ dialogState.cancelText }}
        </v-btn>
        <v-btn
          :color="dialogState.color"
          variant="flat"
          @click="handleConfirm"
        >
          {{ dialogState.confirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed } from 'vue';
import { useDialog } from '@/composables/useDialog';

const { state, closeDialog } = useDialog();

const dialogState = computed(() => state);

const dialogOpen = computed({
  get: () => state.open,
  set: (value) => {
    if (!value) closeDialog(false);
  },
});

const handleConfirm = () => closeDialog(true);
const handleCancel = () => closeDialog(false);
</script>
