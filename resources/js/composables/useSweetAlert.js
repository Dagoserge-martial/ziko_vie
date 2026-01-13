import { showSuccess, showError, showWarning, showInfo, showValidationErrors, validateRequired } from '../plugins/sweetalert';

export function useSweetAlert() {
    return {
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showValidationErrors,
        validateRequired,
    };
}

