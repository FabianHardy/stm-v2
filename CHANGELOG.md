# ðŸ“ CHANGELOG - STM v2

Historique centralisÃ© de toutes les modifications du projet.

---

## [12/11/2025 20:00] - Sprint 5 (ÉTAPE 4) : Vues customers ✅

### ✅ Ajouté
- **5 vues customers** complètes et fonctionnelles :
  - **customers/index.php** : Liste clients avec filtres (pays, représentant, recherche) + stats
  - **customers/create.php** : Formulaire création + lien vers import DB externe
  - **customers/show.php** : Détails client + campagnes attribuées + historique commandes
  - **customers/edit.php** : Formulaire modification
  - **customers/import_preview.php** : Import DB externe avec sélection multiple

### 🎨 Design & Fonctionnalités
**index.php** :
- Statistiques rapides : Total, BE, LU, Actifs
- Filtres HTMX : recherche, pays, représentant
- Tableau avec badges colorés (pays, statut)
- Actions : Voir, Modifier, Supprimer (formulaire POST sécurisé)
- 2 boutons en-tête : "Nouveau client" + "Importer depuis DB"

**create.php** :
- Formulaire complet (12 champs)
- Select représentant dynamique selon pays (Alpine.js)
- Card bleue avec lien vers import DB externe
- Validation HTML5 + affichage erreurs
- Token CSRF

**show.php** :
- Layout 2 colonnes (principale + sidebar)
- Section informations générales + coordonnées
- Section "Campagnes attribuées" (lecture seule)
- Section "Historique des commandes" (tableau)
- Sidebar : Catégorisation + Métadonnées système
- Boutons : Modifier, Supprimer

**edit.php** :
- Identique à create.php mais pré-rempli
- Action POST vers /stm/admin/customers/{id}
- Sans option import

**import_preview.php** :
- Filtres : Pays (BE/LU) + Recherche (Alpine.js)
- Tableau avec checkboxes de sélection
- Badge "Déjà importé" pour doublons (checkbox disabled)
- Compteur temps réel : "X clients sélectionnés"
- Boutons : "Tout sélectionner", "Tout désélectionner", "Importer"
- Action POST vers /stm/admin/customers/import/execute

### 🎯 Standards respectés
- ✅ Layout centralisé : `require __DIR__ . '/../../layouts/admin.php'`
- ✅ Structure : ob_start() → HTML → ob_get_clean() → $content → $title → layout
- ✅ Design cohérent avec campaigns (même style badges, tableaux, formulaires)
- ✅ Tailwind CSS + HTMX + Alpine.js
- ✅ Responsive mobile-first
- ✅ Token CSRF dans tous les formulaires POST
- ✅ Commentaires français + DocBlocks complets
- ✅ Messages flash gérés par layout

### 📊 Progression Sprint 5
- ✅ ÉTAPE 1 : Base de données + Connexion externe (100%)
- ✅ ÉTAPE 2 : Model Customer.php (100%)
- ✅ ÉTAPE 3 : CustomerController.php (100%)
- ✅ ÉTAPE 4 : Vues customers (100%)
- ⬜ ÉTAPE 5 : Routes et intégration (0%)
- ⬜ ÉTAPE 6 : Attribution campagnes finale (0%)

**Sprint 5 progression** : 67% (4/6 étapes terminées)

---


## [12/11/2025 19:15] - Sprint 5 (ÉTAPE 3) : CustomerController.php ✅

### ✅ Ajouté
- **CustomerController.php** : Contrôleur complet du module Clients
  - **CRUD standard** : 7 méthodes (index, create, store, show, edit, update, delete)
  - **Import DB externe** : 2 méthodes (importPreview, importExecute)
  - **Attribution campagnes** : 2 méthodes (assignCampaigns, updateCampaignAssignments)
  - **Helpers** : getRepresentatives(), validateCSRF()

### 🎯 Fonctionnalités implémentées
**CRUD complet** :
- Liste clients avec filtres (pays, représentant, recherche)
- Création/modification avec validation
- Suppression sécurisée (POST + CSRF + vérification commandes)
- Détails client avec campagnes et commandes

**Import base externe** :
- Prévisualisation clients disponibles (BE_CLL / LU_CLL)
- Import par sélection multiple
- Détection doublons (contrainte customer_number + country)
- Statistiques d'import (importés, ignorés, erreurs)

**Attribution campagnes** :
- Interface d'attribution par client
- Mise à jour des relations client-campagne
- Support Mode 1 (liste manuelle) prêt pour Mode 2 (tous dynamique)

### 🔒 Sécurité
- Validation CSRF sur toutes les actions POST
- Try/catch sur opérations DB
- Vérification existence avant modification/suppression
- Protection contre suppression si commandes existantes

### 📝 Code quality
- Commentaires en français
- DocBlocks complets (@created, @modified)
- Respect PSR-12
- Gestion erreurs avec messages flash
- Structure inspirée de CampaignController

### 📊 Progression Sprint 5
- ✅ ÉTAPE 1 : Base de données + Connexion externe (100%)
- ✅ ÉTAPE 2 : Model Customer.php (100%)
- ✅ ÉTAPE 3 : CustomerController.php (100%)
- ⬜ ÉTAPE 4 : Vues customers (0%)
- ⬜ ÉTAPE 5 : Routes et intégration (0%)
- ⬜ ÉTAPE 6 : Attribution campagnes finale (0%)

**Sprint 5 progression** : 50% (3/6 étapes terminées)

---


## [12/11/2025 18:50] - Sprint 4 : SystÃ¨me de quotas TERMINÃ‰ âœ…

### ðŸŽ‰ SUCCÃˆS
Le systÃ¨me de quotas est maintenant **100% fonctionnel** en production !

**Tests rÃ©ussis** :
- âœ… CrÃ©ation de promotion avec quotas
- âœ… Modification de promotion avec quotas
- âœ… Affichage des quotas avec badges colorÃ©s
- âœ… Validation correcte (nombres >= 1)
- âœ… Sauvegarde en base de donnÃ©es

### ðŸ“Š SystÃ¨me de quotas complet
**Interface** :
- Section "Quotas de commande" dans les formulaires
- 2 champs optionnels : max_total (global) et max_per_customer (par client)
- Exemples d'utilisation intÃ©grÃ©s
- Affichage badges colorÃ©s : ðŸŒ (violet), ðŸ‘¤ (bleu), âˆž (gris)

**Backend** :
- Colonnes max_total et max_per_customer dans table products
- Validation : nombres entiers positifs >= 1 ou NULL (illimitÃ©)
- Gestion dans Product.php (create/update/validate)
- Traitement dans ProductController.php (store/update)

### ðŸ”§ Session de dÃ©bogage
**MÃ©thode utilisÃ©e** :
1. VÃ©rification base de donnÃ©es â†’ Colonnes OK
2. Ajout affichage erreurs de validation â†’ OK
3. Mode debug visuel â†’ Identification du bug
4. Correction appliquÃ©e â†’ RÃ©solu

**DurÃ©e totale** : ~2h de debug et corrections
**RÃ©sultat** : SystÃ¨me entiÃ¨rement opÃ©rationnel

### ðŸ“ˆ Progression projet
- Sprint 4 (Module Promotions) : 100% âœ…
- Progression globale : 60% â†’ PrÃªt pour Sprint 5 (Clients)

---

## [12/11/2025 18:45] - Sprint 4 : FIX FINAL Modification quotas âœ…

### ðŸ› CorrigÃ©
- **ProductController.php** : Ajout de l'ID dans $data lors de la modification
  - Bug identifiÃ© : L'ID n'Ã©tait pas passÃ© Ã  la validation
  - ConsÃ©quence : La validation Ã©chouait avec "Ce code produit existe dÃ©jÃ "
  - Solution : Ajout de `'id' => $id` dans le tableau $data
  - Retrait du mode debug temporaire

- **Product.php** : Nettoyage du code
  - Retrait des logs de debug excessifs
  - Conservation des try/catch essentiels
  - Simplification de la gestion d'erreur

### âœ… RÃ©sultat
- âœ… **CrÃ©ation** : Fonctionne avec quotas
- âœ… **Modification** : Fonctionne maintenant avec quotas

### ðŸ” Diagnostic effectuÃ©
1. Mode debug visuel â†’ IdentifiÃ© que le formulaire fonctionne
2. Analyse du code â†’ TrouvÃ© que l'ID manquait dans $data
3. Validation Ã©chouait â†’ Code produit considÃ©rÃ© comme doublon
4. Correction appliquÃ©e â†’ L'ID est maintenant passÃ© Ã  la validation

### ðŸ“Š Bug technique
**Ligne problÃ©matique dans Product::validate()** :
```php
$existing = $this->findByCode($data['product_code']);
if ($existing && (!isset($data['id']) || $existing['id'] != $data['id'])) {
    // Erreur "code existe dÃ©jÃ " MÃŠME pour le produit lui-mÃªme
}
```

**Sans l'ID** : `!isset($data['id'])` = true â†’ Erreur systÃ©matique  
**Avec l'ID** : La condition vÃ©rifie si c'est un autre produit â†’ OK

---

## [12/11/2025 18:30] - Sprint 4 : Mode debug visuel (temporaire)

### ðŸ”§ AjoutÃ©
- **ProductController_DEBUG.php** : Version debug temporaire
  - Affichage Ã  l'Ã©cran des valeurs POST et DATA
  - Test de la fonction empty() sur les quotas
  - ArrÃªt du traitement pour diagnostic
  - **âš ï¸ Ã€ utiliser temporairement pour identifier le problÃ¨me**

### ðŸ“‹ Fichiers
- **MODE_DEBUG_INSTRUCTIONS.md** : Guide d'utilisation
  - Instructions d'upload et de test
  - InterprÃ©tation des 3 cas possibles
  - Rappel de retirer le mode debug aprÃ¨s diagnostic

### ðŸŽ¯ Objectif
Identifier pourquoi les quotas ne se sauvent pas lors de la modification.
Le mode debug affiche les valeurs directement Ã  l'Ã©cran sans nÃ©cessiter d'accÃ¨s aux logs PHP.

---

## [12/11/2025 18:15] - Sprint 4 : Diagnostic modification quotas

### ðŸ”§ ModifiÃ©
- **Product.php** : Ajout logging dÃ©taillÃ© dans update()
  - Log des paramÃ¨tres SQL avant exÃ©cution
  - TraÃ§age des valeurs max_total et max_per_customer
  - Permet d'identifier exactement oÃ¹ Ã§a bloque

- **ProductController.php** : Ajout logging dÃ©taillÃ© dans update()
  - Log des valeurs POST reÃ§ues du formulaire
  - Log des valeurs DATA aprÃ¨s traitement
  - Comparaison POST vs DATA pour dÃ©bugger

### âœ… AjoutÃ©
- **DIAGNOSTIC_MODIFICATION.md** : Guide complet de diagnostic
  - Instructions de test Ã©tape par Ã©tape
  - Guide d'accÃ¨s aux logs PHP sur O2switch
  - Questions de diagnostic
  - Ce qu'il faut chercher dans les logs

### ðŸ“Š Ã‰tat actuel
- âœ… **CrÃ©ation** : Fonctionne avec quotas
- âŒ **Modification** : Ne fonctionne pas avec quotas
- ðŸ” **Diagnostic** : Logging activÃ© pour identifier le problÃ¨me

---

## [12/11/2025 18:00] - Sprint 4 : FIX Validation quotas + Affichage erreurs

### ðŸ› CorrigÃ©
- **create.php** : Ajout affichage erreurs validation quotas
  - Messages d'erreur rouges sous les champs max_total et max_per_customer
  - Bordure rouge sur les champs en erreur

- **edit.php** : Ajout affichage erreurs validation quotas
  - MÃªme systÃ¨me que create.php
  - PrÃ©-remplissage des valeurs existantes maintenu

- **Product.php** : Simplification validation quotas
  - Logique de validation plus claire et robuste
  - Conversion explicite en int avant validation
  - VÃ©rification : nombre entier positif >= 1
  - Ajout logging dÃ©taillÃ© pour debug

### ðŸ“Š Diagnostic
- **SymptÃ´me** : Promotion ne se sauve pas avec quotas remplis
- **Cause** : Erreurs de validation non affichÃ©es dans les formulaires
- **Solution** : Ajout affichage erreurs + simplification validation

### âœ… AjoutÃ©
- **INSTRUCTIONS_DEBOGAGE.md** : Guide complet de test
  - ProcÃ©dure de test Ã©tape par Ã©tape
  - Tableau des valeurs Ã  tester
  - Instructions pour vÃ©rifier les logs
  - 5 fichiers Ã  uploader listÃ©s

---

## [12/11/2025 17:45] - Sprint 4 : FIX Bug sauvegarde Promotions

### ðŸ› CorrigÃ©
- **Product.php** : Ajout gestion d'erreur avec try/catch
  - Logging des erreurs SQL dans error_log
  - Affichage erreur dÃ©taillÃ©e en cas d'Ã©chec
  - MÃ©thode `create()` : try/catch avec error_log
  - MÃ©thode `update()` : try/catch avec error_log

- **ProductController.php** : AmÃ©lioration messages d'erreur
  - MÃ©thode `store()` : Capture exception et affichage erreur technique
  - MÃ©thode `update()` : Capture exception et affichage erreur technique
  - Messages plus explicites pour l'utilisateur

### âœ… AjoutÃ©
- **DIAGNOSTIC_TABLE_PRODUCTS.sql** : Script SQL de diagnostic
  - VÃ©rification structure table products
  - Ajout colonnes max_total et max_per_customer si manquantes
  - Tests de vÃ©rification

### ðŸ“Š ProblÃ¨me identifiÃ©
- Redirections silencieuses sans message d'erreur visible
- Erreurs SQL non capturÃ©es ni loggÃ©es
- Impossible de dÃ©buguer sans accÃ¨s aux logs

### ðŸ”§ Solution appliquÃ©e
- Try/catch dans le Model pour capturer erreurs SQL
- Error_log pour tracer les problÃ¨mes
- Messages d'erreur explicites Ã  l'utilisateur
- Script de diagnostic pour vÃ©rifier colonnes DB

---

## [12/11/2025 16:50] - Sprint 4 : ImplÃ©mentation interface quotas

### ðŸ”§ ModifiÃ©
- **create.php** : Ajout section "ðŸ“Š Quotas de commande (Optionnel)"
  - Champs `max_total` (quota global) et `max_per_customer` (quota par client)
  - Inputs de type number avec placeholder "IllimitÃ©"
  - EncadrÃ© bleu avec exemples d'utilisation
  - PositionnÃ© aprÃ¨s section ParamÃ¨tres, avant boutons action

- **edit.php** : Ajout section "ðŸ“Š Quotas de commande (Optionnel)"
  - MÃªmes champs que create.php
  - Values avec fallback : `$old ?? $product ?? ''`
  - PrÃ©-remplissage automatique des quotas existants

- **show.php** : Ajout affichage quotas dans section ParamÃ¨tres
  - Badges colorÃ©s : violet ðŸŒ (global), bleu ðŸ‘¤ (par client)
  - Affichage conditionnel (si quotas dÃ©finis vs illimitÃ©)
  - Formatage nombre avec `number_format()` pour max_total
  - Explications sous chaque badge

### âœ… FonctionnalitÃ©s
- Interface complÃ¨te pour dÃ©finir les quotas lors de la crÃ©ation
- Modification des quotas existants
- Visualisation claire des quotas avec badges colorÃ©s
- SystÃ¨me optionnel : champs non-required, placeholders "IllimitÃ©"

### ðŸ“Š SystÃ¨me de quotas
- **max_total** : Limite globale tous clients confondus
- **max_per_customer** : Limite individuelle par client
- NULL = IllimitÃ© (pas de contrainte)
- Validation cÃ´tÃ© serveur dÃ©jÃ  implÃ©mentÃ©e (nombres positifs uniquement)

---

## [12/11/2025] - Optimisation configuration projet Claude

### âœ… AjoutÃ©
- **INSTRUCTIONS_PROJET_OPTIMISEES.md** : Nouvelles instructions projet v2.0
  - Autorisation permanente d'accÃ¨s au GitHub
  - RÃ¨gle de vÃ©rification systÃ©matique des fichiers (aucune supposition)
  - Gestion incrÃ©mentale du CHANGELOG
  - Clarification environnement O2switch (full production)
  - Workflow de dÃ©veloppement optimisÃ©
  
- **FICHIERS_PROJET_CLAUDE.md** : Guide d'organisation du projet
  - Liste des 7 fichiers essentiels Ã  uploader
  - Fichiers Ã  ne pas uploader (code accessible via GitHub)
  - Instructions de mise Ã  jour
  - Checklist setup initial

### ðŸ”§ ModifiÃ©
- **CHANGELOG.md** : Ajout de cette entrÃ©e (mise Ã  jour incrÃ©mentale)

### ðŸ“‹ Configuration projet
- Environnement clarifiÃ© : full O2switch (pas de local)
- AccÃ¨s GitHub autorisÃ© de maniÃ¨re permanente
- Process de vÃ©rification des fichiers Ã©tabli
- Mise Ã  jour CHANGELOG systÃ©matique Ã  chaque session

---

## [11/11/2025] - Sprint 3 : Module CatÃ©gories

### âœ… AjoutÃ©
- **CategoryController.php v1.5** : Upload d'icÃ´nes
  - MÃ©thode `handleIconUpload()` : validation, upload, gÃ©nÃ©ration nom unique
  - MÃ©thode `deleteIcon()` : suppression physique des fichiers
  - Modification `store()` et `update()` pour gÃ©rer l'upload
  
- **categories_index.php** : Liste des catÃ©gories
  - Statistiques (total, actives, inactives)
  - Filtres (recherche, statut)
  - Table avec icÃ´nes colorÃ©es
  - Actions (voir, modifier, supprimer)

- **categories_create.php** : Formulaire crÃ©ation avec upload
  - Onglets : Upload de fichier OU saisie d'URL
  - AperÃ§u JavaScript de l'icÃ´ne
  - Validation HTML5 (types de fichiers acceptÃ©s)

- **categories_edit.php** : Formulaire Ã©dition avec upload
  - Affichage de l'icÃ´ne actuelle
  - Remplacement par upload ou URL
  - Avertissement suppression automatique

- **SÃ©curitÃ© uploads** :
  - `.htaccess` : blocage exÃ©cution PHP, restriction types de fichiers
  - `index.html` : blocage du listing du rÃ©pertoire

### ðŸ”§ ModifiÃ©
- Aucune modification de fichiers existants (nouveaux fichiers uniquement)

### ðŸ› CorrigÃ©
- Fichier `categories/index.php` manquant (erreur 404)

### ðŸ“ Structure ajoutÃ©e
```
/stm/public/uploads/categories/
  â”œâ”€â”€ .htaccess
  â””â”€â”€ index.html
```

### ðŸ”’ SÃ©curitÃ©
- Validation stricte : SVG, PNG, JPG, WEBP uniquement
- Taille max : 2MB
- Nom de fichier unique : `category_[uniqid]_[timestamp].[ext]`
- Blocage exÃ©cution PHP dans /uploads/

---

## [08/11/2025] - Sprint 2 : Module Campagnes (100%)

### âœ… AjoutÃ©
- **CampaignController.php** : CRUD complet des campagnes
  - 10 mÃ©thodes : index, create, store, show, edit, update, destroy, active, archives, toggleActive
  - Validation CSRF sur toutes les actions POST
  - Gestion des erreurs et messages flash

- **Campaign.php (Model)** : Gestion BDD
  - 11 mÃ©thodes incluant getStats(), getActive(), getArchived()
  - Validation des donnÃ©es (dates, pays, champs requis)

- **4 vues campagnes** :
  - `index.php` : Liste avec filtres et statistiques
  - `create.php` : Formulaire crÃ©ation multilingue
  - `show.php` : DÃ©tails d'une campagne
  - `edit.php` : Formulaire modification

### ðŸ”§ ModifiÃ©
- **admin.php (layout)** : Ajout rÃ©cupÃ©ration stats pour sidebar
- **sidebar.php** : Badge dynamique pour campagnes actives
- **routes.php** : 8 routes campagnes ajoutÃ©es

### ðŸ› CorrigÃ©
- Chemin layout dans vues campagnes (2 niveaux au lieu de 1)
- Actions formulaires : POST vers `/admin/campaigns` au lieu de `/store`
- Suppression sÃ©curisÃ©e : formulaire POST au lieu de onclick GET
- Badge sidebar : affichage nombre rÃ©el de campagnes actives

---

## [07/11/2025] - Sprint 1 : Authentification (100%)

### âœ… AjoutÃ©
- **AuthController.php** : Login/Logout
- **AuthMiddleware.php** : Protection routes admin
- **Dashboard complet** : KPIs + graphiques Chart.js
- **Layout admin.php** : Sidebar + navigation
- Table `users` avec 1 admin par dÃ©faut

### ðŸ”’ SÃ©curitÃ©
- Bcrypt pour les mots de passe
- Protection brute-force : 5 tentatives, 15 min lockout
- CSRF token sur tous les formulaires
- Session sÃ©curisÃ©e avec rÃ©gÃ©nÃ©ration

---

## [06/11/2025] - Sprint 0 : Architecture (100%)

### âœ… AjoutÃ©
- **Structure MVC complÃ¨te**
- **Core classes** : Database, Router, View, Request, Response, Auth, Session, Validator
- **Base de donnÃ©es** : 12 tables crÃ©Ã©es
- **Configuration** : .env avec variables O2switch spÃ©cifiques
- **50+ helpers** : Fonctions utilitaires
- **Autoloader PSR-4**

---

## ðŸŽ¯ PROGRESSION GLOBALE

```
âœ… Sprint 0 : Architecture & Setup (100%)
âœ… Sprint 1 : Authentification (100%)
âœ… Sprint 2 : CRUD Campagnes (100%)
âœ… Sprint 3 : Module CatÃ©gories (100%)
â¬œ Sprint 4 : Module Produits (0%)
â¬œ Sprint 5 : Module Clients (0%)
â¬œ Sprint 6 : Module Commandes (0%)

PROGRESSION : ~45%
```

---

## ðŸ“‹ FORMAT DES ENTRÃ‰ES

Chaque modification doit suivre ce format :

```markdown
## [DATE] - Titre de la session

### âœ… AjoutÃ©
- Liste des nouveaux fichiers/fonctionnalitÃ©s

### ðŸ”§ ModifiÃ©
- Liste des fichiers modifiÃ©s

### ðŸ› CorrigÃ©
- Liste des bugs corrigÃ©s

### ðŸ—‘ï¸ SupprimÃ© (si applicable)
- Liste des fichiers/fonctionnalitÃ©s supprimÃ©s
```

---

**DerniÃ¨re mise Ã  jour** : 12/11/2025 16:30  
**Version projet** : 2.0  
**Statut** : En dÃ©veloppement actif