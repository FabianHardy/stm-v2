# 📝 CHANGELOG - STM v2

Historique centralisé de toutes les modifications du projet.

---

## [12/11/2025] - Optimisation configuration projet Claude

### ✅ Ajouté
- **INSTRUCTIONS_PROJET_OPTIMISEES.md** : Nouvelles instructions projet v2.0
  - Autorisation permanente d'accès au GitHub
  - Règle de vérification systématique des fichiers (aucune supposition)
  - Gestion incrémentale du CHANGELOG
  - Clarification environnement O2switch (full production)
  - Workflow de développement optimisé
  
- **FICHIERS_PROJET_CLAUDE.md** : Guide d'organisation du projet
  - Liste des 7 fichiers essentiels à uploader
  - Fichiers à ne pas uploader (code accessible via GitHub)
  - Instructions de mise à jour
  - Checklist setup initial

### 🔧 Modifié
- **CHANGELOG.md** : Ajout de cette entrée (mise à jour incrémentale)

### 📋 Configuration projet
- Environnement clarifié : full O2switch (pas de local)
- Accès GitHub autorisé de manière permanente
- Process de vérification des fichiers établi
- Mise à jour CHANGELOG systématique à chaque session

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

- **Sécurité uploads** :
  - `.htaccess` : blocage exécution PHP, restriction types de fichiers
  - `index.html` : blocage du listing du répertoire

### 🔧 Modifié
- Aucune modification de fichiers existants (nouveaux fichiers uniquement)

### 🐛 Corrigé
- Fichier `categories/index.php` manquant (erreur 404)

### 📁 Structure ajoutée
```
/stm/public/uploads/categories/
  ├── .htaccess
  └── index.html
```

### 🔒 Sécurité
- Validation stricte : SVG, PNG, JPG, WEBP uniquement
- Taille max : 2MB
- Nom de fichier unique : `category_[uniqid]_[timestamp].[ext]`
- Blocage exécution PHP dans /uploads/

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

### 🔧 Modifié
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

### 🔒 Sécurité
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

## 🎯 PROGRESSION GLOBALE

```
✅ Sprint 0 : Architecture & Setup (100%)
✅ Sprint 1 : Authentification (100%)
✅ Sprint 2 : CRUD Campagnes (100%)
✅ Sprint 3 : Module Catégories (100%)
⬜ Sprint 4 : Module Produits (0%)
⬜ Sprint 5 : Module Clients (0%)
⬜ Sprint 6 : Module Commandes (0%)

PROGRESSION : ~45%
```

---

## 📋 FORMAT DES ENTRÉES

Chaque modification doit suivre ce format :

```markdown
## [DATE] - Titre de la session

### ✅ Ajouté
- Liste des nouveaux fichiers/fonctionnalités

### 🔧 Modifié
- Liste des fichiers modifiés

### 🐛 Corrigé
- Liste des bugs corrigés

### 🗑️ Supprimé (si applicable)
- Liste des fichiers/fonctionnalités supprimés
```

---

**Dernière mise à jour** : 12/11/2025 16:30  
**Version projet** : 2.0  
**Statut** : En développement actif
