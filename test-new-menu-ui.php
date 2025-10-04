<?php

/**
 * Simulateur du nouveau système de menu interactif
 * Test complet du workflow UX amélioré
 */

echo "=== SIMULATEUR NOUVEAU SYSTÈME MENU ===\n\n";

// Configuration
$baseUrl = 'http://localhost:8000/api/webhook/whatsapp';
$testUser = 'whatsapp:+33123456789';

function simulateWebhook($from, $body = null, $mediaUrl = null, $mediaType = null) {
    global $baseUrl;
    
    $data = [
        'From' => $from,
        'Body' => $body
    ];
    
    if ($mediaUrl) {
        $data['MediaUrl0'] = $mediaUrl;
        $data['MediaContentType0'] = $mediaType;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'response' => $response
    ];
}

echo "🔹 TEST 1: PREMIER UTILISATEUR (Message de bienvenue)\n";
echo "Simulation: Utilisateur envoie 'Bonjour'\n";
$result = simulateWebhook($testUser, 'Bonjour');
echo "Status: " . $result['status'] . "\n";
echo "Réponse attendue: Message de bienvenue avec présentation du bot\n\n";

echo "🔹 TEST 2: ENVOI PDF (Menu principal)\n";
echo "Simulation: Utilisateur envoie un PDF\n";
$result = simulateWebhook(
    $testUser, 
    null, 
    'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    'application/pdf'
);
echo "Status: " . $result['status'] . "\n";
echo "Réponse attendue: Menu avec 6 options numérotées\n\n";

echo "🔹 TEST 3: SÉLECTION OPTION SIMPLE (Compression)\n";
echo "Simulation: Utilisateur tape '1'\n";
$result = simulateWebhook($testUser, '1');
echo "Status: " . $result['status'] . "\n";
echo "Réponse attendue: Compression lancée directement\n\n";

echo "🔹 TEST 4: SÉLECTION OPTION AVEC SOUS-MENU (Conversion)\n";
echo "Simulation: Utilisateur tape '2'\n";
$result = simulateWebhook($testUser, '2');
echo "Status: " . $result['status'] . "\n";
echo "Réponse attendue: Sous-menu conversion (21-23)\n\n";

echo "🔹 TEST 5: SÉLECTION SOUS-MENU (Word)\n";
echo "Simulation: Utilisateur tape '21'\n";
$result = simulateWebhook($testUser, '21');
echo "Status: " . $result['status'] . "\n";
echo "Réponse attendue: Conversion Word lancée\n\n";

echo "🔹 TEST 6: SÉLECTION OPTION RÉSUMÉ\n";
echo "Simulation: Utilisateur tape '4'\n";
$result = simulateWebhook($testUser, '4');
echo "Status: " . $result['status'] . "\n";
echo "Réponse attendue: Sous-menu résumé (41-43)\n\n";

echo "🔹 TEST 7: SÉLECTION RÉSUMÉ DÉTAILLÉ\n";
echo "Simulation: Utilisateur tape '43'\n";
$result = simulateWebhook($testUser, '43');
echo "Status: " . $result['status'] . "\n";
echo "Réponse attendue: Résumé détaillé lancé\n\n";

echo "🔹 TEST 8: SÉLECTION TRADUCTION\n";
echo "Simulation: Utilisateur tape '5'\n";
$result = simulateWebhook($testUser, '5');
echo "Status: " . $result['status'] . "\n";
echo "Réponse attendue: Sous-menu traduction (51-55)\n\n";

echo "🔹 TEST 9: TRADUCTION ANGLAIS\n";
echo "Simulation: Utilisateur tape '51'\n";
$result = simulateWebhook($testUser, '51');
echo "Status: " . $result['status'] . "\n";
echo "Réponse attendue: Traduction anglais lancée\n\n";

echo "✅ TESTS COMPLÉTÉS\n\n";

echo "🎯 NOUVEAU WORKFLOW UTILISATEUR:\n";
echo "1️⃣ Premier contact → Bienvenue personnalisée\n";
echo "2️⃣ Envoi PDF → Menu clair avec emojis\n";
echo "3️⃣ Choix numéro → Action ou sous-menu\n";
echo "4️⃣ Sous-choix → Opération spécifique\n";
echo "5️⃣ Confirmation → Traitement en cours\n\n";

echo "🚀 AMÉLIORATIONS UX IMPLÉMENTÉES:\n";
echo "✅ Interface intuitive avec numéros\n";
echo "✅ Messages de bienvenue personnalisés\n";
echo "✅ Emojis pour meilleure lisibilité\n";
echo "✅ Sous-menus pour options complexes\n";
echo "✅ Gestion de session avec cache\n";
echo "✅ Messages de confirmation clairs\n\n";

echo "📱 EXPERIENCE UTILISATEUR OPTIMISÉE!\n";
echo "Fini les commandes texte complexes, place aux menus simples!\n";

?>
