<?php
/**
 * Test basique de l'intégration IA
 */

require_once 'ai_config.php';
require_once 'ai_solution_validator_sqlserver.php';

echo "🧪 Test basique de l'IA CodeChallenge\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Configuration
echo "1️⃣ Vérification de la configuration...\n";
$config_ok = true;

if (!AI_ENABLED) {
    echo "❌ IA désactivée dans la configuration\n";
    $config_ok = false;
} else {
    echo "✅ IA activée\n";
}

echo "   API URL: " . AI_API_URL . "\n";
echo "   Timeout: " . AI_TIMEOUT . "s\n";
echo "   Langages supportés: " . count(AI_SUPPORTED_LANGUAGES) . "\n\n";

// Test 2: Environnement
echo "2️⃣ Vérification de l'environnement...\n";
$env_checks = checkAIEnvironment();

foreach ($env_checks as $check => $status) {
    $icon = $status ? "✅" : "❌";
    echo "   $icon " . ucfirst(str_replace('_', ' ', $check)) . "\n";
}
echo "\n";

// Test 3: Base de données
echo "3️⃣ Test de connexion à la base de données...\n";
try {
    $validator = new AISolutionValidator();
    echo "✅ Connexion réussie\n";
    
    // Test des statistiques
    $stats = $validator->getAIStats();
    if ($stats) {
        echo "✅ Statistiques récupérées\n";
        echo "   Solutions analysées: " . ($stats['total_analyzed'] ?? 0) . "\n";
    } else {
        echo "⚠️ Aucune statistique disponible (normal si première utilisation)\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: API Flask (si disponible)
echo "4️⃣ Test de l'API Flask...\n";
if ($env_checks['ai_api_reachable']) {
    echo "✅ API Flask accessible\n";
    
    // Test simple
    try {
        $test_data = [
            'solution_id' => 'test_001',
            'problem' => [
                'title' => 'Hello World Test',
                'description' => 'Simple test',
                'difficulty' => 'easy',
                'language' => 'python'
            ],
            'solution' => [
                'code' => 'print("Hello World")',
                'explanation' => 'Simple test',
                'developer' => 'test_user'
            ]
        ];
        
        $options = [
            'http' => [
                'header' => 'Content-Type: application/json',
                'method' => 'POST',
                'content' => json_encode($test_data),
                'timeout' => 10
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents(AI_API_URL . '/test', false, $context);
        
        if ($response) {
            $result = json_decode($response, true);
            if ($result && isset($result['success'])) {
                echo "✅ Test API réussi\n";
                echo "   Score de test: " . ($result['ai_score'] ?? 'N/A') . "/100\n";
            } else {
                echo "⚠️ Réponse API inattendue\n";
            }
        } else {
            echo "❌ Pas de réponse de l'API\n";
        }
    } catch (Exception $e) {
        echo "❌ Erreur lors du test API: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ API Flask non accessible\n";
    echo "   Assurez-vous que le service IA est démarré\n";
}
echo "\n";

// Résumé
echo "📊 RÉSUMÉ\n";
echo str_repeat("=", 20) . "\n";

$all_checks = array_merge(['config' => $config_ok], $env_checks);
$passed = array_sum($all_checks);
$total = count($all_checks);

echo "Tests réussis: $passed/$total\n";

if ($passed == $total) {
    echo "🎉 Tous les tests sont passés ! L'IA est prête à fonctionner.\n";
} elseif ($passed >= $total - 1) {
    echo "⚠️ Presque prêt ! Vérifiez les points en échec ci-dessus.\n";
} else {
    echo "❌ Plusieurs problèmes détectés. Configuration requise.\n";
}

echo "\n";

// Instructions de dépannage
if ($passed < $total) {
    echo "🔧 DÉPANNAGE\n";
    echo str_repeat("=", 15) . "\n";
    
    if (!$env_checks['ai_api_reachable']) {
        echo "• Pour démarrer l'API Flask IA :\n";
        echo "  cd ai_service && python app.py\n\n";
    }
    
    if (!$env_checks['pdo_extension']) {
        echo "• Installer l'extension PDO PHP\n\n";
    }
    
    if (!$env_checks['curl_extension']) {
        echo "• Installer l'extension cURL PHP\n\n";
    }
    
    echo "• Vérifiez les logs dans : " . AI_LOG_FILE . "\n";
    echo "• Documentation : README_AI.md\n\n";
}

// Test de performance simple
echo "⚡ TEST DE PERFORMANCE\n";
echo str_repeat("=", 25) . "\n";

$start_time = microtime(true);

// Simuler quelques opérations
for ($i = 0; $i < 100; $i++) {
    getAIConfig('enabled');
    isLanguageSupported('python');
    getAIMessage('analyzing', rand(0, 100));
}

$end_time = microtime(true);
$execution_time = ($end_time - $start_time) * 1000; // en millisecondes

echo "100 opérations de configuration : " . number_format($execution_time, 2) . " ms\n";

if ($execution_time < 10) {
    echo "✅ Performance excellente\n";
} elseif ($execution_time < 50) {
    echo "✅ Performance correcte\n";
} else {
    echo "⚠️ Performance lente - vérifiez la configuration\n";
}

echo "\n🏁 Test terminé.\n";
?>
