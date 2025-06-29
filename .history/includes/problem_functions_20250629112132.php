<?php
/**
 * Fonctions pour la gestion des problèmes
 */

/**
 * Récupérer un problème avec ses métadonnées
 */
function getProblemById($conn, $problem_id, $user_id = 0) {
    try {
        if ($user_id > 0) {
            $stmt = $conn->prepare("
                SELECT p.*, u.username, u.avatar_url, u.name as author_name,
                     CASE WHEN EXISTS(SELECT 1 FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) THEN 1 ELSE 0 END AS is_favorite,
                     CASE WHEN EXISTS(SELECT 1 FROM solutions WHERE problem_id = p.problem_id AND user_id = ? AND status IN ('approved','accepted')) THEN 1 ELSE 0 END AS is_solved
                FROM problems p 
                INNER JOIN users u ON p.user_id = u.id
                WHERE p.problem_id = ?
            ");
            $stmt->execute([$user_id, $user_id, $problem_id]);
        } else {
            $stmt = $conn->prepare("
                SELECT p.*, u.username, u.avatar_url, u.name as author_name,
                     0 AS is_favorite,
                     0 AS is_solved
                FROM problems p 
                INNER JOIN users u ON p.user_id = u.id
                WHERE p.problem_id = ?
            ");
            $stmt->execute([$problem_id]);
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur getProblemById: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupérer les solutions approuvées d'un problème
 */
function getProblemSolutions($conn, $problem_id) {
    try {
        $stmt = $conn->prepare("
            SELECT s.*, u.username, u.avatar_url
            FROM solutions s
            INNER JOIN users u ON s.user_id = u.id
            WHERE s.problem_id = ? AND s.status = 'approved'
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$problem_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur getProblemSolutions: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupérer les statistiques d'un problème
 */
function getProblemStats($conn, $problem_id) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_solutions,
                SUM(CASE WHEN status IN ('approved','accepted') THEN 1 ELSE 0 END) as approved_solutions
            FROM solutions
            WHERE problem_id = ?
        ");
        $stmt->execute([$problem_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur getProblemStats: " . $e->getMessage());
        return ['total_solutions' => 0, 'approved_solutions' => 0];
    }
}

/**
 * Récupérer la dernière solution de l'utilisateur pour un problème
 */
function getUserSolutionForProblem($conn, $problem_id, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT TOP 1 id, status, created_at, evaluated_at
            FROM solutions
            WHERE problem_id = ? AND user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$problem_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur getUserSolutionForProblem: " . $e->getMessage());
        return null;
    }
}

/**
 * Formater la difficulté en français
 */
function formatDifficulty($difficulty) {
    $difficulty_labels = [
        'easy' => 'Facile',
        'medium' => 'Moyen',
        'hard' => 'Difficile'
    ];
    return $difficulty_labels[$difficulty] ?? ucfirst($difficulty);
}

/**
 * Formater les tags en tableau
 */
function formatTags($tags_string) {
    if (empty($tags_string)) {
        return [];
    }
    return array_map('trim', explode(',', $tags_string));
}
?>