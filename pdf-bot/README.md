# PDF Bot WhatsApp

Bot WhatsApp pour la manipulation de fichiers PDF avec Laravel et microservice Python.

## 🎯 Fonctionnalités

- **Compression PDF** (modes : whatsapp, impression, équilibré)
- **Conversion** (PDF ↔ DOCX/XLSX, PDF → images)
- **OCR** (PDF scanné → texte/DOCX)
- **Résumé automatique** (court/moyen/détaillé)
- **Traduction** (multilingue)
- **Sécurisation** (mot de passe + watermark)

## 🏗️ Architecture

- **Backend** : Laravel 11 + FilamentPHP + TailwindCSS
- **Queue** : Redis + Laravel Horizon
- **Stockage** : S3/MinIO (URLs signées temporaires)
- **Microservice** : Python FastAPI pour traitement PDF
- **WhatsApp** : Twilio API (MVP) → Meta Cloud API (production)

## 📋 Prérequis

- PHP 8.2+
- Composer
- Node.js 18+
- Redis
- MySQL/PostgreSQL ou SQLite (développement)

## 🚀 Installation

### 1. Cloner et installer les dépendances

```bash
# Cloner le projet
git clone <repository-url> pdf-bot
cd pdf-bot

# Installer les dépendances PHP
composer install

# Installer les dépendances Node.js
npm install
```

### 2. Configuration de base

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Configurer la base de données (.env)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Configurer Redis (.env)
REDIS_CLIENT=predis
QUEUE_CONNECTION=redis

# Configurer le stockage S3/MinIO (.env)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=pdf-bot

# Pour MinIO local (développement)
MINIO_ACCESS_KEY=minioadmin
MINIO_SECRET_KEY=minioadmin
MINIO_ENDPOINT=http://127.0.0.1:9000
MINIO_BUCKET=pdf-bot
MINIO_USE_PATH_STYLE_ENDPOINT=true
```

### 3. Base de données et permissions

```bash
# Créer la base de données SQLite
touch database/database.sqlite

# Exécuter les migrations
php artisan migrate

# Installer les permissions et créer l'admin
php artisan db:seed --class=AdminSeeder

# Créer le lien symbolique pour le stockage
php artisan storage:link
```

### 7. Démarrage des services

```bash
# Démarrer le serveur Laravel
php artisan serve

# Dans un autre terminal : démarrer les workers
php artisan queue:work

# Dans un troisième terminal : démarrer Horizon (optionnel)
php artisan horizon
```

## 🔧 Commandes disponibles

### Maintenance
```bash
# Nettoyer les documents expirés
php artisan documents:cleanup-expired --dry-run
php artisan documents:cleanup-expired --force

# Vérifier les étapes
powershell -ExecutionPolicy Bypass -File verify-step1.ps1
powershell -ExecutionPolicy Bypass -File verify-step2.ps1
powershell -ExecutionPolicy Bypass -File verify-step3.ps1
powershell -ExecutionPolicy Bypass -File verify-step4.ps1
```

### WhatsApp Commands (Via Twilio)
- **COMPRESS [mode]** - Compresse un PDF (whatsapp/impression/équilibré)
- **CONVERT [format]** - Convertit un PDF (docx/xlsx/img)
- **OCR** - Extrait le texte d'un PDF scanné
- **SUMMARIZE [size]** - Résume un PDF (short/medium/long)
- **TRANSLATE [lang]** - Traduit un PDF (fr/en/es/de...)
- **SECURE [option]** - Sécurise un PDF (password/watermark)
- **HELP** - Affiche l'aide
- **STATUS** - Affiche les tâches récentes

## 🏗️ Architecture technique

### Composants Step 4
- **WhatsAppWebhookController** - Reçoit les messages Twilio
- **TwilioService** - Envoie messages/médias WhatsApp
- **CommandParser** - Parse les commandes utilisateur
- **Job Classes** - Traitement asynchrone des PDFs
- **MessagingProvider** - Interface pour futurs providers (Meta)

### 4. Migrations et seeders

```bash
# Tester la commande de nettoyage (mode dry-run)
php artisan documents:cleanup-expired --dry-run

# Lancer le nettoyage forcé
php artisan documents:cleanup-expired --force

# Vérifier les tâches programmées
php artisan schedule:list
```

### 5. Services et queues

```bash
# Démarrer Redis (requis)
redis-server

# Démarrer les workers de queue
php artisan queue:work

# Démarrer Horizon (monitoring des queues)
php artisan horizon
```

### 6. WhatsApp/Twilio Configuration

Modifier le fichier `.env` :

```env
# Twilio WhatsApp (MVP)
TWILIO_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_WHATSAPP_NUMBER=whatsapp:+14155238886

# Redis (obligatoire pour les queues)
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Redis
QUEUE_CONNECTION=redis
```

**Configuration Twilio Console:**
1. Webhook URL: `https://your-domain.com/api/whatsapp/webhook`
2. HTTP Method: POST
3. Content Type: application/x-www-form-urlencoded

### 7. Démarrage des services

```bash
# Exécuter les migrations
php artisan migrate

# Créer l'utilisateur admin
php artisan db:seed --class=AdminSeeder
```

### 5. Compilation des assets

```bash
# Développement
npm run dev

# Production
npm run build
```

## 🔧 Configuration avancée

### Laravel Sail (optionnel)

Pour utiliser Docker avec Laravel Sail :

```bash
# Installer Sail
composer require laravel/sail --dev

# Publier la configuration Sail
php artisan sail:install

# Démarrer l'environnement
./vendor/bin/sail up -d

# Utiliser Sail pour les commandes
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

### Redis et Horizon

```bash
# Démarrer Redis (si installé localement)
redis-server

# Démarrer Horizon
php artisan horizon

# Dashboard Horizon accessible à : http://localhost:8000/horizon
```

## 👤 Accès Admin

Après avoir exécuté le seeder AdminSeeder :

- **URL** : http://localhost:8000/admin
- **Email** : admin@pdf-bot.local
- **Mot de passe** : password

## 🛠️ Commandes utiles

```bash
# Démarrer le serveur de développement
php artisan serve

# Nettoyer le cache
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Queue workers
php artisan queue:work
php artisan horizon

# Tests
php artisan test
```

## 📁 Structure du projet

```
app/
├── Models/
│   ├── User.php           # Utilisateur avec rôles/permissions
│   ├── Document.php       # Documents PDF
│   └── TaskJob.php        # Jobs de traitement
├── Providers/
│   └── Filament/
│       └── AdminPanelProvider.php
config/
├── horizon.php            # Configuration Horizon
└── permission.php         # Configuration Spatie Permission
database/
├── migrations/
│   ├── create_documents_table.php
│   ├── create_task_jobs_table.php
│   └── create_permission_tables.php
└── seeders/
    └── AdminSeeder.php    # Création admin + rôles
```

## 📊 Monitoring

- **Horizon Dashboard** : `/horizon` (authentification requise)
- **Filament Admin** : `/admin`
- **Logs** : `storage/logs/laravel.log`

## 🔐 Permissions

### Rôles disponibles :
- **admin** : Accès complet
- **operator** : Gestion documents + jobs

### Permissions :
- `manage users`
- `manage documents`
- `manage task jobs`
- `view admin dashboard`
- `access horizon`

## 🚧 Étapes suivantes

Cette installation correspond à l'**Étape 1** du plan de développement. Les étapes suivantes incluront :

- Étape 2 : Interface admin Filament
- Étape 3 : Stockage S3/MinIO
- Étape 4 : Intégration WhatsApp Twilio
- Étape 5 : Jobs de traitement
- Étape 6 : Microservice Python FastAPI
- ...

## 📝 Notes importantes

- Redis est obligatoire pour le fonctionnement des queues
- Les fichiers sont stockés temporairement avec TTL automatique
- Toutes les URLs média utilisent des signatures temporaires
- Les logs sont centralisés et structurés JSON

---

Développé avec ❤️ en Laravel + FilamentPHP

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
