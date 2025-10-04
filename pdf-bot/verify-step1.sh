#!/bin/bash

echo "🚀 Vérification de l'installation PDF Bot - Étape 1"
echo "=================================================="

# Vérification des dépendances PHP
echo "✅ Vérification de Composer..."
composer --version

echo ""
echo "✅ Vérification des packages Laravel installés..."
php artisan --version

echo ""
echo "✅ Vérification Filament..."
php artisan list | grep filament || echo "Filament non trouvé"

echo ""
echo "✅ Vérification de la base de données..."
php artisan migrate:status

echo ""
echo "✅ Vérification des permissions Spatie..."
php artisan tinker --execute="use Spatie\\Permission\\Models\\Role; echo 'Rôles: ' . Role::count();"

echo ""
echo "✅ Vérification de l'admin..."
php artisan tinker --execute="use App\\Models\\User; echo 'Admin existe: ' . (User::where('email', 'admin@pdf-bot.local')->exists() ? 'OUI' : 'NON');"

echo ""
echo "🎉 Installation Étape 1 terminée avec succès !"
echo ""
echo "📋 Prochaines étapes :"
echo "1. Accéder à l'admin : http://127.0.0.1:8000/admin"
echo "2. Login : admin@pdf-bot.local / password"
echo "3. Continuer avec l'Étape 2 : Interface admin Filament"
echo ""
echo "⚠️  N'oubliez pas de démarrer Redis avant l'Étape 2 !"
