<?php
/**
 * Système d'analyse IA pour déterminer automatiquement :
 * - La difficulté du problème
 * - Le score de base
 * - Les types d'erreurs
 * - Les bonus pour solutions non acceptées
 */

class AIAnalyzer {
    
    // Configuration des scores par difficulté
    private const DIFFICULTY_SCORES = [
        'easy' => 10,
        'medium' => 20,
        'hard' => 35
    ];
    
    // Bonus pour solutions non acceptées précédemment
    private const UNACCEPTED_SOLUTION_BONUS = 5;
    
    // Bonus pour première solution acceptée
    private const FIRST_ACCEPTED_BONUS = 3;
    
    /**
     * Analyser un problème et déterminer sa difficulté et son score
     */
    public function analyzeProblem($code, $description, $language, $solution) {
        $analysis = [
            'difficulty' => 'medium',
            'base_score' => 20,
            'error_types' => [],
            'error_count' => 0,
            'complexity_factors' => [],
            'ai_confidence' => 0.0
        ];
        
        try {
            // 1. Analyse de la complexité du code
            $codeComplexity = $this->analyzeCodeComplexity($code, $language);
            
            // 2. Analyse de la description
            $descriptionComplexity = $this->analyzeDescriptionComplexity($description);
            
            // 3. Analyse des erreurs potentielles
            $errorAnalysis = $this->analyzeErrors($code, $language);
            
            // 4. Déterminer la difficulté finale
            $difficulty = $this->calculateDifficulty($codeComplexity, $descriptionComplexity, $errorAnalysis);
            
            // 5. Calculer le score de base
            $baseScore = $this->calculateBaseScore($difficulty, $errorAnalysis);
            
            $analysis = [
                'difficulty' => $difficulty,
                'base_score' => $baseScore,
                'error_types' => $errorAnalysis['types'],
                'error_count' => $errorAnalysis['count'],
                'complexity_factors' => array_merge($codeComplexity['factors'], $descriptionComplexity['factors']),
                'ai_confidence' => $this->calculateConfidence($codeComplexity, $descriptionComplexity, $errorAnalysis)
            ];
            
        } catch (Exception $e) {
            error_log("Erreur d'analyse IA: " . $e->getMessage());
            // Valeurs par défaut en cas d'erreur
        }
        
        return $analysis;
    }
    
    /**
     * Analyser la complexité du code
     */
    private function analyzeCodeComplexity($code, $language) {
        $complexity = [
            'score' => 0,
            'factors' => []
        ];
        
        $lines = explode("\n", $code);
        $lineCount = count($lines);
        
        // Facteurs de complexité basés sur le nombre de lignes
        if ($lineCount > 50) {
            $complexity['score'] += 3;
            $complexity['factors'][] = 'Code très long (>50 lignes)';
        } elseif ($lineCount > 20) {
            $complexity['score'] += 2;
            $complexity['factors'][] = 'Code long (>20 lignes)';
        } elseif ($lineCount > 10) {
            $complexity['score'] += 1;
            $complexity['factors'][] = 'Code moyen (>10 lignes)';
        }
        
        // Analyse des structures de contrôle
        $patterns = [
            'loops' => [
                'patterns' => ['/\bfor\s*\(/i', '/\bwhile\s*\(/i', '/\bforeach\s*\(/i', '/\bdo\s*\{/i'],
                'score' => 1,
                'description' => 'Boucles détectées'
            ],
            'nested_loops' => [
                'patterns' => ['/for\s*\([^}]*for\s*\(/i', '/while\s*\([^}]*while\s*\(/i'],
                'score' => 2,
                'description' => 'Boucles imbriquées'
            ],
            'recursion' => [
                'patterns' => ['/function\s+(\w+)[^}]*\1\s*\(/i'],
                'score' => 2,
                'description' => 'Récursion détectée'
            ],
            'complex_conditions' => [
                'patterns' => ['/if\s*\([^)]*&&[^)]*\|\|/i', '/\?\s*[^:]*:\s*[^;]*/i'],
                'score' => 1,
                'description' => 'Conditions complexes'
            ],
            'data_structures' => [
                'patterns' => ['/\barray\b/i', '/\blist\b/i', '/\bdict\b/i', '/\bmap\b/i', '/\bset\b/i'],
                'score' => 1,
                'description' => 'Structures de données avancées'
            ],
            'algorithms' => [
                'patterns' => ['/sort/i', '/search/i', '/binary/i', '/hash/i', '/tree/i', '/graph/i'],
                'score' => 2,
                'description' => 'Algorithmes avancés'
            ]
        ];
        
        foreach ($patterns as $type => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (preg_match($pattern, $code)) {
                    $complexity['score'] += $config['score'];
                    $complexity['factors'][] = $config['description'];
                    break; // Une seule détection par type
                }
            }
        }
        
        // Analyse spécifique par langage
        $complexity = $this->analyzeLanguageSpecific($code, $language, $complexity);
        
        return $complexity;
    }
    
    /**
     * Analyser la complexité de la description
     */
    private function analyzeDescriptionComplexity($description) {
        $complexity = [
            'score' => 0,
            'factors' => []
        ];
        
        $wordCount = str_word_count($description);
        
        // Complexité basée sur la longueur
        if ($wordCount > 200) {
            $complexity['score'] += 2;
            $complexity['factors'][] = 'Description très détaillée';
        } elseif ($wordCount > 100) {
            $complexity['score'] += 1;
            $complexity['factors'][] = 'Description détaillée';
        }
        
        // Mots-clés indiquant de la complexité
        $complexKeywords = [
            'algorithme' => 2,
            'optimisation' => 2,
            'complexité' => 2,
            'récursion' => 2,
            'dynamique' => 3,
            'graphe' => 3,
            'arbre' => 2,
            'tri' => 1,
            'recherche' => 1,
            'performance' => 2,
            'mémoire' => 2,
            'efficacité' => 2
        ];
        
        $descriptionLower = strtolower($description);
        foreach ($complexKeywords as $keyword => $score) {
            if (strpos($descriptionLower, $keyword) !== false) {
                $complexity['score'] += $score;
                $complexity['factors'][] = "Concept avancé: $keyword";
            }
        }
        
        return $complexity;
    }
    
    /**
     * Analyser les erreurs dans le code
     */
    private function analyzeErrors($code, $language) {
        $errorAnalysis = [
            'count' => 0,
            'types' => [],
            'severity' => 'low'
        ];
        
        // Erreurs de syntaxe communes
        $syntaxErrors = [
            'missing_semicolon' => [
                'patterns' => ['/\w+\s*\n(?!\s*[;}])/'],
                'languages' => ['javascript', 'java', 'c', 'cpp', 'csharp'],
                'description' => 'Point-virgule manquant'
            ],
            'unmatched_braces' => [
                'patterns' => ['/\{[^}]*$/m', '/^[^{]*\}/m'],
                'languages' => ['all'],
                'description' => 'Accolades non appariées'
            ],
            'undefined_variables' => [
                'patterns' => ['/\$\w+(?!\s*=)/', '/\b[a-z]\w*(?!\s*[=\(])/'],
                'languages' => ['php', 'python', 'javascript'],
                'description' => 'Variables potentiellement non définies'
            ]
        ];
        
        // Erreurs logiques
        $logicErrors = [
            'infinite_loop' => [
                'patterns' => ['/while\s*\(\s*true\s*\)/', '/for\s*\(\s*;\s*;\s*\)/'],
                'description' => 'Boucle infinie potentielle'
            ],
            'division_by_zero' => [
                'patterns' => ['/\/\s*0/', '/\/\s*\$?\w*(?=\s*[;\n])/'],
                'description' => 'Division par zéro possible'
            ],
            'array_bounds' => [
                'patterns' => ['/\[\s*\d+\s*\]/', '/\[.*length.*\]/'],
                'description' => 'Accès tableau hors limites'
            ]
        ];
        
        // Compter les erreurs
        $allErrors = array_merge($syntaxErrors, $logicErrors);
        
        foreach ($allErrors as $errorType => $config) {
            if (isset($config['languages']) && 
                $config['languages'] !== ['all'] && 
                !in_array($language, $config['languages'])) {
                continue;
            }
            
            foreach ($config['patterns'] as $pattern) {
                if (preg_match($pattern, $code)) {
                    $errorAnalysis['count']++;
                    $errorAnalysis['types'][] = $config['description'];
                }
            }
        }
        
        // Déterminer la sévérité
        if ($errorAnalysis['count'] > 5) {
            $errorAnalysis['severity'] = 'high';
        } elseif ($errorAnalysis['count'] > 2) {
            $errorAnalysis['severity'] = 'medium';
        }
        
        return $errorAnalysis;
    }
    
    /**
     * Calculer la difficulté finale
     */
    private function calculateDifficulty($codeComplexity, $descriptionComplexity, $errorAnalysis) {
        $totalScore = $codeComplexity['score'] + $descriptionComplexity['score'];
        
        // Ajuster selon le nombre d'erreurs
        if ($errorAnalysis['count'] > 5) {
            $totalScore += 3;
        } elseif ($errorAnalysis['count'] > 2) {
            $totalScore += 2;
        } elseif ($errorAnalysis['count'] > 0) {
            $totalScore += 1;
        }
        
        // Déterminer la difficulté
        if ($totalScore >= 8) {
            return 'hard';
        } elseif ($totalScore >= 4) {
            return 'medium';
        } else {
            return 'easy';
                    }
    }
    
    /**
     * Calculer le score de base
     */
    private function calculateBaseScore($difficulty, $errorAnalysis) {
        $baseScore = self::DIFFICULTY_SCORES[$difficulty];
        
        // Bonus selon le nombre d'erreurs (plus d'erreurs = plus de points)
        $errorBonus = min($errorAnalysis['count'] * 2, 15); // Max 15 points bonus
        
        // Bonus selon la sévérité
        $severityBonus = 0;
        switch ($errorAnalysis['severity']) {
            case 'high':
                $severityBonus = 10;
                break;
            case 'medium':
                $severityBonus = 5;
                break;
            case 'low':
                $severityBonus = 2;
                break;
        }
        
        return $baseScore + $errorBonus + $severityBonus;
    }
    
    /**
     * Analyser spécifiquement selon le langage
     */
    private function analyzeLanguageSpecific($code, $language, $complexity) {
        switch ($language) {
            case 'python':
                // Détection de compréhensions de liste, décorateurs, etc.
                if (preg_match('/\[.*for.*in.*\]/', $code)) {
                    $complexity['score'] += 1;
                    $complexity['factors'][] = 'Compréhension de liste Python';
                }
                if (preg_match('/@\w+/', $code)) {
                    $complexity['score'] += 2;
                    $complexity['factors'][] = 'Décorateurs Python';
                }
                break;
                
            case 'javascript':
                // Détection de promesses, async/await, closures
                if (preg_match('/async|await|Promise/', $code)) {
                    $complexity['score'] += 2;
                    $complexity['factors'][] = 'Programmation asynchrone JS';
                }
                if (preg_match('/function.*return.*function/', $code)) {
                    $complexity['score'] += 2;
                    $complexity['factors'][] = 'Closures JavaScript';
                }
                break;
                
            case 'java':
                // Détection de génériques, streams, etc.
                if (preg_match('/<.*>/', $code)) {
                    $complexity['score'] += 1;
                    $complexity['factors'][] = 'Génériques Java';
                }
                if (preg_match('/\.stream\(\)/', $code)) {
                    $complexity['score'] += 2;
                    $complexity['factors'][] = 'Streams Java';
                }
                break;
                
            case 'cpp':
                // Détection de templates, pointeurs, etc.
                if (preg_match('/template\s*</', $code)) {
                    $complexity['score'] += 3;
                    $complexity['factors'][] = 'Templates C++';
                }
                if (preg_match('/\*\w+|&\w+/', $code)) {
                    $complexity['score'] += 2;
                    $complexity['factors'][] = 'Gestion mémoire C++';
                }
                break;
        }
        
        return $complexity;
    }
    
    /**
     * Calculer la confiance de l'IA
     */
    private function calculateConfidence($codeComplexity, $descriptionComplexity, $errorAnalysis) {
        $confidence = 0.5; // Base
        
        // Plus de facteurs détectés = plus de confiance
        $totalFactors = count($codeComplexity['factors']) + count($descriptionComplexity['factors']);
        $confidence += min($totalFactors * 0.1, 0.4);
        
        // Erreurs détectées augmentent la confiance
        if ($errorAnalysis['count'] > 0) {
            $confidence += 0.1;
        }
        
        return min($confidence, 1.0);
    }
    
    /**
     * Calculer le score final pour une solution
     */
    public function calculateSolutionScore($problemId, $userId, $baseScore) {
        require_once 'db_connect.php';
        $conn = connect();
        
        $finalScore = $baseScore;
        
        try {
            // Vérifier si c'est la première solution acceptée pour ce problème
            $stmt = $conn->prepare("
                SELECT COUNT(*) as accepted_count 
                FROM solutions 
                WHERE problem_id = ? AND status IN ('approved', 'accepted')
            ");
            $stmt->execute([$problemId]);
            $result = $stmt->fetch();
            
            if ($result['accepted_count'] == 0) {
                // Première solution acceptée = bonus
                $finalScore += self::FIRST_ACCEPTED_BONUS;
            }
            
            // Vérifier les solutions précédentes non acceptées de cet utilisateur
            $stmt = $conn->prepare("
                SELECT COUNT(*) as unaccepted_count 
                FROM solutions 
                WHERE problem_id = ? AND user_id = ? AND status IN ('rejected', 'pending')
            ");
            $stmt->execute([$problemId, $userId]);
            $result = $stmt->fetch();
            
            if ($result['unaccepted_count'] > 0) {
                // Bonus pour persévérance
                $finalScore += ($result['unaccepted_count'] * self::UNACCEPTED_SOLUTION_BONUS);
            }
            
        } catch (Exception $e) {
            error_log("Erreur calcul score solution: " . $e->getMessage());
        }
        
        return $finalScore;
    }
    
    /**
     * Générer un rapport d'analyse détaillé
     */
    public function generateAnalysisReport($analysis) {
        $report = [
            'summary' => $this->generateSummary($analysis),
            'details' => $this->generateDetails($analysis),
            'recommendations' => $this->generateRecommendations($analysis)
        ];
        
        return $report;
    }
    
    private function generateSummary($analysis) {
        $difficultyLabels = [
            'easy' => 'Facile',
            'medium' => 'Moyen', 
            'hard' => 'Difficile'
        ];
        
        return [
            'difficulty' => $difficultyLabels[$analysis['difficulty']],
            'base_score' => $analysis['base_score'],
            'error_count' => $analysis['error_count'],
            'confidence' => round($analysis['ai_confidence'] * 100, 1)
        ];
    }
    
    private function generateDetails($analysis) {
        return [
            'complexity_factors' => $analysis['complexity_factors'],
            'error_types' => $analysis['error_types'],
            'scoring_breakdown' => [
                'base_difficulty' => self::DIFFICULTY_SCORES[$analysis['difficulty']],
                'error_bonus' => min($analysis['error_count'] * 2, 15),
                'total' => $analysis['base_score']
            ]
        ];
    }
    
    private function generateRecommendations($analysis) {
        $recommendations = [];
        
        if ($analysis['error_count'] > 5) {
            $recommendations[] = "Problème complexe avec de nombreuses erreurs - idéal pour les développeurs expérimentés";
        } elseif ($analysis['error_count'] > 2) {
            $recommendations[] = "Bon équilibre entre difficulté et apprentissage";
        } else {
            $recommendations[] = "Problème accessible pour les débutants";
        }
        
        if ($analysis['ai_confidence'] < 0.7) {
            $recommendations[] = "Analyse nécessitant une révision manuelle recommandée";
        }
        
        if (in_array('Récursion détectée', $analysis['complexity_factors'])) {
            $recommendations[] = "Excellent pour pratiquer la programmation récursive";
        }
        
        return $recommendations;
    }
}
?>
