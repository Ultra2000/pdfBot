# 🚀 Guide de Démarrage Rapide

## Installation Express (5 minutes)

### 1. Prérequis
```bash
# Vérifier PHP 8.2+
php --version

# Vérifier Composer
composer --version

# Vérifier Node.js 18+
node --version

# Vérifier Python 3.8+
python --version
```

### 2. Installation automatique
```powershell
# Depuis le dossier Bot/
.\install.ps1
```

### 3. Configuration WhatsApp
Éditer `pdf-bot/.env` :

```bash
# Choisir le provider
MESSAGING_PROVIDER=meta

# Meta WhatsApp Cloud API (Gratuit)
WHATSAPP_TOKEN=EAA...
WHATSAPP_PHONE_ID=123...
WHATSAPP_VERIFY_TOKEN=mon_token
WHATSAPP_APP_SECRET=abc123...

# URL publique (ngrok)
APP_URL=https://abc123.ngrok.app
```

### 4. Démarrage des services

**Terminal 1 - Laravel :**
```bash
cd pdf-bot
php artisan serve --host=0.0.0.0 --port=8000
```

**Terminal 2 - Python :**
```bash
cd pdf-bot/services/pdf_microservice
venv\Scripts\activate
python main.py
```

**Terminal 3 - Queues :**
```bash
cd pdf-bot
php artisan horizon
```

**Terminal 4 - Webhook public :**
```bash
ngrok http 8000
# Copier l'URL https dans APP_URL du .env
```

### 5. Configuration Meta WhatsApp

1. **App Meta** : https://developers.facebook.com/
2. **Webhook URL** : `https://abc123.ngrok.app/api/meta/webhook`
3. **Verify Token** : Celui dans `WHATSAPP_VERIFY_TOKEN`
4. **Fields** : `messages`

### 6. Test

1. **Envoyer PDF** au numéro WhatsApp configuré
2. **Choisir une option** (1-6) dans le menu
3. **Recevoir le résultat** traité

## ✅ Vérifications

```bash
# Laravel OK ?
curl http://localhost:8000

# Python OK ?
curl http://localhost:8001

# Webhook OK ?
curl https://abc123.ngrok.app/api/meta/webhook?hub.mode=subscribe&hub.verify_token=mon_token&hub.challenge=test

# Admin OK ?
# http://localhost:8000/admin (admin@pdf-bot.local / password)
```

## 🐛 Dépannage Express

**Problème webhook :**
```bash
# Vérifier logs
Get-Content pdf-bot/storage/logs/laravel.log -Tail 10

# Tester manuellement
php artisan tinker
>>> app(\App\Services\WhatsApp\WhatsAppService::class)->sendText('whatsapp:+1234567890', 'Test');
```

**Microservice Python down :**
```bash
cd pdf-bot/services/pdf_microservice
venv\Scripts\activate
python main.py
```

**Jobs bloqués :**
```bash
php artisan horizon:terminate
php artisan horizon
```

**Meta webhook fails :**
```bash
# Vérifier la signature dans MetaWebhookController
# Vérifier APP_URL = ngrok URL exacte
```

---

🎉 **C'est tout ! Votre bot est opérationnel en 5 minutes.**