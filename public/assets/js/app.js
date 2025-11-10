/**
 * STM v2 - JavaScript principal
 * Auteur : Fabian Hardy
 * Date : 04/11/2025
 */

// ===================================
// CONFIGURATION GLOBALE
// ===================================

const STM = {
    // URLs de l'API
    api: {
        base: '/api',
        products: '/api/products',
        cart: '/api/cart',
        customers: '/api/customers',
    },
    
    // Configuration
    config: {
        locale: 'fr',
        currency: '€',
        dateFormat: 'DD/MM/YYYY',
    },
    
    // État global
    state: {
        cart: {
            items: [],
            total: 0,
        },
    },
};

// ===================================
// UTILITAIRES
// ===================================

/**
 * Formater un nombre en devise
 * @param {number} amount - Montant à formater
 * @returns {string}
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat(STM.config.locale, {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

/**
 * Formater une date
 * @param {string|Date} date - Date à formater
 * @returns {string}
 */
function formatDate(date) {
    const d = new Date(date);
    return d.toLocaleDateString(STM.config.locale);
}

/**
 * Afficher une notification toast
 * @param {string} message - Message à afficher
 * @param {string} type - Type de notification (success, error, warning, info)
 */
function showToast(message, type = 'info') {
    // Créer l'élément toast
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 ${getToastClass(type)}`;
    toast.textContent = message;
    
    // Ajouter au DOM
    document.body.appendChild(toast);
    
    // Animation d'entrée
    setTimeout(() => {
        toast.classList.add('opacity-100');
    }, 10);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        toast.classList.remove('opacity-100');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

/**
 * Obtenir la classe CSS pour le toast selon le type
 * @param {string} type
 * @returns {string}
 */
function getToastClass(type) {
    const classes = {
        success: 'bg-green-600 text-white',
        error: 'bg-red-600 text-white',
        warning: 'bg-yellow-600 text-white',
        info: 'bg-blue-600 text-white',
    };
    return classes[type] || classes.info;
}

/**
 * Afficher un spinner de chargement
 * @param {HTMLElement} element - Élément où afficher le spinner
 */
function showSpinner(element) {
    const spinner = document.createElement('div');
    spinner.className = 'spinner w-8 h-8 mx-auto';
    spinner.id = 'loading-spinner';
    element.appendChild(spinner);
}

/**
 * Masquer le spinner de chargement
 */
function hideSpinner() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * Effectuer une requête AJAX
 * @param {string} url - URL de la requête
 * @param {object} options - Options de la requête
 * @returns {Promise}
 */
async function ajax(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    };
    
    const config = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, config);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Erreur AJAX:', error);
        showToast('Une erreur est survenue', 'error');
        throw error;
    }
}

// ===================================
// FONCTIONS PANIER
// ===================================

/**
 * Ajouter un produit au panier
 * @param {number} productId
 * @param {number} quantity
 */
async function addToCart(productId, quantity) {
    try {
        const response = await ajax(`${STM.api.cart}/add`, {
            method: 'POST',
            body: JSON.stringify({ productId, quantity }),
        });
        
        if (response.success) {
            updateCartCount(response.cartCount);
            showToast('Produit ajouté au panier', 'success');
        }
    } catch (error) {
        showToast('Erreur lors de l\'ajout au panier', 'error');
    }
}

/**
 * Mettre à jour le compteur du panier
 * @param {number} count
 */
function updateCartCount(count) {
    const cartBadge = document.getElementById('cart-count');
    if (cartBadge) {
        cartBadge.textContent = count;
        
        // Animation
        cartBadge.classList.add('scale-125');
        setTimeout(() => {
            cartBadge.classList.remove('scale-125');
        }, 200);
    }
}

// ===================================
// FONCTIONS FORMULAIRE
// ===================================

/**
 * Valider un formulaire
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
function validateForm(form) {
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            showFieldError(input, 'Ce champ est obligatoire');
            isValid = false;
        } else {
            clearFieldError(input);
        }
    });
    
    return isValid;
}

/**
 * Afficher une erreur de champ
 * @param {HTMLElement} input
 * @param {string} message
 */
function showFieldError(input, message) {
    input.classList.add('border-red-500');
    
    let errorDiv = input.nextElementSibling;
    if (!errorDiv || !errorDiv.classList.contains('form-error')) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'form-error';
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
    }
    errorDiv.textContent = message;
}

/**
 * Effacer l'erreur d'un champ
 * @param {HTMLElement} input
 */
function clearFieldError(input) {
    input.classList.remove('border-red-500');
    
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('form-error')) {
        errorDiv.remove();
    }
}

// ===================================
// INITIALISATION
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('STM v2 initialisé');
    
    // Gestion des formulaires
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    
    // Confirmation de suppression
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide des alertes
    const alerts = document.querySelectorAll('.alert[data-auto-hide]');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Export pour utilisation globale
window.STM = STM;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.showToast = showToast;
window.addToCart = addToCart;
