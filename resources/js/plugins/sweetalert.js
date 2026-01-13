import Swal from 'sweetalert2';

// Configuration globale de SweetAlert2 en français
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    },
});

// Configuration globale de SweetAlert2 en français
Swal.mixin({
    confirmButtonText: 'OK',
    cancelButtonText: 'Annuler',
    closeButtonAriaLabel: 'Fermer',
});

// Fonctions utilitaires pour afficher des messages en français
export const showSuccess = (message) => {
    Toast.fire({
        icon: 'success',
        title: message || 'Opération réussie',
    });
};

export const showError = (message) => {
    Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: message || 'Une erreur est survenue',
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc2626',
    });
};

export const showWarning = (message) => {
    Toast.fire({
        icon: 'warning',
        title: message || 'Attention',
    });
};

export const showInfo = (message) => {
    Toast.fire({
        icon: 'info',
        title: message || 'Information',
    });
};

// Fonction pour afficher les messages flash depuis Inertia
export const showFlashMessages = (flash) => {
    if (flash?.success) {
        showSuccess(flash.success);
    }
    if (flash?.error) {
        showError(flash.error);
    }
    if (flash?.warning) {
        showWarning(flash.warning);
    }
    if (flash?.info) {
        showInfo(flash.info);
    }
};

// Fonction pour valider les champs obligatoires
export const validateRequired = (form, fieldName, displayName) => {
    if (!form[fieldName] || (typeof form[fieldName] === 'string' && form[fieldName].trim() === '')) {
        showError(`Le champ "${displayName}" est obligatoire`);
        return false;
    }
    return true;
};

// Fonction pour afficher les erreurs de validation
export const showValidationErrors = (errors) => {
    if (errors && Object.keys(errors).length > 0) {
        // Traduire les messages d'erreur courants
        const translatedErrors = Object.entries(errors).map(([field, messages]) => {
            const fieldName = translateFieldName(field);
            const translatedMessages = Array.isArray(messages) 
                ? messages.map(msg => translateErrorMessage(msg, field))
                : [translateErrorMessage(messages, field)];
            return `${fieldName}: ${translatedMessages.join(', ')}`;
        });
        
        Swal.fire({
            icon: 'error',
            title: 'Erreurs de validation',
            html: translatedErrors.join('<br>'),
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc2626',
        });
    }
};

// Fonction pour traduire les noms de champs
const translateFieldName = (field) => {
    const translations = {
        'nom': 'Nom',
        'prenom': 'Prénom',
        'telephone': 'Téléphone',
        'email': 'Email',
        'password': 'Mot de passe',
        'password_confirmation': 'Confirmation du mot de passe',
        'localite_id': 'Localité',
        'adresse': 'Adresse',
        'date_adhesion': 'Date d\'adhésion',
        'statut': 'Statut',
        'est_utilisateur': 'Créer un compte utilisateur',
    };
    return translations[field] || field;
};

// Fonction pour traduire les messages d'erreur Laravel
const translateErrorMessage = (message, field = '') => {
    if (!message || typeof message !== 'string') {
        return message;
    }
    
    const lowerMessage = message.toLowerCase();
    const lowerField = field.toLowerCase();
    
    // Message spécial pour l'email déjà utilisé
    if ((lowerField === 'email' || lowerField.includes('email')) && 
        (lowerMessage.includes('already been taken') || 
         lowerMessage.includes('unique') || 
         lowerMessage.includes('déjà utilisé') ||
         lowerMessage.includes('already exists'))) {
        return 'Cette adresse email est déjà utilisée. Veuillez en choisir une autre.';
    }
    
    // Traductions des messages d'erreur Laravel courants
    const translations = {
        // Messages de validation génériques
        'required': 'Ce champ est obligatoire',
        'required_if': 'Ce champ est obligatoire',
        'email': 'Veuillez entrer une adresse email valide',
        'confirmed': 'La confirmation ne correspond pas',
        'unique': 'Cette valeur est déjà utilisée',
        'numeric': 'Ce champ doit être un nombre',
        'date': 'Veuillez entrer une date valide',
        'exists': 'La sélection est invalide',
        'in': 'La valeur sélectionnée est invalide',
        'max': 'La valeur est trop longue',
        'min': 'La valeur est trop courte',
        'min:8': 'Le mot de passe doit contenir au moins 8 caractères',
        
        // Messages spécifiques
        'The :attribute field is required.': 'Ce champ est obligatoire',
        'The :attribute must be a valid email address.': 'Veuillez entrer une adresse email valide',
        'The :attribute must be at least :min characters.': 'Ce champ doit contenir au moins :min caractères',
        'The :attribute confirmation does not match.': 'La confirmation ne correspond pas',
        'The :attribute has already been taken.': 'Cette valeur est déjà utilisée',
        'The :attribute must be a number.': 'Ce champ doit être un nombre',
        'The :attribute must be a valid date.': 'Veuillez entrer une date valide',
        'The selected :attribute is invalid.': 'La sélection est invalide',
    };
    
    // Chercher une correspondance exacte
    if (translations[message]) {
        return translations[message];
    }
    
    // Chercher des correspondances partielles
    if (lowerMessage.includes('required')) {
        return 'Ce champ est obligatoire';
    }
    if (lowerMessage.includes('email') && lowerMessage.includes('valid')) {
        return 'Veuillez entrer une adresse email valide';
    }
    if (lowerMessage.includes('confirmed') || lowerMessage.includes('does not match')) {
        return 'La confirmation ne correspond pas';
    }
    if (lowerMessage.includes('already been taken') || lowerMessage.includes('unique')) {
        // Message générique pour les valeurs uniques (sauf email qui est géré plus haut)
        if (lowerField !== 'email' && !lowerField.includes('email')) {
            return 'Cette valeur est déjà utilisée. Veuillez en choisir une autre.';
        }
    }
    if (lowerMessage.includes('at least') && lowerMessage.includes('characters')) {
        const minMatch = message.match(/at least (\d+)/i);
        if (minMatch) {
            return `Ce champ doit contenir au moins ${minMatch[1]} caractères`;
        }
        return 'Ce champ est trop court';
    }
    if (lowerMessage.includes('must be a number') || lowerMessage.includes('numeric')) {
        return 'Ce champ doit être un nombre';
    }
    if (lowerMessage.includes('valid date')) {
        return 'Veuillez entrer une date valide';
    }
    if (lowerMessage.includes('invalid') || lowerMessage.includes('is invalid')) {
        return 'La valeur est invalide';
    }
    
    // Retourner le message original s'il est déjà en français ou non traduit
    return message;
};

export default Swal;

