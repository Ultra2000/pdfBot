Tu es GitHub Copilot.
Génère le code étape par étape. Après chaque étape, arrête-toi et attends que je réponde “OK – Étape N” avant de continuer.

🎯 Objectif global

Construire un bot WhatsApp pour manipuler des PDF :

Compression (modes : whatsapp, impression, équilibré)

Conversion (PDF ↔ DOCX / XLSX, PDF → images)

OCR (PDF scanné → texte / DOCX)

Résumé automatique (court / moyen / détaillé)

Traduction (langue cible)

Sécurisation (mot de passe + watermark)

Envoi du résultat à l’utilisateur via WhatsApp (Twilio d’abord, Meta plus tard)

Architecture :

Laravel (backend principal) + FilamentPHP (admin) + Tailwind

Redis (queue) + S3/MinIO (stockage temporaire)

Microservice Python (FastAPI) pour le traitement PDF

WhatsApp via Twilio API (MVP), conception compatible Meta Cloud API

Jobs asynchrones, logs, nettoyage automatique des fichiers

Étape 1 — Projet Laravel + Installation Filament

Génère la création du projet Laravel (doc dans README.md) :

Commandes (à documenter) : composer create-project laravel/laravel pdf-bot, php artisan key:generate, php artisan storage:link.

Ajoute Laravel Sail en option (section README).

Ajoute TailwindCSS (postcss / vite) avec config minimale.

Installe FilamentPHP et crée un admin de base :

Paquets : composer require filament/filament spatie/laravel-permission

Publie les configs nécessaires, crée un super-admin Seeder.

Crée les Models/Migrations de base : User, Document, TaskJob (ou ProcessingJob) avec états (pending, running, done, failed), type d’action (compress/convert/ocr/summarize/translate/secure), liens S3, taille d’entrée/sortie, logs.

Prépare Redis (queue) + Horizon :

composer require laravel/horizon predis/predis

Config QUEUE_CONNECTION=redis, dashboard Horizon protégé par middleware can:admin.

Fichiers attendus :

Migrations pour documents, task_jobs.

Seeders pour créer un admin Filament.

config/horizon.php + route/dashboard.

README avec toutes les commandes.

👉 Arrête-toi ici et attends “OK – Étape 1”.

Étape 2 — Filament Admin (monitoring)

Crée Filament Resources pour User, Document, TaskJob :

Listes, filtres par état/type, vue détail, actions (rejouer job, supprimer).

Ajoute un Dashboard Filament :

Widgets : nombre de jobs aujourd’hui, temps moyen de traitement, taux d’échec, taille totale traitée.

Ajoute politiques & rôles (Spatie Permission) : admin, operator.

👉 Attends “OK – Étape 2”.

Étape 3 — Stockage S3/MinIO + URLs signées

Ajoute paquet AWS : composer require league/flysystem-aws-s3-v3 aws/aws-sdk-php.

Configure S3 (ou MinIO en local).

Méthodes utilitaires :

Upload sécurisé, URLs signées temporaires (10–60 min) pour l’envoi WhatsApp.

Ajoute rétention/TTL sur Document (ex: 24h) + scheduler (app/Console/Kernel.php) pour purge.

👉 Attends “OK – Étape 3”.

Étape 4 — Intégration WhatsApp (Twilio MVP)

Installe twilio/sdk.

Crée un Webhook Controller WhatsAppWebhookController :

Reçoit messages texte + médias (PDF).

Parse commandes : COMPRESS [mode], CONVERT [docx|xlsx|img], OCR, SUMMARIZE [short|medium|long], TRANSLATE [fr|en|...], SECURE [password|watermark].

Crée un service WhatsApp/TwilioService :

Méthodes sendText, sendMedia($url, $caption).

Interface MessagingProvider + impl TwilioProvider (pour pouvoir ajouter MetaProvider plus tard).

Routes : /api/whatsapp/webhook.

Variables .env documentées : TWILIO_SID, TWILIO_AUTH_TOKEN, TWILIO_WHATSAPP_NUMBER.

👉 Attends “OK – Étape 4”.

Étape 5 — Orchestration des Jobs Laravel

Crée Jobs : CompressPdfJob, ConvertPdfJob, OcrPdfJob, SummarizePdfJob, TranslatePdfJob, SecurePdfJob.

Crée un Parser de commande (App\Support\CommandParser) qui mappe une commande entrante à un Job + paramètres.

Chaque job :

Télécharge le fichier source (depuis l’URL média Twilio) vers S3.

Appelle le client du microservice Python (à faire à l’étape 6).

Stocke le résultat S3, met à jour TaskJob et renvoie la réponse via WhatsApp (texte ou média).

Gère erreurs et logs.

👉 Attends “OK – Étape 5”.

Étape 6 — Microservice Python (FastAPI)

Crée un dossier /services/pdf_service avec :

main.py (FastAPI)

requirements.txt (fastapi, uvicorn, pikepdf, pillow, pdf2image, pytesseract, camelot-py, tabula-py, pdfminer.six, requests, python-docx, reportlab, httpx, pydantic, numpy)

Dockerfile + README (lancement : uvicorn main:app --host 0.0.0.0 --port 8000)

Endpoints multipart/form-data qui reçoivent un fichier + params, renvoient un fichier :

POST /compress (modes : whatsapp/impression/équilibré) — pikepdf + (option) ghostscript si dispo

POST /convert (format=docx|xlsx|img) — pdf2image, python-docx, (option LibreOffice headless)

POST /ocr — pytesseract, sortie texte ou DOCX

POST /summarize — reçoit PDF, extrait texte (pdfminer) puis résume (placeholder appel LLM, renvoie .txt)

POST /translate — placeholder appel DeepL/Google (renvoie .txt ou PDF simple via reportlab)

POST /secure — mot de passe + watermark (pikepdf + reportlab)

Hygiene :

Dossiers temp, tailles max, timeouts, logs JSON, validation.

👉 Attends “OK – Étape 6”.

Étape 7 — Client HTTP Laravel → Python

Crée App\Services\PdfServiceClient (Laravel HTTP client) :

Méthodes compress($s3Url, $mode), convert(...), ocr(...), summarize(...), translate(...), secure(...).

Télécharge source depuis S3 en local temporaire, envoie en multipart à FastAPI, récupère le flux résultant, uploade sur S3, renvoie l’URL.

Intègre ce client dans chaque Job (Étape 5).

👉 Attends “OK – Étape 7”.

Étape 8 — Flux complet COMPRESS (MVP)

Dans le webhook, quand un PDF arrive + commande COMPRESS whatsapp :

Crée Document, TaskJob, dispatch CompressPdfJob.

CompressPdfJob appelle PdfServiceClient->compress(...).

À la fin, envoie via TwilioService->sendMedia($signedUrl, "Compression ok").

Écris tests (Feature) pour ce flux.

👉 Attends “OK – Étape 8”.

Étape 9 — Conversion / OCR / Résumé / Traduction / Sécurité

Pour chaque fonctionnalité, répète le schéma :

Commande → Job → Client Python → URL signée → Message WhatsApp

Ajoute tests de base (chemins heureux + erreurs).

👉 Attends “OK – Étape 9”.

Étape 10 — Meta WhatsApp Cloud API (option production)

Ajoute MetaProvider (impl MessagingProvider) : envoi texte + document.

Contrôleur Webhook dédié (vérif token, validation signature).

.env : WHATSAPP_TOKEN, WHATSAPP_PHONE_ID, WHATSAPP_VERIFY_TOKEN.

Feature flag pour choisir le provider actif (config/messaging.php).

👉 Attends “OK – Étape 10”.

Étape 11 — Observabilité & Sécurité

Logs structurés (Monolog JSON), corrélation task_job_id.

Rate limiting Webhook, validation MIME/tailles, antivirus (option ClamAV).

Scheduler : purge des fichiers S3 expirés + job failed retries.

Politique de confidentialité : masquage de PII dans logs.

👉 Attends “OK – Étape 11”.

Étape 12 — Docker & Compose (local/dev)

docker-compose.yml avec :

app (Laravel + PHP-FPM), nginx, redis, minio, pdf_service (FastAPI).

Dockerfiles (PHP et Python), volumes, réseaux.

Makefile / scripts : make up, make seed, make horizon.

👉 Attends “OK – Étape 12”.

Étape 13 — Paiements (option) via Stripe (Laravel Cashier)

Plans : Student (3€/mois), Pro (29€/mois).

Middleware d’accès aux features selon plan.

Filament : page factures / statut abonnement.

👉 Attends “OK – Étape 13”.

Étape 14 — Documentation finale

Génère README.md détaillé :

Prérequis, installation, .env exemple, commandes, webhooks Twilio/Meta, routes, tests.

Ajoute diagramme d’architecture (ASCII/mermaid) + checklist mise en prod.

👉 Attends “OK – Étape 14”.

Rappels importants pour toutes les étapes

Toujours valider les entrées (taille max, type MIME).

Supprimer les fichiers temporaires après usage.

Utiliser des URLs signées pour tout média envoyé aux utilisateurs.

Factoriser la logique commune des Jobs (téléchargement, appel Python, upload, notification).

Écrire des tests à chaque étape.

S’arrêter après chaque étape et attendre “OK – Étape N”.

💪 Copilot, commence maintenant par l’Étape 1.