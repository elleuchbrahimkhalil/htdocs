<?php
/**
 * Configuration IA pour CodeChallenge
 */

// Configuration de base
define('AI_ENABLED', true);
define('AI_API_URL', 'http://localhost:5000');
define('AI_TIMEOUT', 60);
define('AI_AUTO_APPROVE_THRESHOLD', 90);
define('AI_AUTO_REJECT_THRESHOLD', 30);

// Messages IA personnalisés
define('AI_MESSAGES', [
    'analyzing' => '🤖 Analyse IA en cours...',
    'passed' => '✅ Solution validée par l\'IA',
    'failed' => '❌ Solution rejetée par l\'IA',
    'review' => '👁️ Révision humaine requise',
    'error' => '⚠️ Erreur lors de l\'analyse IA',
    'approved_auto' => '🎉 Solution approuvée automatiquement !',
    'rejected_auto' => '❌ Solution rejetée automatiquement'
]);

// Configuration des scores
define('AI_SCORE_CONFIG', [
    'excellent' => 90,  // Auto-approval
    'good' => 70,       // Human review
    'poor' => 30,       // Auto-reject threshold
    'failed' => 0       // Minimum score
]);

// Langages supportés par l'IA
define('AI_SUPPORTED_LANGUAGES', [
    'python',
    'javascript',
    'java',
    'cpp',
    'c',
    'php',
    'ruby',
    'go'
]);

// Configuration des logs
define('AI_LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('AI_LOG_FILE', 'ai_system.log');
define('AI_LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Fonctions utilitaires
function getAIConfig($key, $default = null) {
    $config_map = [
        'enabled' => AI_ENABLED,
        'api_url' => AI_API_URL,
        'timeout' => AI_TIMEOUT,
        'auto_approve_threshold' => AI_AUTO_APPROVE_THRESHOLD,
        'auto_reject_threshold' => AI_AUTO_REJECT_THRESHOLD
    ];
    
    return $config_map[$key] ?? $default;
}

function isLanguageSupported($language) {
    return in_array(strtolower($language), AI_SUPPORTED_LANGUAGES);
}

function getAIMessage($type, $score = null) {
    $message = AI_MESSAGES[$type] ?? 'Message IA non disponible';
    
    if ($score !== null) {
        $message .= " (Score: $score/100)";
    }
    
    return $message;
}

function logAI($level, $message, $context = []) {
    if (!shouldLog($level)) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? ' ' . json_encode($context) : '';
    $log_entry = "[$timestamp] [$level] $message$context_str\n";
    
    // Rotation des logs si nécessaire
    if (file_exists(AI_LOG_FILE) && filesize(AI_LOG_FILE) > AI_LOG_MAX_SIZE) {
        rename(AI_LOG_FILE, AI_LOG_FILE . '.old');
    }
    
    file_put_contents(AI_LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
}

function shouldLog($level) {
    $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
    $current_level = $levels[AI_LOG_LEVEL] ?? 1;
    $message_level = $levels[$level] ?? 1;
    
    return $message_level >= $current_level;
}

// Vérification de l'environnement
function checkAIEnvironment() {
    $checks = [
        'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'pdo_extension' => extension_loaded('pdo'),
        'json_extension' => extension_loaded('json'),
        'curl_extension' => extension_loaded('curl'),
        'ai_api_reachable' => false
    ];
    
    // Tester la connexion à l'API IA
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents(AI_API_URL . '/health', false, $context);
        $checks['ai_api_reachable'] = ($response !== false);
    } catch (Exception $e) {
        $checks['ai_api_reachable'] = false;
    }
    
    return $checks;
}

// Initialisation
if (AI_ENABLED) {
    logAI('INFO', 'Système IA initialisé', [
        'api_url' => AI_API_URL,
        'timeout' => AI_TIMEOUT,
        'supported_languages' => count(AI_SUPPORTED_LANGUAGES)
    ]);
}
?>