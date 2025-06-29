<?php
/**
 * Système de calcul des scores pour les solutions
 * Intègre les bonus pour solutions non acceptées et première solution
 */

require_once 'ai_analyzer.php';
require_once 'db_connect.php';

class SolutionScorer {
    
    private $aiAnalyzer;
    private $conn;
    
    public function __construct() {
        $this->aiAnalyzer = new AIAnalyzer();
        $this->conn = connect();
    }
    
    /**
     * Calculer et enregistrer le score d'une solution acceptée
     */
    public function calculateAndRecordScore($solutionId, $problemId, $userId) {
        try {
            // Récupérer les informations du problème
            $problemInfo = $this->getProblemInfo($problemId);
            if (!$problemInfo) {
                throw new Exception("Problème non trouvé");
            }
            
            // Calculer le score de base
            $baseScore = $problemInfo['points'];
            
            // Calculer les bonus
            $bonuses = $this->calculateBonuses($problemId, $userId);
            
            // Score final
            $finalScore = $baseScore + $bonuses['first_accepted'] + $bonuses['persistence'];
            
            // Enregistrer dans la table solution_scores
            $this->recordScore($solutionId, $problemId, $userId, $baseScore, $bonuses, $finalScore);
            
            // Mettre à jour le score total de l'utilisateur
            $this->updateUserScore($userId, $finalScore);
            
            // Mettre à jour les statistiques du problème
            $this->updateProblemStats($problemId);
            
            return [
                'base_score' => $baseScore,
                'bonuses' => $bonuses,
                'final_score' => $finalScore,
                'breakdown' => $this->getScoreBreakdown($baseScore, $bonuses)
            ];
            
        } catch (Exception $e) {
            error_log("Erreur calcul score solution: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Récupérer les informations du problème
     */
    private function getProblemInfo($problemId) {
        $stmt = $this->conn->prepare("
            SELECT problem_id, points, difficulty, ai_analysis, title
            FROM problems 
            WHERE problem_id = ?
        ");
        $stmt->execute([$problemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calculer les bonus
     */
    private function calculateBonuses($problemId, $userId) {
        $bonuses = [
            'first_accepted' => 0,
            'persistence' => 0,
            'difficulty_multiplier' => 0
        ];
        
        // Bonus pour première solution acceptée
        if ($this->isFirstAcceptedSolution($problemId)) {
            $bonuses['first_accepted'] = 3;
        }
        
        // Bonus de persévérance (solutions précédentes non acceptées)
        $unacceptedCount = $this->getUnacceptedSolutionsCount($problemId, $userId);
        if ($unacceptedCount > 0) {
            $bonuses['persistence'] = min($unacceptedCount * 5, 25); // Max 25 points
        }
        
        // Bonus multiplicateur selon difficulté (pour les cas exceptionnels)
        $problemInfo = $this->getProblemInfo($problemId);
        if ($problemInfo['difficulty'] === 'hard' && $unacceptedCount >= 3) {
            $bonuses['difficulty_multiplier'] = 5;
        }
        
        return $bonuses;
    }
    
    /**
     * Vérifier si c'est la première solution acceptée pour ce problème
     */
    private function isFirstAcceptedSolution($problemId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM solutions 
            WHERE problem_id = ? AND status IN ('approved', 'accepted')
        ");
        $stmt->execute([$problemId]);
        $result = $stmt->fetch();
        
        return $result['count'] == 0;
    }
    
    /**
     * Compter les solutions non acceptées de l'utilisateur pour ce problème
     */
    private function getUnacceptedSolutionsCount($problemId, $userId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM solutions 
            WHERE problem_id = ? AND user_id = ? AND status IN ('rejected', 'pending')
        ");
        $stmt->execute([$problemId, $userId]);
        $result = $stmt->fetch();
        
        return $result['count'];
    }
    
    /**
     * Enregistrer le score dans la base de données
     */
    private function recordScore($solutionId, $problemId, $userId, $baseScore, $bonuses, $finalScore) {
        $stmt = $this->conn->prepare("
            INSERT INTO solution_scores (
                solution_id, problem_id, user_id, base_score, 
                bonus_first_accepted, bonus_persistence, final_score
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $solutionId,
            $problemId, 
            $userId,
            $baseScore,
            $bonuses['first_accepted'],
            $bonuses['persistence'],
            $finalScore
        ]);
    }
    
    /**
     * Mettre à jour le score total de l'utilisateur
     */
    private function updateUserScore($userId, $scoreToAdd) {
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET score = score + ?, problems_solved = problems_solved + 1
            WHERE id = ?
        ");
        $stmt->execute([$scoreToAdd, $userId]);
    }
    
    /**
     * Mettre à jour les statistiques du problème
     */
    private function updateProblemStats($problemId) {
        $stmt = $this->conn->prepare("
            UPDATE problems 
            SET solution_count = (
                SELECT COUNT(*) 
                FROM solutions 
                WHERE problem_id = ? AND status IN ('approved', 'accepted')
            )
            WHERE problem_id = ?
        ");
        $stmt->execute([$problemId, $problemId]);
    }
    
    /**
     * Obtenir le détail du calcul de score
     */
    private function getScoreBreakdown($baseScore, $bonuses) {
        $breakdown = [
            'base' => [
                'value' => $baseScore,
                'description' => 'Score de base du problème (déterminé par l\'IA)'
            ]
        ];
        
        if ($bonuses['first_accepted'] > 0) {
            $breakdown['first_solution'] = [
                'value' => $bonuses['first_accepted'],
                'description' => 'Bonus première solution acceptée'
            ];
        }
        
        if ($bonuses['persistence'] > 0) {
            $breakdown['persistence'] = [
                'value' => $bonuses['persistence'],
                'description' => 'Bonus persévérance (tentatives précédentes)'
            ];
        }
        
        if ($bonuses['difficulty_multiplier'] > 0) {
            $breakdown['difficulty_bonus'] = [
                'value' => $bonuses['difficulty_multiplier'],
                'description' => 'Bonus difficulté élevée'
            ];
        }
        
        return $breakdown;
    }
    
    /**
     * Obtenir l'historique des scores d'un utilisateur
     */
    public function getUserScoreHistory($userId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT ss.*, p.title as problem_title, p.difficulty,
                   s.created_at as solution_date
            FROM solution_scores ss
            JOIN problems p ON ss.problem_id = p.problem_id
            JOIN solutions s ON ss.solution_id = s.id
            WHERE ss.user_id = ?
            ORDER BY ss.calculated_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtenir les statistiques globales des scores
     */
    public function getGlobalScoreStats() {
        $stats = [];
        
        // Score moyen par difficulté
        $stmt = $this->conn->prepare("
            SELECT p.difficulty, 
                   AVG(ss.final_score) as avg_score,
                   COUNT(*) as solution_count
            FROM solution_scores ss
            JOIN problems p ON ss.problem_id = p.problem_id
            GROUP BY p.difficulty
        ");
        $stmt->execute();
        $stats['by_difficulty'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top scorers
        $stmt = $this->conn->prepare("
            SELECT u.username, u.name, SUM(ss.final_score) as total_score,
                   COUNT(*) as solutions_count
            FROM solution_scores ss
            JOIN users u ON ss.user_id = u.id
            GROUP BY ss.user_id
            ORDER BY total_score DESC
            LIMIT 10
        ");
        $stmt->execute();
        $stats['top_scorers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Bonus distribution
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(CASE WHEN bonus_first_accepted > 0 THEN 1 END) as first_solution_bonuses,
                COUNT(CASE WHEN bonus_persistence > 0 THEN 1 END) as persistence_bonuses,
                AVG(bonus_persistence) as avg_persistence_bonus
            FROM solution_scores
        ");
        $stmt->execute();
        $stats['bonus_distribution'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * Recalculer tous les scores (pour migration ou correction)
     */
    public function recalculateAllScores() {
        try {
            $this->conn->beginTransaction();
            
            // Vider la table des scores
            $this->conn->exec("DELETE FROM solution_scores");
            
            // Récupérer toutes les solutions acceptées
            $stmt = $this->conn->prepare("
                SELECT s.id, s.problem_id, s.user_id, s.created_at
                FROM solutions s
                WHERE s.status IN ('approved', 'accepted')
                ORDER BY s.created_at ASC
            ");
            $stmt->execute();
            $solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $recalculated = 0;
            foreach ($solutions as $solution) {
                try {
                    $this->calculateAndRecordScore(
                        $solution['id'],
                        $solution['problem_id'],
                        $solution['user_id']
                    );
                    $recalculated++;
                } catch (Exception $e) {
                    error_log("Erreur recalcul solution {$solution['id']}: " . $e->getMessage());
                }
            }
            
            $this->conn->commit();
            return $recalculated;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
?>