<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDFBot - Transformez vos PDF en quelques secondes via WhatsApp</title>
    <meta name="description" content="Compressez, convertissez, traduisez et sécurisez vos PDF directement depuis WhatsApp. Interface simple, résultats professionnels.">
    <meta name="keywords" content="PDF, WhatsApp, bot, compression, conversion, traduction, OCR">
    <meta name="author" content="PDFBot">
    <meta property="og:title" content="PDFBot - Bot WhatsApp pour manipuler vos PDF">
    <meta property="og:description" content="Transformez vos PDF directement depuis WhatsApp">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <link rel="canonical" href="{{ url('/') }}">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background-color: #ffffff;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e5e7eb;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 24px;
            color: #25d366;
        }

        .nav-logo i {
            margin-right: 8px;
            font-size: 28px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-link {
            text-decoration: none;
            color: #4b5563;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #25d366;
        }

        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background: #4b5563;
            margin: 3px 0;
            transition: 0.3s;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 211, 102, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #25d366;
            border-color: #25d366;
        }

        .btn-secondary:hover {
            background: #25d366;
            color: white;
        }

        .btn-large {
            padding: 16px 32px;
            font-size: 16px;
        }

        .btn-full {
            width: 100%;
            justify-content: center;
        }

        /* Hero Section */
        .hero {
            padding: 120px 0 80px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            overflow: hidden;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(37, 211, 102, 0.1);
            color: #25d366;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 24px;
        }

        .hero-title {
            font-size: 56px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 24px;
            color: #1a1a1a;
        }

        .highlight {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 20px;
            color: #64748b;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            margin-bottom: 48px;
        }

        .hero-stats {
            display: flex;
            gap: 32px;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .stat-label {
            font-size: 14px;
            color: #64748b;
        }

        /* Phone Mockup */
        .hero-visual {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .phone-mockup {
            width: 300px;
            height: 600px;
            background: #000;
            border-radius: 40px;
            padding: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .phone-screen {
            width: 100%;
            height: 100%;
            background: #ffffff;
            border-radius: 30px;
            overflow: hidden;
        }

        .whatsapp-interface {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .whatsapp-header {
            background: #075e54;
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            background: #25d366;
            border-radius: 50%;
            margin-left: auto;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            background: #e5ddd5;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            max-width: 80%;
        }

        .message.received {
            align-self: flex-start;
        }

        .message.sent {
            align-self: flex-end;
        }

        .message-content {
            background: white;
            padding: 10px 15px;
            border-radius: 15px;
            font-size: 14px;
            line-height: 1.4;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .message.sent .message-content {
            background: #dcf8c6;
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: white;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #1a1a1a;
        }

        .section-header p {
            font-size: 18px;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 32px;
        }

        .feature-card {
            background: white;
            padding: 32px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-color: #25d366;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .feature-icon i {
            font-size: 24px;
            color: white;
        }

        .feature-card h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #1a1a1a;
        }

        .feature-card p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .feature-demo {
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid #25d366;
        }

        .demo-tag {
            font-size: 12px;
            font-weight: 500;
            color: #25d366;
        }

        /* Demo Section */
        .demo {
            padding: 100px 0;
            background: #f8fafc;
        }

        .demo-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .demo-qr {
            text-align: center;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .qr-code:hover {
            border-color: #25d366;
            transform: scale(1.05);
        }

        .qr-code i {
            font-size: 80px;
            color: #64748b;
        }

        .whatsapp-number {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #25d366;
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .whatsapp-number:hover {
            background: #128c7e;
        }

        .demo-steps {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .demo-step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: white;
            border-radius: 8px;
            border-left: 3px solid #25d366;
        }

        .step-num {
            width: 24px;
            height: 24px;
            background: #25d366;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 60px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 24px;
            color: #25d366;
            margin-bottom: 16px;
        }

        .footer-logo i {
            margin-right: 8px;
            font-size: 28px;
        }

        .footer-section p {
            color: #9ca3af;
            margin-bottom: 20px;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: #374151;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: #25d366;
        }

        .footer-section h4 {
            margin-bottom: 16px;
            color: white;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section li {
            margin-bottom: 8px;
        }

        .footer-section a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: #25d366;
        }

        .footer-bottom {
            border-top: 1px solid #374151;
            padding-top: 20px;
            text-align: center;
            color: #9ca3af;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .hamburger {
                display: flex;
            }
            
            .hero-container {
                grid-template-columns: 1fr;
                gap: 40px;
                text-align: center;
            }
            
            .hero-title {
                font-size: 36px;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .phone-mockup {
                width: 250px;
                height: 500px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .demo-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        /* Admin Link */
        .admin-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #25d366;
            color: white;
            padding: 12px 16px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .admin-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-file-pdf"></i>
                <span>PDFBot</span>
            </div>
            <div class="nav-menu">
                <a href="#features" class="nav-link">Fonctionnalités</a>
                <a href="#demo" class="nav-link">Démo</a>
                <a href="{{ route('filament.admin.pages.dashboard') }}" class="nav-link">Admin</a>
                <a href="{{ url('/horizon') }}" class="nav-link">Monitoring</a>
                <a href="#demo" class="btn btn-primary">Essayer Gratuitement</a>
            </div>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-rocket"></i>
                    Nouveau : Interface menu intuitive !
                </div>
                <h1 class="hero-title">
                    Transformez vos <span class="highlight">PDF</span> 
                    directement depuis <span class="highlight">WhatsApp</span>
                </h1>
                <p class="hero-description">
                    Compressez, convertissez, traduisez et sécurisez vos documents PDF en quelques secondes. 
                    Une interface simple, des résultats professionnels, directement dans votre messagerie préférée.
                </p>
                <div class="hero-buttons">
                    <a href="#demo" class="btn btn-primary btn-large" onclick="openWhatsApp()">
                        <i class="fab fa-whatsapp"></i>
                        Commencer sur WhatsApp
                    </a>
                    <a href="#demo" class="btn btn-secondary btn-large">
                        <i class="fas fa-play"></i>
                        Voir la démo
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number">{{ number_format($stats['documents_processed'] ?? 10000) }}+</span>
                        <span class="stat-label">Documents traités</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">{{ $stats['success_rate'] ?? 99.9 }}%</span>
                        <span class="stat-label">Taux de succès</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">< {{ $stats['avg_processing_time'] ?? 30 }}s</span>
                        <span class="stat-label">Temps de traitement</span>
                    </div>
                </div>
            </div>
            <div class="hero-visual">
                <div class="phone-mockup">
                    <div class="phone-screen">
                        <div class="whatsapp-interface">
                            <div class="whatsapp-header">
                                <i class="fas fa-robot"></i>
                                <span>PDFBot</span>
                                <div class="status-indicator"></div>
                            </div>
                            <div class="chat-messages">
                                <div class="message received">
                                    <div class="message-content">
                                        📄 Que voulez-vous faire avec ce PDF ?<br><br>
                                        1️⃣ Compresser le fichier<br>
                                        2️⃣ Convertir en autre format<br>
                                        3️⃣ Extraire le texte (OCR)<br>
                                        4️⃣ Créer un résumé<br>
                                        5️⃣ Traduire le contenu<br>
                                        6️⃣ Sécuriser avec mot de passe
                                    </div>
                                </div>
                                <div class="message sent">
                                    <div class="message-content">2</div>
                                </div>
                                <div class="message received">
                                    <div class="message-content">
                                        ✅ Conversion Word lancée !<br>
                                        ⏳ Traitement en cours...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2>Toutes vos opérations PDF en un seul endroit</h2>
                <p>Une suite complète d'outils professionnels, accessible directement depuis WhatsApp</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-compress-alt"></i>
                    </div>
                    <h3>Compression Intelligente</h3>
                    <p>Réduisez la taille de vos PDF jusqu'à 90% sans perdre en qualité. Parfait pour l'envoi par email ou le stockage.</p>
                    <div class="feature-demo">
                        <span class="demo-tag">5.2 MB → 890 KB</span>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3>Conversion Multi-Format</h3>
                    <p>Convertissez vos PDF en Word, PowerPoint ou images JPG. Gardez la mise en forme originale intacte.</p>
                    <div class="feature-demo">
                        <span class="demo-tag">PDF → DOCX, PPTX, JPG</span>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>OCR Avancé</h3>
                    <p>Extrayez le texte de vos documents scannés avec une précision exceptionnelle. Rendez vos PDF consultables.</p>
                    <div class="feature-demo">
                        <span class="demo-tag">Image → Texte éditable</span>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Résumés Intelligents</h3>
                    <p>Générez des résumés courts, moyens ou détaillés de vos documents. Gagnez du temps de lecture.</p>
                    <div class="feature-demo">
                        <span class="demo-tag">50 pages → 2 paragraphes</span>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-language"></i>
                    </div>
                    <h3>Traduction Instantanée</h3>
                    <p>Traduisez vos documents en 5 langues : anglais, espagnol, italien, allemand, portugais.</p>
                    <div class="feature-demo">
                        <span class="demo-tag">FR → EN, ES, IT, DE, PT</span>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Sécurisation</h3>
                    <p>Protégez vos documents sensibles avec un mot de passe. Sécurité entreprise, simplicité WhatsApp.</p>
                    <div class="feature-demo">
                        <span class="demo-tag">PDF + mot de passe</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section id="demo" class="demo">
        <div class="container">
            <div class="section-header">
                <h2>Essayez maintenant !</h2>
                <p>Scannez le QR code et commencez à transformer vos PDF en quelques secondes</p>
            </div>
            
            <div class="demo-container">
                <div class="demo-qr">
                    <div class="qr-code" onclick="openWhatsApp()">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <p>Scannez avec WhatsApp</p>
                </div>
                <div class="demo-info">
                    <div class="whatsapp-number" onclick="copyWhatsAppNumber()">
                        <i class="fab fa-whatsapp"></i>
                        <span>+1 415 523 8886</span>
                    </div>
                    <p>Ou envoyez un message au numéro ci-dessus avec le code : <strong>join sell-she</strong></p>
                    <div class="demo-steps">
                        <div class="demo-step">
                            <span class="step-num">1</span>
                            Envoyez "join sell-she"
                        </div>
                        <div class="demo-step">
                            <span class="step-num">2</span>
                            Transférez votre PDF
                        </div>
                        <div class="demo-step">
                            <span class="step-num">3</span>
                            Choisissez votre action
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-file-pdf"></i>
                        <span>PDFBot</span>
                    </div>
                    <p>Transformez vos PDF professionnellement, directement depuis WhatsApp.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h4>Produit</h4>
                    <ul>
                        <li><a href="#features">Fonctionnalités</a></li>
                        <li><a href="#demo">Démo</a></li>
                        <li><a href="{{ route('filament.admin.pages.dashboard') }}">Admin</a></li>
                        <li><a href="#">Documentation</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Centre d'aide</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Statut du service</a></li>
                        <li><a href="#">Signaler un bug</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Légal</h4>
                    <ul>
                        <li><a href="#">Confidentialité</a></li>
                        <li><a href="#">Conditions d'utilisation</a></li>
                        <li><a href="#">Sécurité</a></li>
                        <li><a href="#">RGPD</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 PDFBot. Tous droits réservés.</p>
                <p>Développé avec ❤️ pour simplifier votre travail</p>
            </div>
        </div>
    </footer>

    <!-- Admin Quick Access -->
    <a href="{{ route('filament.admin.pages.dashboard') }}" class="admin-link">
        <i class="fas fa-cog"></i>
        Admin
    </a>

    <script>
        // WhatsApp functionality
        function openWhatsApp() {
            const phoneNumber = '+14155238886';
            const message = 'join sell-she';
            const whatsappURL = `https://wa.me/${phoneNumber.replace('+', '')}?text=${encodeURIComponent(message)}`;
            window.open(whatsappURL, '_blank');
        }

        // Copy WhatsApp number
        function copyWhatsAppNumber() {
            const phoneNumber = '+14155238886';
            navigator.clipboard.writeText(phoneNumber).then(function() {
                const element = document.querySelector('.whatsapp-number span');
                const originalText = element.textContent;
                element.textContent = 'Numéro copié !';
                element.parentElement.style.background = '#22c55e';
                
                setTimeout(() => {
                    element.textContent = originalText;
                    element.parentElement.style.background = '#25d366';
                }, 2000);
            });
        }

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Mobile menu toggle
        document.querySelector('.hamburger').addEventListener('click', function() {
            this.classList.toggle('active');
            document.querySelector('.nav-menu').classList.toggle('active');
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = 'none';
            }
        });
    </script>
</body>
</html>
