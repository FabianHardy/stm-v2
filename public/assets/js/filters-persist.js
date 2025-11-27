/**
 * Script de persistance des filtres STM
 *
 * Sauvegarde et restaure automatiquement les filtres des pages admin
 * en utilisant sessionStorage (ne persiste pas après fermeture du navigateur)
 *
 * @author Fabian Hardy
 * @version 1.0.0
 * @created 2025/11/27
 *
 * Usage: Ajouter data-filter-persist="nom_page" sur les éléments <select> ou <input>
 * Exemple: <select data-filter-persist="stats_campaigns" name="country">
 */

(function() {
    'use strict';

    // Préfixe pour les clés sessionStorage
    const STORAGE_PREFIX = 'stm_filters_';

    /**
     * Récupère la clé de stockage basée sur la page actuelle
     * @returns {string}
     */
    function getPageKey() {
        // Utilise le pathname comme clé unique
        const path = window.location.pathname
            .replace(/^\/stm\/admin\//, '')
            .replace(/\//g, '_')
            .replace(/[^a-zA-Z0-9_]/g, '');
        return STORAGE_PREFIX + (path || 'dashboard');
    }

    /**
     * Sauvegarde tous les filtres de la page
     */
    function saveFilters() {
        const pageKey = getPageKey();
        const filters = {};

        // Récupérer tous les éléments avec data-filter-persist ou les selects/inputs de filtres courants
        const filterElements = document.querySelectorAll(
            '[data-filter-persist], ' +
            'select[name="country"], ' +
            'select[name="campaign_id"], ' +
            'select[name="campaign"], ' +
            'select[name="status"], ' +
            'select[name="period"], ' +
            'select[name="category"], ' +
            'input[name="search"], ' +
            'input[name="q"], ' +
            'input[type="date"]'
        );

        filterElements.forEach(function(element) {
            const key = element.name || element.id || element.getAttribute('data-filter-persist');
            if (key && element.value) {
                filters[key] = element.value;
            }
        });

        // Sauvegarder uniquement s'il y a des filtres
        if (Object.keys(filters).length > 0) {
            try {
                sessionStorage.setItem(pageKey, JSON.stringify(filters));
            } catch (e) {
                console.warn('STM Filters: Impossible de sauvegarder les filtres', e);
            }
        }
    }

    /**
     * Restaure les filtres sauvegardés
     */
    function restoreFilters() {
        const pageKey = getPageKey();

        try {
            const savedFilters = sessionStorage.getItem(pageKey);
            if (!savedFilters) return;

            const filters = JSON.parse(savedFilters);

            Object.keys(filters).forEach(function(key) {
                // Chercher l'élément par name, id ou data-filter-persist
                const element = document.querySelector(
                    '[name="' + key + '"], ' +
                    '#' + key + ', ' +
                    '[data-filter-persist="' + key + '"]'
                );

                if (element && filters[key]) {
                    // Vérifier que la valeur existe dans les options (pour les select)
                    if (element.tagName === 'SELECT') {
                        const optionExists = Array.from(element.options).some(function(opt) {
                            return opt.value === filters[key];
                        });
                        if (optionExists) {
                            element.value = filters[key];
                        }
                    } else {
                        element.value = filters[key];
                    }
                }
            });

            // Déclencher les événements change pour les frameworks JS (Alpine.js, etc.)
            Object.keys(filters).forEach(function(key) {
                const element = document.querySelector(
                    '[name="' + key + '"], ' +
                    '#' + key + ', ' +
                    '[data-filter-persist="' + key + '"]'
                );
                if (element) {
                    element.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

        } catch (e) {
            console.warn('STM Filters: Impossible de restaurer les filtres', e);
        }
    }

    /**
     * Efface les filtres de la page courante
     */
    function clearFilters() {
        const pageKey = getPageKey();
        try {
            sessionStorage.removeItem(pageKey);
        } catch (e) {
            console.warn('STM Filters: Impossible d\'effacer les filtres', e);
        }
    }

    /**
     * Initialise les écouteurs d'événements
     */
    function init() {
        // Restaurer les filtres au chargement
        document.addEventListener('DOMContentLoaded', function() {
            // Petit délai pour laisser Alpine.js s'initialiser
            setTimeout(restoreFilters, 100);
        });

        // Sauvegarder les filtres quand ils changent
        document.addEventListener('change', function(e) {
            const target = e.target;
            if (
                target.matches('[data-filter-persist]') ||
                target.matches('select[name="country"]') ||
                target.matches('select[name="campaign_id"]') ||
                target.matches('select[name="campaign"]') ||
                target.matches('select[name="status"]') ||
                target.matches('select[name="period"]') ||
                target.matches('select[name="category"]') ||
                target.matches('input[name="search"]') ||
                target.matches('input[name="q"]') ||
                target.matches('input[type="date"]')
            ) {
                saveFilters();
            }
        });

        // Sauvegarder aussi sur input pour les champs texte
        document.addEventListener('input', function(e) {
            const target = e.target;
            if (
                target.matches('input[name="search"]') ||
                target.matches('input[name="q"]')
            ) {
                // Debounce pour éviter trop de sauvegardes
                clearTimeout(target._saveTimeout);
                target._saveTimeout = setTimeout(saveFilters, 500);
            }
        });

        // Sauvegarder avant de quitter la page
        window.addEventListener('beforeunload', saveFilters);
    }

    // Exposer les fonctions globalement si besoin
    window.STMFilters = {
        save: saveFilters,
        restore: restoreFilters,
        clear: clearFilters
    };

    // Initialiser
    init();

})();