<?php
/**
 * Validateur de solutions IA - Version SQL Server
 */

class AISolutionValidator {
    private $pdo;
    private $flask_api_url;
    private $timeout;
    
    public function __construct() {
        $this->pdo = $this->connectDatabase();
        $this->flask_api_url = $this->getConfig('flask_api_url', 'http://localhost:5000');
        $this->timeout = (int)$this->getConfig('analysis_timeout', 60);
    }
    
    private function connectDatabase() {
        try {
            // Configuration pour SQL Server
            $serverName = "localhost"; // ou votre serveur SQL Server
            $database = "votre_base_de_donnees";
            $username = "votre_utilisateur";
            $password = "votre_mot_de_passe";
            
            $dsn = "sqlsrv:Server=$serverName;Database=$database";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Erreur de connexion à SQL Server: " . $e->getMessage());
        }
    }
    
    private function getConfig($key, $default = null) {
        try {
            // Utiliser la fonction SQL Server
            $stmt = $this->pdo->prepare("SELECT dbo.GetAIConfig(?) as config_value");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            return $result['config_value'] ?? $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    public function analyzeSolution($solution_id) {
        try {
            $this->logAction($solution_id, 'analysis_start', 'pending', 'Début de l\'analyse IA');
            
            // Récupérer les données de la solution
            $solution_data = $this->getSolutionData($solution_id);
            if (!$solution_data) {
                throw new Exception("Solution non trouvée: $solution_id");
            }
            
            // Marquer comme en cours d'analyse
            $this->updateSolutionAIStatus($solution_id, 'ai_testing');
            
            // Préparer les données pour l'API Flask
            $api_data = [
                'solution_id' => $solution_id,
                'problem' => [
                    'title' => $solution_data['problem_title'],
                    'description' => $solution_data['problem_description'],
                    'difficulty' => $solution_data['problem_difficulty'],
                    'language' => $solution_data['problem_language'],
                    'expected_output' => $solution_data['expected_output'] ?? ''
                ],
                            'solution' => [
                    'code' => $solution_data['solution_code'],
                    'explanation' => $solution_data['explanation'] ?? '',
                    'developer' => $solution_data['username']
                ]
            ];
            
            // Appeler l'API Flask
            $start_time = microtime(true);
            $ai_results = $this->callFlaskAPI('/analyze-solution', $api_data);
            $execution_time = microtime(true) - $start_time;
            
            if (!$ai_results['success']) {
                throw new Exception("Erreur API IA: " . ($ai_results['error'] ?? 'Erreur inconnue'));
            }
            
            // Déterminer le statut final
            $ai_score = $ai_results['ai_score'] ?? 0;
            $threshold = (int)$this->getConfig('auto_validation_threshold', 80);
            
            if ($ai_score >= $threshold) {
                $final_status = 'ai_passed';
            } elseif ($ai_score < 30) {
                $final_status = 'ai_failed';
            } else {
                $final_status = 'ai_review';
            }
            
            // Sauvegarder les résultats
            $ai_data_json = json_encode($ai_results, JSON_UNESCAPED_UNICODE);
            $this->updateSolutionAIStatus($solution_id, $final_status, $ai_data_json, $ai_score);
            
            // Logger le succès
            $this->logAction($solution_id, 'analysis_complete', $final_status, 
                "Analyse terminée - Score: $ai_score/100", $execution_time);
            
            return [
                'success' => true,
                'solution_id' => $solution_id,
                'status' => $final_status,
                'ai_results' => $ai_results,
                'execution_time' => $execution_time
            ];
            
        } catch (Exception $e) {
            // Logger l'erreur
            $this->logAction($solution_id, 'analysis_error', 'ai_error', $e->getMessage());
            $this->updateSolutionAIStatus($solution_id, 'ai_error');
            
            return [
                'success' => false,
                'solution_id' => $solution_id,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getSolutionData($solution_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                s.id, s.solution_code, s.explanation, s.created_at,
                p.problem_id, p.title as problem_title, p.description as problem_description,
                p.difficulty as problem_difficulty, p.language as problem_language,
                p.code as problem_code, p.solution as expected_output,
                u.username, u.name as user_name
            FROM solutions s
            JOIN problems p ON s.problem_id = p.problem_id
            JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ");
        
        $stmt->execute([$solution_id]);
        return $stmt->fetch();
    }
    
    private function updateSolutionAIStatus($solution_id, $status, $ai_data = null, $ai_score = null) {
        // Utiliser la procédure stockée SQL Server
        $stmt = $this->pdo->prepare("EXEC UpdateSolutionAIStatus ?, ?, ?, ?");
        $stmt->execute([$solution_id, $status, $ai_data, $ai_score]);
    }
    
    private function callFlaskAPI($endpoint, $data) {
        $url = $this->flask_api_url . $endpoint;
        
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
                'timeout' => $this->timeout
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Impossible de contacter l'API Flask sur $url");
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Réponse API invalide: " . json_last_error_msg());
        }
        
        return $result;
    }
    
    private function logAction($solution_id, $action, $status, $message, $execution_time = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO ai_logs (solution_id, action, status, message, execution_time, created_at)
                VALUES (?, ?, ?, ?, ?, GETDATE())
            ");
            $stmt->execute([$solution_id, $action, $status, $message, $execution_time]);
        } catch (Exception $e) {
            // Ignorer les erreurs de log pour ne pas interrompre le processus principal
            error_log("Erreur lors du logging IA: " . $e->getMessage());
        }
    }
    
    public function getAIStats() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM ai_stats");
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des stats IA: " . $e->getMessage());
            return null;
        }
    }
    
    public function processAllPendingSolutions() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM pending_ai_solutions");
            $pending_solutions = $stmt->fetchAll();
            
            $results = [];
            foreach ($pending_solutions as $solution) {
                $result = $this->analyzeSolution($solution['solution_id']);
                $results[] = $result;
                
                // Pause courte entre les analyses
                usleep(500000); // 0.5 seconde
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Erreur lors du traitement en lot: " . $e->getMessage());
            return [];
        }
    }
    
    public function cleanupOldData($days = 30) {
        try {
            $stmt = $this->pdo->prepare("EXEC CleanOldAIAnalyses ?");
            $stmt->execute([$days]);
            
            $this->logAction(null, 'cleanup', 'success', "Nettoyage des données de plus de $days jours");
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors du nettoyage: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDetailedStats() {
        try {
            $stmt = $this->pdo->query("EXEC GetDetailedAIStats");
            
            $all_results = [];
            do {
                $results = $stmt->fetchAll();
                if (!empty($results)) {
                    $stat_type = $results[0]['stat_type'] ?? 'unknown';
                    $all_results[$stat_type] = $results;
                }
            } while ($stmt->nextRowset());
            
            return $all_results;
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des stats détaillées: " . $e->getMessage());
            return [];
        }
    }
    
    public function validateSolutionManually($solution_id, $human_feedback, $approve = true) {
        try {
            $new_status = $approve ? 'approved' : 'rejected';
            
            // Mettre à jour la solution
            $stmt = $this->pdo->prepare("
                UPDATE solutions 
                SET status = ?, 
                    feedback = CONCAT(ISNULL(feedback, ''), CHAR(13) + CHAR(10) + CHAR(13) + CHAR(10) + '👤 Validation manuelle: ' + ?),
                    evaluated_at = GETDATE()
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $human_feedback, $solution_id]);
            
            // Logger l'action
            $this->logAction($solution_id, 'manual_validation', $new_status, 
                "Validation manuelle: " . ($approve ? 'Approuvée' : 'Rejetée'));
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de la validation manuelle: " . $e->getMessage());
            return false;
        }
    }
    
    public function reanalyze($solution_id) {
        try {
            // Réinitialiser le statut IA
            $this->updateSolutionAIStatus($solution_id, 'pending');
            
            // Relancer l'analyse
            return $this->analyzeSolution($solution_id);
        } catch (Exception $e) {
            error_log("Erreur lors de la re-analyse: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getConfigValue($key) {
        return $this->getConfig($key);
    }
    
    public function updateConfig($key, $value) {
        try {
            $stmt = $this->pdo->prepare("
                MERGE ai_config AS target
                USING (SELECT ? as config_key, ? as config_value) AS source
                ON target.config_key = source.config_key
                WHEN MATCHED THEN
                    UPDATE SET config_value = source.config_value, updated_at = GETDATE()
                WHEN NOT MATCHED THEN
                    INSERT (config_key, config_value) VALUES (source.config_key, source.config_value);
            ");
            $stmt->execute([$key, $value]);
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de la mise à jour de config: " . $e->getMessage());
            return false;
        }
    }
}

// Fonction utilitaire pour l'intégration dans le système existant
function analyzeNewSolution($solution_id) {
    try {
        $validator = new AISolutionValidator();
        return $validator->analyzeSolution($solution_id);
    } catch (Exception $e) {
        error_log("Erreur analyse solution $solution_id: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Fonction pour vérifier si l'IA est activée
function isAIValidationEnabled() {
    try {
        $validator = new AISolutionValidator();
        $enabled = $validator->getConfigValue('enable_ai_validation');
        return strtolower($enabled) === 'true';
    } catch (Exception $e) {
        return false;
    }
}
?>
