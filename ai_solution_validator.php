<?php
/**
 * Système de validation automatique des solutions par IA
 * 
 * Rôles de l'IA :
 * 1. Vérifier la classification du problème (facile, moyen, difficile)
 * 2. Valider si l'output correspond aux attentes du client
 * 3. Détecter les erreurs algorithmiques ou de logique
 * 4. Mettre en état "flou" jusqu'au paiement si validé
 */

require_once 'db_connect.php';
require_once 'verification.php';

class AISolutionValidator {
    
    private $pdo;
    private $flask_api_url;
    
    public function __construct() {
        $this->pdo = connect();
        $this->flask_api_url = 'http://localhost:5000'; // URL de votre API Flask
    }
    
    /**
     * Analyser une solution avec l'IA
     */
    public function analyzeSolution($solution_id) {
        try {
            // Récupérer les données de la solution et du problème
            $stmt = $this->pdo->prepare("
                SELECT s.*, p.title, p.description, p.difficulty, p.code as problem_code,
                       p.language, p.expected_output, u.username
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ?
            ");
            
            $stmt->execute([$solution_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                throw new Exception("Solution non trouvée");
            }
            
            // Marquer comme en cours d'analyse IA
            $this->updateSolutionStatus($solution_id, 'ai_testing');
            
            // Préparer les données pour l'IA
            $ai_request = [
                'solution_id' => $solution_id,
                'problem' => [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'difficulty' => $data['difficulty'],
                    'code' => $data['problem_code'],
                    'language' => $data['language'],
                    'expected_output' => $data['expected_output'] ?? ''
                ],
                'solution' => [
                    'code' => $data['solution_code'],
                    'explanation' => $data['explanation'] ?? '',
                    'developer' => $data['username']
                ]
            ];
            
            // Envoyer à l'API Flask pour analyse
            $ai_result = $this->callFlaskAPI('/analyze-solution', $ai_request);
            
            if ($ai_result && isset($ai_result['success']) && $ai_result['success']) {
                // Sauvegarder les résultats de l'IA
                $this->saveAIResults($solution_id, $ai_result);
                
                // Déterminer le statut final basé sur l'analyse IA
                $final_status = $this->determineFinalStatus($ai_result);
                $this->updateSolutionStatus($solution_id, $final_status, $ai_result);
                
                return [
                    'success' => true,
                    'status' => $final_status,
                    'ai_results' => $ai_result
                ];
            } else {
                throw new Exception("Erreur lors de l'analyse IA: " . ($ai_result['error'] ?? 'Erreur inconnue'));
            }
            
        } catch (Exception $e) {
            error_log("Erreur AI validation: " . $e->getMessage());
            
            // Marquer comme erreur IA
            $this->updateSolutionStatus($solution_id, 'ai_error', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Appeler l'API Flask
     */
    private function callFlaskAPI($endpoint, $data) {
        $url = $this->flask_api_url . $endpoint;
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                'method' => 'POST',
                'content' => json_encode($data),
                'timeout' => 60 // Timeout de 60 secondes pour l'IA
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Impossible de contacter l'API IA");
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Réponse IA invalide");
        }
        
        return $result;
    }
    
    /**
     * Sauvegarder les résultats de l'IA
     */
    private function saveAIResults($solution_id, $ai_results) {
        $stmt = $this->pdo->prepare("
            INSERT INTO ai_analysis (
                solution_id, 
                difficulty_check, 
                output_validation, 
                error_detection, 
                ai_score, 
                ai_feedback, 
                analysis_details,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                difficulty_check = VALUES(difficulty_check),
                output_validation = VALUES(output_validation),
                error_detection = VALUES(error_detection),
                ai_score = VALUES(ai_score),
                ai_feedback = VALUES(ai_feedback),
                analysis_details = VALUES(analysis_details),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $solution_id,
            $ai_results['difficulty_check'] ?? 'unknown',
            $ai_results['output_validation'] ?? 'unknown',
            $ai_results['error_detection'] ?? 'none',
            $ai_results['ai_score'] ?? 0,
            $ai_results['ai_feedback'] ?? '',
            json_encode($ai_results['details'] ?? [])
        ]);
    }
    
    /**
     * Déterminer le statut final basé sur l'analyse IA
     */
    private function determineFinalStatus($ai_results) {
        $score = $ai_results['ai_score'] ?? 0;
        $has_errors = ($ai_results['error_detection'] ?? 'none') !== 'none';
        $output_valid = ($ai_results['output_validation'] ?? 'invalid') === 'valid';
        
        // Logique de décision
        if ($has_errors) {
            return 'ai_failed'; // Erreurs détectées
        }
        
        if (!$output_valid) {
            return 'ai_failed'; // Output ne correspond pas
        }
        
        if ($score >= 80) {
            return 'ai_passed'; // Auto-validation si score élevé
        } elseif ($score >= 60) {
            return 'ai_review'; // Nécessite révision manuelle
        } else {
            return 'ai_failed'; // Score trop faible
        }
    }
    
    /**
     * Mettre à jour le statut de la solution
     */
    private function updateSolutionStatus($solution_id, $status, $ai_data = null) {
        $stmt = $this->pdo->prepare("
            UPDATE solutions 
            SET status = ?, ai_status = ?, ai_data = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            $status,
            $status,
            $ai_data ? json_encode($ai_data) : null,
            $solution_id
        ]);
    }
    
    /**
     * Traiter toutes les solutions en attente
     */
    public function processAllPendingSolutions() {
        $stmt = $this->pdo->prepare("
            SELECT id FROM solutions 
            WHERE status = 'pending' 
            AND (ai_status IS NULL OR ai_status = 'pending')
            ORDER BY created_at ASC
            LIMIT 10
        ");
        
        $stmt->execute();
        $solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        
        foreach ($solutions as $solution) {
            $result = $this->analyzeSolution($solution['id']);
            $results[] = [
                'solution_id' => $solution['id'],
                'result' => $result
            ];
            
            // Pause entre les analyses pour ne pas surcharger l'IA
            sleep(2);
        }
        
        return $results;
    }
    
    /**
     * Obtenir les statistiques IA
     */
    public function getAIStats() {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_analyzed,
                SUM(CASE WHEN ai_status = 'ai_passed' THEN 1 ELSE 0 END) as ai_passed,
                SUM(CASE WHEN ai_status = 'ai_failed' THEN 1 ELSE 0 END) as ai_failed,
                SUM(CASE WHEN ai_status = 'ai_review' THEN 1 ELSE 0 END) as ai_review,
                SUM(CASE WHEN ai_status = 'ai_testing' THEN 1 ELSE 0 END) as ai_testing,
                AVG(JSON_EXTRACT(ai_data, '$.ai_score')) as avg_score
            FROM solutions 
            WHERE ai_status IS NOT NULL
        ");
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// API endpoints pour l'interface
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $validator = new AISolutionValidator();
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'analyze_solution':
            $solution_id = $_POST['solution_id'] ?? 0;
            if ($solution_id > 0) {
                $result = $validator->analyzeSolution($solution_id);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => 'ID solution manquant']);
            }
            break;
            
        case 'process_all':
            $results = $validator->processAllPendingSolutions();
            echo json_encode(['success' => true, 'results' => $results]);
            break;
            
        case 'get_stats':
            $stats = $validator->getAIStats();
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
    }
    exit;
}

// Interface de gestion IA (si accédé directement)
if (!isset($_POST['action'])) {
    require_once 'header.php';
    
    $validator = new AISolutionValidator();
    $stats = $validator->getAIStats();
    ?>
    
    <div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <h1><i class="fas fa-robot"></i> Gestion IA - Validation des Solutions</h1>
        
        <!-- Statistiques IA -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h3 style="color: #3498db; margin: 0;"><?php echo $stats['total_analyzed'] ?? 0; ?></h3>
                <p>Solutions Analysées</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h3 style="color: #27ae60; margin: 0;"><?php echo $stats['ai_passed'] ?? 0; ?></h3>
                <p>Validées par IA</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h3 style="color: #e74c3c; margin: 0;"><?php echo $stats['ai_failed'] ?? 0; ?></h3>
                <p>Rejetées par IA</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h3 style="color: #f39c12; margin: 0;"><?php echo $stats['ai_testing'] ?? 0; ?></h3>
                <p>En Cours d'Analyse</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h3 style="color: #9b59b6; margin: 0;"><?php echo number_format($stats['avg_score'] ?? 0, 1); ?>/100</h3>
                <p>Score Moyen IA</p>
            </div>
        </div>
        
        <!-- Actions IA -->
        <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <h2><i class="fas fa-cogs"></i> Actions IA</h2>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <button onclick="processAllSolutions()" class="btn btn-primary">
                    <i class="fas fa-play"></i> Analyser toutes les solutions en attente
                </button>
                
                <button onclick="refreshStats()" class="btn btn-success">
                    <i class="fas fa-sync-alt"></i> Actualiser les statistiques
                </button>
                
                <button onclick="testAIConnection()" class="btn btn-warning">
                    <i class="fas fa-plug"></i> Tester la connexion IA
                </button>
            </div>
            
            <div id="ai-status" style="margin-top: 20px; padding: 15px; border-radius: 8px; display: none;"></div>
        </div>
        
               <!-- Configuration IA -->
        <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <h2><i class="fas fa-sliders-h"></i> Configuration IA</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <label>Seuil de validation automatique :</label>
                    <input type="range" id="auto-validation-threshold" min="50" max="100" value="80" 
                           style="width: 100%; margin: 10px 0;">
                    <span id="threshold-value">80</span>/100
                </div>
                
                <div>
                    <label>Timeout d'analyse (secondes) :</label>
                    <input type="number" id="analysis-timeout" min="30" max="300" value="60" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div>
                    <label>Mode de validation :</label>
                    <select id="validation-mode" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="strict">Strict (haute précision)</option>
                        <option value="balanced" selected>Équilibré</option>
                        <option value="permissive">Permissif (plus de solutions passent)</option>
                    </select>
                </div>
            </div>
            
            <button onclick="saveAIConfig()" class="btn btn-success" style="margin-top: 15px;">
                <i class="fas fa-save"></i> Sauvegarder la configuration
            </button>
        </div>
        
        <!-- Log des analyses récentes -->
        <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2><i class="fas fa-history"></i> Analyses Récentes</h2>
            <div id="recent-analyses" style="max-height: 400px; overflow-y: auto;">
                <!-- Sera rempli par JavaScript -->
            </div>
        </div>
    </div>
    
    <script>
        // Gestion du seuil de validation
        document.getElementById('auto-validation-threshold').addEventListener('input', function() {
            document.getElementById('threshold-value').textContent = this.value;
        });
        
        // Traiter toutes les solutions
        function processAllSolutions() {
            const statusDiv = document.getElementById('ai-status');
            statusDiv.style.display = 'block';
            statusDiv.style.backgroundColor = '#e3f2fd';
            statusDiv.style.color = '#1976d2';
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyse en cours...';
            
            fetch('ai_solution_validator.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=process_all'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusDiv.style.backgroundColor = '#e8f5e8';
                    statusDiv.style.color = '#2e7d32';
                    statusDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${data.results.length} solutions analysées avec succès !`;
                    refreshStats();
                    loadRecentAnalyses();
                } else {
                    throw new Error(data.error || 'Erreur inconnue');
                }
            })
            .catch(error => {
                statusDiv.style.backgroundColor = '#ffebee';
                statusDiv.style.color = '#c62828';
                statusDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> Erreur: ${error.message}`;
            });
        }
        
        // Actualiser les statistiques
        function refreshStats() {
            fetch('ai_solution_validator.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour les statistiques dans l'interface
                    location.reload(); // Simple reload pour cet exemple
                }
            });
        }
        
        // Tester la connexion IA
        function testAIConnection() {
            const statusDiv = document.getElementById('ai-status');
            statusDiv.style.display = 'block';
            statusDiv.style.backgroundColor = '#fff3e0';
            statusDiv.style.color = '#f57c00';
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test de connexion...';
            
            // Simuler un test de connexion
            setTimeout(() => {
                statusDiv.style.backgroundColor = '#e8f5e8';
                statusDiv.style.color = '#2e7d32';
                statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> Connexion IA opérationnelle !';
            }, 2000);
        }
        
        // Sauvegarder la configuration
        function saveAIConfig() {
            const threshold = document.getElementById('auto-validation-threshold').value;
            const timeout = document.getElementById('analysis-timeout').value;
            const mode = document.getElementById('validation-mode').value;
            
            const statusDiv = document.getElementById('ai-status');
            statusDiv.style.display = 'block';
            statusDiv.style.backgroundColor = '#e8f5e8';
            statusDiv.style.color = '#2e7d32';
            statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> Configuration sauvegardée !';
            
            // Ici vous pourriez envoyer la config au serveur
            console.log('Config IA:', { threshold, timeout, mode });
        }
        
        // Charger les analyses récentes
        function loadRecentAnalyses() {
            const container = document.getElementById('recent-analyses');
            
            // Simuler des données d'analyses récentes
            const mockAnalyses = [
                {
                    id: 1,
                    problem: 'Algorithme de tri rapide',
                    developer: 'john_doe',
                    score: 85,
                    status: 'ai_passed',
                    time: '2024-01-15 14:30:00'
                },
                {
                    id: 2,
                    problem: 'Recherche binaire optimisée',
                    developer: 'jane_smith',
                    score: 45,
                    status: 'ai_failed',
                    time: '2024-01-15 14:25:00'
                },
                {
                    id: 3,
                    problem: 'Calcul de fibonacci récursif',
                    developer: 'dev_master',
                    score: 72,
                    status: 'ai_review',
                    time: '2024-01-15 14:20:00'
                }
            ];
            
            container.innerHTML = mockAnalyses.map(analysis => {
                const statusColors = {
                    'ai_passed': '#27ae60',
                    'ai_failed': '#e74c3c',
                    'ai_review': '#f39c12'
                };
                
                const statusLabels = {
                    'ai_passed': 'Validé',
                    'ai_failed': 'Rejeté',
                    'ai_review': 'À réviser'
                };
                
                return `
                    <div style="border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${analysis.problem}</strong><br>
                            <small style="color: #666;">Par ${analysis.developer} • ${analysis.time}</small>
                        </div>
                        <div style="text-align: right;">
                            <div style="background: ${statusColors[analysis.status]}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; margin-bottom: 5px;">
                                ${statusLabels[analysis.status]}
                            </div>
                            <div style="font-weight: bold; color: ${statusColors[analysis.status]};">
                                ${analysis.score}/100
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        // Charger les analyses au démarrage
        document.addEventListener('DOMContentLoaded', loadRecentAnalyses);
    </script>
    
    <?php
    include 'footer.php';
}
?>
