import { reactive, readonly } from 'vue';

const state = reactive({
  open: false,
  type: 'alert',
  title: '',
  message: '',
  confirmText: 'OK',
  cancelText: 'Cancel',
  color: 'primary',
  onConfirm: null,
  onCancel: null,
  resolve: null,
});

const openDialog = (type, message, options = {}) => {
  return new Promise((resolve) => {
    state.type = type;
    state.message = message || '';
    state.title = options.title || (type === 'confirm' ? 'Please Confirm' : 'Notice');
    state.confirmText = options.confirmText || 'OK';
    state.cancelText = options.cancelText || 'Cancel';
    state.color = options.color || 'primary';
    state.onConfirm = options.onConfirm || null;
    state.onCancel = options.onCancel || null;
    state.resolve = resolve;
    state.open = true;
  });
};

const closeDialog = (result) => {
  if (result && typeof state.onConfirm === 'function') {
    state.onConfirm();
  }
  if (!result && typeof state.onCancel === 'function') {
    state.onCancel();
  }
  state.open = false;
  if (state.resolve) {
    state.resolve(result);
  }
  state.resolve = null;
};

export const useDialog = () => {
  const alert = (message, options = {}) => openDialog('alert', message, options);
  const confirm = (message, options = {}) => openDialog('confirm', message, options);

  return {
    state: readonly(state),
    alert,
    confirm,
    closeDialog,
  };
};
