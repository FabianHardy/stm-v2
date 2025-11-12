# 📝 CHANGELOG - STM v2

Historique centralisé de toutes les modifications du projet.

---

## [12/11/2025 18:50] - Sprint 4 : Système de quotas TERMINÉ ✅

### 🎉 SUCCÈS
Le système de quotas est maintenant **100% fonctionnel** en production !

**Tests réussis** :
- ✅ Création de promotion avec quotas
- ✅ Modification de promotion avec quotas
- ✅ Affichage des quotas avec badges colorés
- ✅ Validation correcte (nombres >= 1)
- ✅ Sauvegarde en base de données

### 📊 Système de quotas complet
**Interface** :
- Section "Quotas de commande" dans les formulaires
- 2 champs optionnels : max_total (global) et max_per_customer (par client)
- Exemples d'utilisation intégrés
- Affichage badges colorés : 🌍 (violet), 👤 (bleu), ∞ (gris)

**Backend** :
- Colonnes max_total et max_per_customer dans table products
- Validation : nombres entiers positifs >= 1 ou NULL (illimité)
- Gestion dans Product.php (create/update/validate)
- Traitement dans ProductController.php (store/update)

### 🔧 Session de débogage
**Méthode utilisée** :
1. Vérification base de données → Colonnes OK
2. Ajout affichage erreurs de validation → OK
3. Mode debug visuel → Identification du bug
4. Correction appliquée → Résolu

**Durée totale** : ~2h de debug et corrections
**Résultat** : Système entièrement opérationnel

### 📈 Progression projet
- Sprint 4 (Module Promotions) : 100% ✅
- Progression globale : 60% → Prêt pour Sprint 5 (Clients)

---

## [12/11/2025 18:45] - Sprint 4 : FIX FINAL Modification quotas ✅

### 🐛 Corrigé
- **ProductController.php** : Ajout de l'ID dans $data lors de la modification
  - Bug identifié : L'ID n'était pas passé à la validation
  - Conséquence : La validation échouait avec "Ce code produit existe déjà"
  - Solution : Ajout de `'id' => $id` dans le tableau $data
  - Retrait du mode debug temporaire

- **Product.php** : Nettoyage du code
  - Retrait des logs de debug excessifs
  - Conservation des try/catch essentiels
  - Simplification de la gestion d'erreur

### ✅ Résultat
- ✅ **Création** : Fonctionne avec quotas
- ✅ **Modification** : Fonctionne maintenant avec quotas

### 🔍 Diagnostic effectué
1. Mode debug visuel → Identifié que le formulaire fonctionne
2. Analyse du code → Trouvé que l'ID manquait dans $data
3. Validation échouait → Code produit considéré comme doublon
4. Correction appliquée → L'ID est maintenant passé à la validation

### 📊 Bug technique
**Ligne problématique dans Product::validate()** :
```php
$existing = $this->findByCode($data['product_code']);
if ($existing && (!isset($data['id']) || $existing['id'] != $data['id'])) {
    // Erreur "code existe déjà" MÊME pour le produit lui-même
}
```

**Sans l'ID** : `!isset($data['id'])` = true → Erreur systématique  
**Avec l'ID** : La condition vérifie si c'est un autre produit → OK

---

## [12/11/2025 18:30] - Sprint 4 : Mode debug visuel (temporaire)

### 🔧 Ajouté
- **ProductController_DEBUG.php** : Version debug temporaire
  - Affichage à l'écran des valeurs POST et DATA
  - Test de la fonction empty() sur les quotas
  - Arrêt du traitement pour diagnostic
  - **⚠️ À utiliser temporairement pour identifier le problème**

### 📋 Fichiers
- **MODE_DEBUG_INSTRUCTIONS.md** : Guide d'utilisation
  - Instructions d'upload et de test
  - Interprétation des 3 cas possibles
  - Rappel de retirer le mode debug après diagnostic

### 🎯 Objectif
Identifier pourquoi les quotas ne se sauvent pas lors de la modification.
Le mode debug affiche les valeurs directement à l'écran sans nécessiter d'accès aux logs PHP.

---

## [12/11/2025 18:15] - Sprint 4 : Diagnostic modification quotas

### 🔧 Modifié
- **Product.php** : Ajout logging détaillé dans update()
  - Log des paramètres SQL avant exécution
  - Traçage des valeurs max_total et max_per_customer
  - Permet d'identifier exactement où ça bloque

- **ProductController.php** : Ajout logging détaillé dans update()
  - Log des valeurs POST reçues du formulaire
  - Log des valeurs DATA après traitement
  - Comparaison POST vs DATA pour débugger

### ✅ Ajouté
- **DIAGNOSTIC_MODIFICATION.md** : Guide complet de diagnostic
  - Instructions de test étape par étape
  - Guide d'accès aux logs PHP sur O2switch
  - Questions de diagnostic
  - Ce qu'il faut chercher dans les logs

### 📊 État actuel
- ✅ **Création** : Fonctionne avec quotas
- ❌ **Modification** : Ne fonctionne pas avec quotas
- 🔍 **Diagnostic** : Logging activé pour identifier le problème

---

## [12/11/2025 18:00] - Sprint 4 : FIX Validation quotas + Affichage erreurs

### 🐛 Corrigé
- **create.php** : Ajout affichage erreurs validation quotas
  - Messages d'erreur rouges sous les champs max_total et max_per_customer
  - Bordure rouge sur les champs en erreur

- **edit.php** : Ajout affichage erreurs validation quotas
  - Même système que create.php
  - Pré-remplissage des valeurs existantes maintenu

- **Product.php** : Simplification validation quotas
  - Logique de validation plus claire et robuste
  - Conversion explicite en int avant validation
  - Vérification : nombre entier positif >= 1
  - Ajout logging détaillé pour debug

### 📊 Diagnostic
- **Symptôme** : Promotion ne se sauve pas avec quotas remplis
- **Cause** : Erreurs de validation non affichées dans les formulaires
- **Solution** : Ajout affichage erreurs + simplification validation

### ✅ Ajouté
- **INSTRUCTIONS_DEBOGAGE.md** : Guide complet de test
  - Procédure de test étape par étape
  - Tableau des valeurs à tester
  - Instructions pour vérifier les logs
  - 5 fichiers à uploader listés

---

## [12/11/2025 17:45] - Sprint 4 : FIX Bug sauvegarde Promotions

### 🐛 Corrigé
- **Product.php** : Ajout gestion d'erreur avec try/catch
  - Logging des erreurs SQL dans error_log
  - Affichage erreur détaillée en cas d'échec
  - Méthode `create()` : try/catch avec error_log
  - Méthode `update()` : try/catch avec error_log

- **ProductController.php** : Amélioration messages d'erreur
  - Méthode `store()` : Capture exception et affichage erreur technique
  - Méthode `update()` : Capture exception et affichage erreur technique
  - Messages plus explicites pour l'utilisateur

### ✅ Ajouté
- **DIAGNOSTIC_TABLE_PRODUCTS.sql** : Script SQL de diagnostic
  - Vérification structure table products
  - Ajout colonnes max_total et max_per_customer si manquantes
  - Tests de vérification

### 📊 Problème identifié
- Redirections silencieuses sans message d'erreur visible
- Erreurs SQL non capturées ni loggées
- Impossible de débuguer sans accès aux logs

### 🔧 Solution appliquée
- Try/catch dans le Model pour capturer erreurs SQL
- Error_log pour tracer les problèmes
- Messages d'erreur explicites à l'utilisateur
- Script de diagnostic pour vérifier colonnes DB

---

## [12/11/2025 16:50] - Sprint 4 : Implémentation interface quotas

### 🔧 Modifié
- **create.php** : Ajout section "📊 Quotas de commande (Optionnel)"
  - Champs `max_total` (quota global) et `max_per_customer` (quota par client)
  - Inputs de type number avec placeholder "Illimité"
  - Encadré bleu avec exemples d'utilisation
  - Positionné après section Paramètres, avant boutons action

- **edit.php** : Ajout section "📊 Quotas de commande (Optionnel)"
  - Mêmes champs que create.php
  - Values avec fallback : `$old ?? $product ?? ''`
  - Pré-remplissage automatique des quotas existants

- **show.php** : Ajout affichage quotas dans section Paramètres
  - Badges colorés : violet 🌍 (global), bleu 👤 (par client)
  - Affichage conditionnel (si quotas définis vs illimité)
  - Formatage nombre avec `number_format()` pour max_total
  - Explications sous chaque badge

### ✅ Fonctionnalités
- Interface complète pour définir les quotas lors de la création
- Modification des quotas existants
- Visualisation claire des quotas avec badges colorés
- Système optionnel : champs non-required, placeholders "Illimité"

### 📊 Système de quotas
- **max_total** : Limite globale tous clients confondus
- **max_per_customer** : Limite individuelle par client
- NULL = Illimité (pas de contrainte)
- Validation côté serveur déjà implémentée (nombres positifs uniquement)

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