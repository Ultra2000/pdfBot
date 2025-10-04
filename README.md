# 🤖 WhatsApp PDF Bot

Bot WhatsApp intelligent pour traiter et manipuler des fichiers PDF via une interface conversationnelle intuitive.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-12.0-red.svg)
![Python](https://img.shields.io/badge/Python-FastAPI-green.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 🎯 Fonctionnalités

### 📄 Traitement PDF Avancé
- **Compression** : Réduction de taille (modes WhatsApp/impression/équilibré)
- **Conversion** : PDF ↔ Word/Excel, PDF → Images
- **OCR** : Extraction de texte depuis PDF scannés
- **Résumé** : Génération automatique de résumés (court/moyen/détaillé)
- **Traduction** : Traduction multilingue du contenu
- **Sécurisation** : Protection par mot de passe + watermarks

### 💬 Interface WhatsApp
- Menu interactif avec options numérotées (1-6)
- Support des fichiers PDF jusqu'à 16MB
- Réponses en temps réel avec statuts de progression
- Compatible Twilio et Meta WhatsApp Cloud API

### 🎛️ Administration
- Dashboard Filament PHP complet
- Monitoring des tâches et statistiques
- Gestion des utilisateurs et permissions
- Interface de surveillance Horizon (queues Redis)

## 🏗️ Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   WhatsApp API  │───▶│  Laravel Backend │───▶│ Python Service  │
│ (Twilio/Meta)   │    │   + Filament     │    │   (FastAPI)     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │  Redis + Horizon │
                    │     (Queues)     │
                    └──────────────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │   S3/MinIO       │
                    │  (Stockage)      │
                    └──────────────────┘
```

### Composants
- **Backend** : Laravel 12 + Filament PHP + TailwindCSS
- **Microservice** : Python FastAPI pour traitement PDF
- **Queues** : Redis + Laravel Horizon
- **Stockage** : S3/MinIO avec URLs signées temporaires
- **WhatsApp** : Support Twilio et Meta WhatsApp Cloud API

## 🚀 Installation

### Prérequis
- PHP 8.2+
- Composer
- Node.js 18+
- Python 3.8+
- Redis
- SQLite ou MySQL

### 1. Backend Laravel

```bash
# Cloner et installer les dépendances
git clone <repository-url>
cd pdf-bot
composer install
npm install

# Configuration
cp .env.example .env
php artisan key:generate
php artisan storage:link

# Base de données
php artisan migrate
php artisan db:seed --class=AdminSeeder

# Assets
npm run build
```

### 2. Microservice Python

```bash
# Aller dans le dossier du microservice
cd services/pdf_microservice

# Créer environnement virtuel
python -m venv venv

# Activer l'environnement (Windows)
venv\Scripts\activate

# Installer les dépendances
pip install -r requirements.txt
```

### 3. Services

```bash
# Terminal 1 - Laravel
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2 - Python Microservice
cd services/pdf_microservice
venv\Scripts\activate
python main.py

# Terminal 3 - Horizon (queues)
php artisan horizon

# Terminal 4 - ngrok (webhook public)
ngrok http 8000
```

## ⚙️ Configuration

### Variables d'environnement (.env)

```bash
# Application
APP_NAME="WhatsApp PDF Bot"
APP_ENV=local
APP_DEBUG=true
APP_URL=https://your-ngrok-url.ngrok.app

# Base de données
DB_CONNECTION=sqlite
# ou MySQL : DB_CONNECTION=mysql, DB_HOST, DB_PORT, etc.

# Redis (Queues)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
QUEUE_CONNECTION=redis

# Provider de messagerie (twilio ou meta)
MESSAGING_PROVIDER=meta

# Twilio WhatsApp (Legacy)
TWILIO_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_WHATSAPP_NUMBER=whatsapp:+14155238886

# Meta WhatsApp Cloud API (Recommandé)
WHATSAPP_TOKEN=your_permanent_access_token
WHATSAPP_PHONE_ID=your_phone_number_id
WHATSAPP_VERIFY_TOKEN=your_custom_verify_token
WHATSAPP_APP_SECRET=your_app_secret
WHATSAPP_API_VERSION=v18.0

# Microservice Python
PDF_MICROSERVICE_URL=http://localhost:8001
PDF_MICROSERVICE_TIMEOUT=60
PDF_MICROSERVICE_ENABLED=true

# Stockage S3/MinIO
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=pdf-bot-storage
AWS_USE_PATH_STYLE_ENDPOINT=false

# MinIO local (optionnel)
# AWS_ENDPOINT=http://localhost:9000
# AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Configuration WhatsApp

#### Option 1 : Meta WhatsApp Cloud API (Recommandé)

1. **Créer une app Meta** : https://developers.facebook.com/
2. **Ajouter WhatsApp Business** product
3. **Configurer webhook** :
   - URL : `https://your-ngrok-url.ngrok.app/api/meta/webhook`
   - Verify Token : votre `WHATSAPP_VERIFY_TOKEN`
   - Fields : `messages`
4. **Récupérer les tokens** : Access Token, Phone Number ID, App Secret

#### Option 2 : Twilio (Sandbox)

1. **Compte Twilio** : https://console.twilio.com/
2. **WhatsApp Sandbox** : Try it out > Send a WhatsApp message
3. **Configurer webhook** : `https://your-ngrok-url.ngrok.app/api/webhook/whatsapp`

## 📱 Utilisation

### Interface Utilisateur

1. **Rejoindre le bot** (si Twilio) :
   ```
   Envoyer "join <your-sandbox-code>" au numéro Twilio
   ```

2. **Envoyer un PDF** :
   - Joindre un fichier PDF via WhatsApp
   - Le bot affiche automatiquement le menu

3. **Menu interactif** :
   ```
   🤖 PDF reçu ! Choisissez votre action :
   
   1️⃣ Compresser le PDF
   2️⃣ Convertir (PDF → Word/Image)
   3️⃣ OCR (Extraire le texte)
   4️⃣ Résumer le contenu
   5️⃣ Traduire le texte
   6️⃣ Sécuriser avec mot de passe
   
   💬 Tapez le numéro de votre choix (1-6)
   ```

4. **Recevoir le résultat** :
   - Le bot traite le PDF via le microservice Python
   - Vous recevez le fichier traité + confirmation

### Dashboard Admin

- **URL** : http://localhost:8000/admin
- **Identifiants par défaut** :
  - Email : `admin@pdf-bot.local`
  - Mot de passe : `password`

### Monitoring Horizon

- **URL** : http://localhost:8000/horizon
- Surveillance des queues Redis en temps réel
- Statistiques de performance des jobs

## 🔧 API

### Endpoints Python Microservice

```
GET  /                     # Health check
POST /compress            # Compression PDF
POST /convert             # Conversion PDF
POST /ocr                 # OCR extraction
POST /summarize           # Résumé automatique  
POST /translate           # Traduction
POST /secure              # Sécurisation
```

### Documentation API
- **Swagger** : http://localhost:8001/docs
- **ReDoc** : http://localhost:8001/redoc

## 🧪 Tests

```bash
# Tests Laravel
php artisan test

# Tests du microservice Python
cd services/pdf_microservice
python -m pytest

# Test webhook (simulation)
php artisan tinker
>>> app(\App\Services\WhatsApp\WhatsAppService::class)->handleTextMessage('whatsapp:+1234567890', 'test')
```

## 📊 Monitoring

### Logs
```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs Python
tail -f services/pdf_microservice/app.log
```

### Métriques disponibles
- Nombre de documents traités
- Temps de traitement moyen
- Taux d'échec des jobs
- Utilisation des ressources

## 🔒 Sécurité

- ✅ Validation des webhooks (signature HMAC)
- ✅ Limitation de débit (rate limiting)
- ✅ Validation MIME et tailles de fichiers
- ✅ URLs signées temporaires (1h)
- ✅ Chiffrement des données sensibles
- ✅ Nettoyage automatique des fichiers temporaires

## 🐛 Résolution de problèmes

### Problèmes courants

**1. Webhook non accessible**
```bash
# Vérifier ngrok
curl -I https://your-ngrok-url.ngrok.app/api/meta/webhook

# Vérifier les logs
php artisan log:show
```

**2. Microservice Python non accessible**
```bash
# Tester le microservice
curl http://localhost:8001/

# Redémarrer si nécessaire
cd services/pdf_microservice
venv\Scripts\activate
python main.py
```

**3. Jobs bloqués**
```bash
# Relancer Horizon
php artisan horizon:terminate
php artisan horizon
```

**4. Limite Twilio atteinte**
```bash
# Passer à Meta ou attendre 24h
# Voir MESSAGING_PROVIDER=meta dans .env
```

### Logs utiles
```bash
# Logs complets
Get-Content storage/logs/laravel.log -Tail 50

# Erreurs uniquement  
Get-Content storage/logs/laravel.log | Select-String "ERROR"

# Filtrer par job
Get-Content storage/logs/laravel.log | Select-String "CompressPdfJob"
```

## 🚀 Déploiement

### Docker Compose (En développement)

```yaml
# docker-compose.yml à venir
# Services : app, nginx, redis, minio, pdf_service
```

### Production
- Utiliser un vrai serveur S3 (pas MinIO local)
- Configurer un domaine permanent (pas ngrok)
- Activer la mise en cache Redis
- Configurer les backups de base de données
- Monitorer avec New Relic/Sentry

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit (`git commit -am 'Ajouter nouvelle fonctionnalite'`)
4. Push (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🆘 Support

- **Documentation** : Voir ce README
- **Issues** : Créer un ticket GitHub
- **Discord** : [Lien du serveur] (si applicable)

---

**Fait avec ❤️ pour simplifier le traitement de PDF via WhatsApp**