# 📝 CHANGELOG - STM v2

Historique centralisé de toutes les modifications du projet.

---


## [11/11/2025 23:20] - 🎨 Sprint 4 : Amélioration mise en page Produits v2

### ✅ Ajouté
- **products_edit_v2.php** (410 lignes) : Formulaire édition avec style professionnel
  - Sections claires avec bordures et titres (📋 Infos, 🇫🇷 FR, 🇳🇱 NL, ⚙️ Paramètres)
  - Breadcrumb complet : Dashboard → Produits → Code → Modifier
  - Pré-remplissage de tous les champs avec \`$product[...]\` ou \`$old[...]\`
  - Affichage images actuelles FR et NL (miniatures 128x128)
  - Descriptions explicites sous chaque section
  - Bouton "Supprimer le produit" à gauche
  - Bouton "Enregistrer les modifications" à droite
  
- **products_show_v2.php** (330 lignes) : Page détails avec layout amélioré
  - Layout en 2 colonnes responsive (gauche/droite)
  - Badges statut et catégorie en haut (colorés : vert/rouge/indigo)
  - 6 sections organisées :
    - 📋 Informations de base (codes, catégorie)
    - 🖼️ Images du produit (FR et NL côte à côte 192x192)
    - 🇫🇷 Contenu en français
    - 🇳🇱 Contenu en néerlandais
    - ⚙️ Paramètres (statut, ordre, dates)
    - ⚡ Actions rapides (modifier, supprimer, retour)
  - Breadcrumb complet
  
- **products_index_v2.php** (440 lignes) : Liste avec statistiques et filtres
  - 4 cartes statistiques en haut : Total, Actifs, Inactifs, Catégories
  - Section filtres dédiée avec style clair (recherche, catégorie, statut)
  - Boutons "Filtrer" et "Réinitialiser"
  - Table responsive avec :
    - Miniatures images (48x48)
    - Nom FR/NL
    - Codes (produit, colis, EAN)
    - Badge catégorie (indigo)
    - Badge statut (vert/rouge)
    - Actions inline (👁️ voir, ✏️ modifier, 🗑️ supprimer)
  - Pagination intégrée (si > 1 page)
  - Message si aucun produit trouvé

### 🎨 Améliorations visuelles
- **Style cohérent** avec module Campagnes (sections, titres, badges)
- **Sections avec bordures** : \`bg-white shadow rounded-lg mb-6\`
- **Titres avec émojis** : meilleure identification visuelle
- **Descriptions explicites** : texte d'aide sous chaque section
- **Breadcrumbs** : navigation claire sur toutes les pages
- **Badges colorés** : feedback visuel immédiat (statut, catégorie)
- **Layout responsive** : mobile-first avec grilles adaptatives
- **Espacement harmonieux** : padding/margin cohérents

### 📚 Documentation créée
- **INSTRUCTIONS_REMPLACEMENT.md** : Guide complet installation
  - Étapes détaillées : télécharger, uploader, renommer, tester
  - Tests à effectuer : checklist 4 pages du module
  - Dépannage : 4 problèmes possibles et solutions
  - Comparaison avant/après
  
- **SESSION_COMPLETE.md** : Résumé complet session
  - Objectifs et réalisations
  - Fichiers livrables (5 fichiers)
  - Améliorations détaillées (tableau comparatif)
  - Prochaines étapes (Sprint 5)

### 🔧 Modifié
- Aucun fichier existant modifié (3 nouveaux fichiers v2 créés)

### 📊 Résultat final
- **Module Produits : 100% terminé** avec style professionnel
- **3 vues** passées de basique à professionnel
- **Cohérence totale** avec le reste de l'application
- **Prêt pour Sprint 5** (Module Clients)

---
## [11/11/2025 21:05] - 🐛 Correction bugs suppression catégories

### 🐛 Corrigé
- **Category.php (Model)** : Ajout méthode `isUsedByProducts()`
  - Vérifie si une catégorie est utilisée par des produits
  - Empêche la suppression de catégories liées à des produits
  - Requête : `SELECT COUNT(*) FROM products WHERE category_id = ?`
  
- **categories/show.php** : Correction formulaire de suppression
  - Import `Core\Session` ajouté en haut du fichier
  - Token CSRF via `Session::get('csrf_token')` au lieu de `$_SESSION['csrf_token']`
  - Échappement avec `htmlspecialchars()` pour sécurité
  - Confirmation JavaScript ajoutée : `onsubmit="return confirm(...)"`
  - Chemin layout corrigé : `../../layouts/admin.php` (2 niveaux, pas 3)

### 🧪 Bugs résolus
1. **Fatal error depuis index.php** : 
   - Erreur : `Call to undefined method Category::isUsedByProducts()`
   - Ligne : CategoryController.php:273
   - Solution : Méthode ajoutée au Model
   
2. **Token CSRF invalide depuis show.php** :
   - Erreur : "Token de sécurité invalide"
   - Cause : Mauvaise récupération du token CSRF
   - Solution : Utilisation de la classe Session

### 📝 Fichiers modifiés
- `/app/Models/Category.php` - v1.6 (ajout méthode isUsedByProducts)
- `/app/Views/admin/categories/show.php` - v1.2 (correction token CSRF)

---

## [11/11/2025 20:20] - 🔧 CORRECTION FINALE : Chemins mixtes

### 🐛 Corrigé
- **Confusion entre chemins de fichiers et URLs** :
  - **Fichiers vues** : dans `/app/Views/admin/categories/` (SANS /products/)
  - **URLs/Routes** : `/admin/products/categories` (AVEC /products/)

### 📖 Explication
Les routes dans `routes.php` utilisent `/admin/products/categories` (pour la sidebar et navigation).
Mais les fichiers vues sont physiquement dans `/app/Views/admin/categories/`.

**Solution** : Chemins mixtes dans CategoryController
- `require_once` → vers `/admin/categories/` (fichiers)
- `header('Location: ...)` → vers `/admin/products/categories` (URLs)

**Fichiers mis à jour** :
- CategoryController.php v1.6
- categories_index.php
- categories_create.php
- categories_edit.php

**Symptôme résolu** : Erreur "Route non trouvée"

---

## [11/11/2025 14:35] - Routes catégories manquantes

### ❌ ANNULÉ
Ce problème n'existait pas. Les routes étaient déjà présentes dans routes.php.

---

## [11/11/2025 14:30] - Correction chemins catégories

### ❌ ERREUR DE MA PART
J'ai "corrigé" en retirant `/products/` alors que c'était nécessaire dans les URLs.
Cette "correction" a créé plus de problèmes qu'elle n'en a résolu.

---

## [11/11/2025] - Sprint 3 : Module Catégories

### ✅ Ajouté
- **CategoryController.php v1.5** : Upload d'icônes
  - Méthode `handleIconUpload()` : validation, upload, génération nom unique
  - Méthode `deleteIcon()` : suppression physique des fichiers
  - Modification `store()` et `update()` pour gérer l'upload
  
- **categories_index.php** : Liste des catégories
  - Statistiques (total, actives, inactives)
  - Filtres (recherche, statut)
  - Table avec icônes colorées
  - Actions (voir, modifier, supprimer)

- **categories_create.php** : Formulaire création avec upload
  - Onglets : Upload de fichier OU saisie d'URL
  - Aperçu JavaScript de l'icône
  - Validation HTML5 (types de fichiers acceptés)

- **categories_edit.php** : Formulaire édition avec upload
  - Affichage de l'icône actuelle
  - Remplacement par upload ou URL
  - Avertissement suppression automatique

- **categories_show.php** : Page détails d'une catégorie
  - Affichage complet des informations
  - Aperçu visuel (couleur + icône)
  - Actions (modifier, supprimer)
  - Formulaire suppression sécurisé

- **Sécurité uploads** :
  - `.htaccess` : blocage exécution PHP, restriction types de fichiers
  - `index.html` : blocage du listing du répertoire

### 📧 Modifié
- **Category.php (Model)** : v1.6 - Ajout `isUsedByProducts()`
- **CategoryController.php** : v1.6 - Chemins mixtes corrigés

### 🐛 Corrigé
- Fichier `categories/index.php` manquant (erreur 404)
- Fatal error méthode `isUsedByProducts()` manquante
- Token CSRF invalide dans formulaire suppression

### 📁 Structure ajoutée
```
/stm/public/uploads/categories/
  ├── .htaccess
  └── index.html
```

### 🔐 Sécurité
- Validation stricte : SVG, PNG, JPG, WEBP uniquement
- Taille max : 2MB
- Nom de fichier unique : `category_[uniqid]_[timestamp].[ext]`
- Blocage exécution PHP dans /uploads/
- Protection suppression si catégorie utilisée par des produits
- Token CSRF sur tous les formulaires de suppression

---

## [08/11/2025] - Sprint 2 : Module Campagnes (100%)

### ✅ Ajouté
- **CampaignController.php** : CRUD complet des campagnes
  - 10 méthodes : index, create, store, show, edit, update, destroy, active, archives, toggleActive
  - Validation CSRF sur toutes les actions POST
  - Gestion des erreurs et messages flash

- **Campaign.php (Model)** : Gestion BDD
  - 11 méthodes incluant getStats(), getActive(), getArchived()
  - Validation des données (dates, pays, champs requis)

- **4 vues campagnes** :
  - `index.php` : Liste avec filtres et statistiques
  - `create.php` : Formulaire création multilingue
  - `show.php` : Détails d'une campagne
  - `edit.php` : Formulaire modification

### 📧 Modifié
- **admin.php (layout)** : Ajout récupération stats pour sidebar
- **sidebar.php** : Badge dynamique pour campagnes actives
- **routes.php** : 8 routes campagnes ajoutées

### 🐛 Corrigé
- Chemin layout dans vues campagnes (2 niveaux au lieu de 1)
- Actions formulaires : POST vers `/admin/campaigns` au lieu de `/store`
- Suppression sécurisée : formulaire POST au lieu de onclick GET
- Badge sidebar : affichage nombre réel de campagnes actives

---

## [07/11/2025] - Sprint 1 : Authentification (100%)

### ✅ Ajouté
- **AuthController.php** : Login/Logout
- **AuthMiddleware.php** : Protection routes admin
- **Dashboard complet** : KPIs + graphiques Chart.js
- **Layout admin.php** : Sidebar + navigation
- Table `users` avec 1 admin par défaut

### 🔐 Sécurité
- Bcrypt pour les mots de passe
- Protection brute-force : 5 tentatives, 15 min lockout
- CSRF token sur tous les formulaires
- Session sécurisée avec régénération

---

## [06/11/2025] - Sprint 0 : Architecture (100%)

### ✅ Ajouté
- **Structure MVC complète**
- **Core classes** : Database, Router, View, Request, Response, Auth, Session, Validator
- **Base de données** : 12 tables créées
- **Configuration** : .env avec variables O2switch spécifiques
- **50+ helpers** : Fonctions utilitaires
- **Autoloader PSR-4**

---

## [11/11/2025 21:50] - Sprint 4 : Module Produits (100%)

### ✅ Ajouté
- **Product.php (Model)** : CRUD complet des produits
  - 11 méthodes incluant getAll(), getByCategory(), isUsedByCampaigns()
  - Validation complète (code unique, EAN 13 chiffres)
  - Liaison avec table categories

- **ProductController.php** : Gestion produits
  - 8 méthodes : index, create, store, show, edit, update, destroy
  - Upload d'images FR et NL (max 5MB, JPG/PNG/WEBP)
  - Suppression automatique anciennes images lors remplacement
  - Protection suppression si produit utilisé dans campagnes

- **4 vues produits** :
  - `index.php` : Liste avec filtres (recherche, catégorie, statut)
  - `create.php` : Formulaire création multilingue + upload 2 images
  - `edit.php` : Formulaire édition avec aperçu images actuelles
  - `show.php` : Détails complets + affichage 2 images

- **Sécurité uploads** :
  - `.htaccess` : blocage PHP, autorisation images uniquement
  - `index.html` : blocage listing répertoire

### 📁 Structure ajoutée
```
/stm/public/uploads/products/
  ├── .htaccess
  └── index.html
```

### 🔐 Sécurité
- Validation stricte : JPG, PNG, WEBP uniquement
- Taille max : 5MB par image
- Nom fichier unique : `product_[fr|nl]_[uniqid]_[timestamp].[ext]`
- Blocage exécution PHP dans /uploads/
- Protection suppression si produit dans campagnes
- Token CSRF partout

### 📊 Statistiques
- Total produits
- Produits actifs/inactifs
- Produits avec/sans catégorie
- Filtres par catégorie et statut

---

## 🎯 PROGRESSION GLOBALE

```
✅ Sprint 0 : Architecture & Setup (100%)
✅ Sprint 1 : Authentification (100%)
✅ Sprint 2 : CRUD Campagnes (100%)
✅ Sprint 3 : Module Catégories (100%)
✅ Sprint 4 : Module Produits (100%) ← TERMINÉ !
⬜ Sprint 5 : Module Clients (0%)
⬜ Sprint 6 : Module Commandes (0%)

PROGRESSION : ~60%
```

---

## 📋 FORMAT DES ENTRÉES

Chaque modification doit suivre ce format :

```markdown
## [DATE HH:MM] - Titre de la session

### ✅ Ajouté
- Liste des nouveaux fichiers/fonctionnalités

### 📧 Modifié
- Liste des fichiers modifiés

### 🐛 Corrigé
- Liste des bugs corrigés

### 🗑️ Supprimé (si applicable)
- Liste des fichiers/fonctionnalités supprimés
```

---

**Dernière mise à jour** : 11/11/2025 23:20  
**Version projet** : 2.0  
**Statut** : En développement actif