# STM v2 - Système de Traitement Marketing

Application web de gestion de promotions B2B pour Trendy Foods.

## 📋 Description

STM v2 est une application PHP moderne permettant à Trendy Foods de gérer efficacement ses campagnes promotionnelles pour les marchés belge et luxembourgeois.

### Fonctionnalités principales

- 🎯 **Gestion de campagnes** : Création et suivi de promotions multi-pays
- 🛒 **Interface client** : Validation de commandes promotionnelles
- 📊 **Statistiques avancées** : Tableaux de bord et graphiques
- 📧 **Notifications automatiques** : Confirmations par email
- 🌐 **Multilingue** : Support FR/NL
- 📱 **Responsive** : Optimisé mobile & desktop

## 🛠️ Stack Technique

- **Backend** : PHP 8.3 + MySQL 8.0
- **Architecture** : MVC maison (pas de framework lourd)
- **Frontend** : Tailwind CSS + HTMX + Alpine.js + Chart.js
- **Hébergement** : O2switch (mutualisé)
- **Environnement local** : Laragon + VS Code

## 📦 Prérequis

- PHP >= 8.3
- MySQL >= 8.0
- Composer
- Node.js >= 18.x
- npm ou yarn

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone [url-repo]
cd stm-v2
```

### 2. Configuration

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Éditer .env avec vos paramètres
nano .env
```

### 3. Installer les dépendances

```bash
# Dépendances PHP
composer install

# Dépendances JavaScript
npm install
```

### 4. Base de données

```bash
# Créer la base de données
mysql -u root -p -e "CREATE DATABASE stm_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Exécuter les migrations
mysql -u root -p stm_v2 < database/migrations/001_create_tables.sql
mysql -u root -p stm_v2 < database/migrations/002_add_indexes.sql
mysql -u root -p stm_v2 < database/seeds/categories.sql
```

### 5. Compiler les assets

```bash
# Développement (avec watch)
npm run dev

# Production (minifié)
npm run build
```

### 6. Permissions

```bash
# Donner les droits d'écriture
chmod -R 775 storage/
chmod -R 775 public/uploads/
```

## 🖥️ Utilisation

### Développement local

1. **Avec Laragon** : 
   - Créer un nouveau projet dans Laragon
   - Pointer vers `/stm-v2/public`
   - Accéder à `http://stm-v2.test`

2. **Avec PHP built-in server** :
```bash
cd public
php -S localhost:8000
```

### Accès

- **Interface Client** : `http://localhost:8000`
- **Interface Admin** : `http://localhost:8000/admin`
- **Credentials par défaut** : 
  - Admin : `admin@trendyfoods.be` / `admin123`

## 📁 Structure du projet

```
stm-v2/
├── app/                    # Code application
│   ├── Controllers/        # Contrôleurs
│   ├── Models/            # Modèles
│   ├── Services/          # Logique métier
│   └── Views/             # Templates
├── core/                   # Classes système
├── config/                 # Configuration
├── public/                 # Fichiers publics
├── storage/                # Stockage
└── database/               # Migrations & seeds
```

## 🧪 Tests

```bash
# Tests unitaires
composer test

# Analyse statique
composer analyse
```

## 📝 Documentation

- [Installation complète](docs/INSTALLATION.md)
- [Guide utilisateur](docs/USER_GUIDE.md)
- [Documentation API](docs/API.md)
- [Cahier des charges](docs/CDC.md)

## 🔒 Sécurité

- ✅ Requêtes préparées PDO
- ✅ Validation des entrées
- ✅ Protection CSRF
- ✅ Sanitisation des données
- ✅ Headers de sécurité

## 🤝 Contribution

Ce projet est développé en interne par Trendy Foods.

## 📄 Licence

Propriétaire - Tous droits réservés Trendy Foods

## 👤 Auteur

**Fabian Hardy**  
Email : fabian@trendyfoods.be

## 📞 Support

Pour toute question ou problème :
- Email : support@trendyfoods.be
- Téléphone : +32 (0)4 XXX XX XX

---

**Version** : 2.0.0  
**Dernière mise à jour** : Novembre 2025
