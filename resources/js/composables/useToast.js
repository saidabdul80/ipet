import { useToast as useToastification } from 'vue-toastification';

export function useToast() {
  const toast = useToastification();

  /**
   * Show success toast
   */
  const success = (message, options = {}) => {
    toast.success(message, options);
  };

  /**
   * Show error toast
   */
  const error = (message, options = {}) => {
    toast.error(message, options);
  };

  /**
   * Show warning toast
   */
  const warning = (message, options = {}) => {
    toast.warning(message, options);
  };

  /**
   * Show info toast
   */
  const info = (message, options = {}) => {
    toast.info(message, options);
  };

  /**
   * Handle API error and show appropriate toast
   */
  const handleError = (err, defaultMessage = 'An error occurred') => {
    console.error('API Error:', err);

    if (err.response) {
      const { status, data } = err.response;
      console.log('API Error Response:', err.response);

      // Handle validation errors (422)
      if (status === 422 && data.errors) {
        const errors = Object.values(data.errors).flat();
        errors.forEach((errorMsg) => {
          error(errorMsg, { timeout: 5000 });
        });
        return;
      }

      // Handle other error responses
      if (data.message) {
        error(data.message, { timeout: 5000 });
        return;
      }

      // Handle specific status codes
      switch (status) {
        case 401:
          error('Unauthorized. Please login again.');
          break;
        case 403:
          error('You do not have permission to perform this action.');
          break;
        case 404:
          error('Resource not found.');
          break;
        case 500:
          error('Server error. Please try again later.');
          break;
        default:
          error(defaultMessage);
      }
    } else if (err.request) {
      // Request was made but no response received
      error('Network error. Please check your connection.');
    } else {
      // Something else happened
      error(defaultMessage);
    }
  };

  return {
    success,
    error,
    warning,
    info,
    handleError,
  };
}

