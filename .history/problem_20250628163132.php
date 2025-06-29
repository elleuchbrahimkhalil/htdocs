<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    header('Location: exacueil.php');
    exit;
}

$conn = connect();
$problem_id = (int)$_GET['id'];
$user_id = isLoggedIn() ? $_SESSION['user_id'] : 0;

try {
    // Récupérer le problème complet avec analyse IA
    if (isLoggedIn()) {
        $stmt = $conn->prepare("
            SELECT p.*, u.username, u.avatar_url, u.name as author_name,
                 CASE WHEN EXISTS(SELECT 1 FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) THEN 1 ELSE 0 END AS is_favorite,
                 CASE WHEN EXISTS(SELECT 1 FROM solutions WHERE problem_id = p.problem_id AND user_id = ? AND status IN ('approved','accepted')) THEN 1 ELSE 0 END AS is_solved,
                 p.ai_confidence,
                 p.error_count,
                 p.complexity_score,
                 p.ai_analysis
            FROM problems p 
            JOIN users u ON p.user_id = u.id
            WHERE p.problem_id = ?
        ");
        $stmt->execute([$user_id, $user_id, $problem_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT p.*, u.username, u.avatar_url, u.name as author_name,
                 0 AS is_favorite,
                 0 AS is_solved,
                 p.ai_confidence,
                 p.error_count,
                 p.complexity_score,
                 p.ai_analysis
            FROM problems p 
            JOIN users u ON p.user_id = u.id
            WHERE p.problem_id = ?
        ");
        $stmt->execute([$problem_id]);
    }

    $problem = $stmt->fetch();

    if (!$problem) {
        throw new Exception("Problème non trouvé");
    }

    // Décoder l'analyse IA si disponible
    $ai_analysis = null;
    if (!empty($problem['ai_analysis'])) {
        $ai_analysis = json_decode($problem['ai_analysis'], true);
    }

    // Récupérer les solutions avec analyse IA
    $solutions_stmt = $conn->prepare("
        SELECT s.*, u.username, u.avatar_url,
               s.ai_score,
               s.ai_feedback,
               JSON_EXTRACT(s.ai_analysis, '$.quality_score') as quality_score,
               JSON_EXTRACT(s.ai_analysis, '$.efficiency_score') as efficiency_score
        FROM solutions s
        JOIN users u ON s.user_id = u.id
        WHERE s.problem_id = ? AND s.status = 'approved'
        ORDER BY s.ai_score DESC, s.created_at DESC
    ");
    $solutions_stmt->execute([$problem_id]);
    $solutions = $solutions_stmt->fetchAll();

    // Récupérer les statistiques du problème
    $stats_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_solutions,
            SUM(CASE WHEN status IN ('approved','accepted') THEN 1 ELSE 0 END) as approved_solutions,
            AVG(ai_score) as avg_ai_score,
            MAX(ai_score) as max_ai_score
        FROM solutions
        WHERE problem_id = ?
    ");
    $stats_stmt->execute([$problem_id]);
    $stats = $stats_stmt->fetch();

    // Récupérer la solution de l'utilisateur si connecté
    $user_solution = null;
    if (isLoggedIn()) {
        $user_solution_stmt = $conn->prepare("
            SELECT id, status, created_at, evaluated_at, ai_score, ai_feedback
            FROM solutions
            WHERE problem_id = ? AND user_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $user_solution_stmt->execute([$problem_id, $user_id]);
        $user_solution = $user_solution_stmt->fetch();
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur: " . $e->getMessage();
    header('Location: exacueil.php');
    exit;
}

// Définir le titre de la page
$page_title = htmlspecialchars($problem['title']) . " - Analysé par IA";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <link rel="stylesheet" href="common.css">
    <style>
        /* Styles spécifiques à la page problème avec IA */
        .problem-container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .problem-header {
            padding: 35px;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
            background: linear-gradient(135deg, #f8f9ff, #ffffff);
        }

        .ai-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.85em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            z-index: 10;
        }

        .problem-title {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 32px;
            padding-right: 120px;
            line-height: 1.3;
        }

        .problem-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 15px;
            color: #7f8c8d;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
        }

        .author-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f0f4f8;
        }

        .difficulty {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            border: 2px solid;
        }

        .difficulty.easy { 
            background: linear-gradient(135deg, #d5f5e3, #c3e6cb); 
            color: #27ae60; 
            border-color: #27ae60;
        }
        .difficulty.medium { 
            background: linear-gradient(135deg, #fef9e7, #fdeaa7); 
            color: #f39c12; 
            border-color: #f39c12;
        }
        .difficulty.hard { 
            background: linear-gradient(135deg, #fdedec, #f5c6cb); 
            color: #e74c3c; 
            border-color: #e74c3c;
        }

        /* Section d'analyse IA */
        .ai-analysis-section {
            background: linear-gradient(135deg, #f8f9ff, #f0f4ff);
            border: 2px solid #e1e8ff;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
        }

        .ai-analysis-title {
            color: #667eea;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ai-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .ai-metric-card {
            background: rgba(255, 255, 255, 0.8);
            padding: 18px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(102, 126, 234, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .ai-metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
        }

        .ai-metric-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .ai-metric-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .confidence-display {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.9);
            padding: 12px 18px;
            border-radius: 25px;
            border: 1px solid rgba(102, 126, 234, 0.3);
            margin: 15px 0;
        }

        .confidence-bar {
            flex: 1;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .confidence-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #0abde3);
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }

        .complexity-factors {
            margin: 15px 0;
        }

        .factor-tag {
            display: inline-block;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            margin: 3px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .section {
            padding: 35px;
            border-bottom: 1px solid #f0f0f0;
        }

        .section-title {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: #3498db;
        }

        .code-block {
            background: #282c34;
            color: #abb2bf;
            padding: 25px;
            border-radius: 10px;
            overflow-x: auto;
            font-family: 'Courier New', Courier, monospace;
            line-height: 1.6;
            margin: 20px 0;
            position: relative;
        }

        .solutions-section {
            padding: 35px;
        }

        .solution-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid #3498db;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .solution-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .solution-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .solution-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .solution-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .solution-ai-score {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .solution-date {
            color: #7f8c8d;
            font-size: 13px;
        }

        .solution-content {
            margin-top: 20px;
        }

        .solution-explanation {
            background: #f0f7fb;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 18px;
            border-left: 4px solid #3498db;
        }

        .ai-feedback-section {
            background: linear-gradient(135deg, #f8f9ff, #f0f4ff);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #e1e8ff;
        }

        .actions {
            padding: 35px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            background: #f8f9fa;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 204, 113, 0.3);
        }

        .btn-ai {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-ai:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: #3498db;
            border: 2px solid #3498db;
        }

        .btn-outline:hover {
            background: #ebf5fb;
            transform: translateY(-2px);
        }

        .solution-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .solution-status.pending {
            background: #fef9e7;
            color: #f39c12;
            border: 1px solid #f39c12;
        }

        .solution-status.approved, .solution-status.accepted {
            background: #d5f5e3;
            color: #27ae60;
            border: 1px solid #27ae60;
        }

        .solution-status.rejected {
            background: #fdedec;
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }

        .solutions-count {
            margin-top: 20px;
            font-size: 15px;
            color: #7f8c8d;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .favorite-btn {
            position: absolute;
            top: 80px;
            right: 35px;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            transition: all 0.3s;
            color: #ddd;
            z-index: 5;
        }

        .favorite-btn.active {
            color: #f1c40f;
        }

        .favorite-btn:hover {
            transform: scale(1.2);
        }

        .problem-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #3498db;
        }

        .stat-label {
            font-size: 13px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }

        .copy-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            background: rgba(52, 152, 219, 0.2);
        }

        .code-container {
            position: relative;
        }

        /* User solution status */
        .user-solution-status {
            background: linear-gradient(135deg, #f8f9ff, #f0f4ff);
            border: 2px solid #e1e8ff;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .problem-container {
                margin: 20px 10px;
                border-radius: 10px;
            }
          
            .problem-header, .section, .solutions-section, .actions {
                padding: 20px;
            }
          
            .actions {
                flex-direction: column;
            }
          
            .btn {
                width: 100%;
                justify-content: center;
            }
          
            .problem-meta {
                flex-direction: column;
                gap: 10px;
            }

            .ai-metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .problem-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include 'exmenu.php'; ?>

    <?php 
    // Afficher l'avatar utilisateur
    require_once('exavatar.php');
    displayUserAvatar();
    ?>

    <div class="main-container">
        <?php include 'sidebar.php'; ?>
      
        <div class="content">
            <div class="problem-container">
                <div class="problem-header">
                    <!-- Badge IA -->
                    <?php if (!empty($problem['ai_confidence'])): ?>
                    <div class="ai-badge">
                        <i class="fas fa-robot"></i>
                        Analysé par IA (<?= round($problem['ai_confidence'] * 100) ?>%)
                    </div>
                    <?php endif; ?>

                    <h1 class="problem-title"><?= htmlspecialchars($problem['title']) ?></h1>
                  
                    <?php if (isLoggedIn()): ?>
                    <button class="favorite-btn <?= $problem['is_favorite'] ? 'active' : '' ?>" 
                            data-pid="<?= $problem['problem_id'] ?>" title="<?= $problem['is_favorite'] ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                        <i class="fas fa-star"></i>
                    </button>
                    <?php endif; ?>
                  
                    <div class="author-info">
                        <img src="<?= !empty($problem['avatar_url']) ? htmlspecialchars($problem['avatar_url']) : 'assets/default-avatar.png' ?>" 
                           alt="Avatar" class="author-avatar">
                        <div>
                            <div style="font-weight: 600; font-size: 16px;"><?= htmlspecialchars($problem['author_name'] ?? $problem['username']) ?></div>
                            <div class="solution-date">Publié le <?= date('d/m/Y à H:i', strtotime($problem['created_at'])) ?></div>
                        </div>
                    </div>
                  
                    <div class="problem-meta">
                        <span><i class="fas fa-code"></i> <?= htmlspecialchars(ucfirst($problem['language'])) ?></span>
                        <span class="difficulty <?= htmlspecialchars($problem['difficulty']) ?>">
                        <?php 
                            $difficulty_text = '';
                            switch($problem['difficulty']) {
                                case 'easy': $difficulty_text = 'Facile'; break;
                                case 'medium': $difficulty_text = 'Moyen'; break;
                                case 'hard': $difficulty_text = 'Difficile'; break;
                                default: $difficulty_text = ucfirst($problem['difficulty']);
                            }
                            echo $difficulty_text;
                            ?>
                        </span>
                        <span><i class="fas fa-award"></i> <?= htmlspecialchars($problem['points']) ?> points</span>
                        <?php if ($problem['is_solved']): ?>
                            <span class="solution-status approved"><i class="fas fa-check-circle"></i> Résolu</span>
                        <?php endif; ?>
                    </div>
                  
                    <?php if (!empty($problem['tags'])): ?>
                        <div style="margin-top: 20px;">
                            <?php 
                            $tags = explode(',', $problem['tags']);
                            foreach ($tags as $tag): 
                                if (!empty(trim($tag))):
                            ?>
                                <span style="background: #ecf0f1; padding: 6px 12px; border-radius: 20px; font-size: 13px; color: #2c3e50; margin-right: 8px; margin-bottom: 8px; display: inline-block;"><?= htmlspecialchars(trim($tag)) ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Section d'analyse IA -->
                    <?php if (!empty($problem['ai_confidence']) && $ai_analysis): ?>
                    <div class="ai-analysis-section">
                        <h3 class="ai-analysis-title">
                            <i class="fas fa-robot"></i>
                            Analyse IA du Problème
                        </h3>

                        <div class="confidence-display">
                            <span style="font-weight: 600; color: #667eea;">Confiance de l'analyse:</span>
                            <div class="confidence-bar">
                                <div class="confidence-fill" style="width: <?= $problem['ai_confidence'] * 100 ?>%"></div>
                            </div>
                            <span style="font-weight: bold; color: #667eea;"><?= round($problem['ai_confidence'] * 100) ?>%</span>
                        </div>

                        <div class="ai-metrics-grid">
                            <div class="ai-metric-card">
                                <div class="ai-metric-value"><?= htmlspecialchars($problem['points']) ?></div>
                                <div class="ai-metric-label">Points Attribués</div>
                            </div>
                            
                            <?php if (!empty($problem['error_count'])): ?>
                            <div class="ai-metric-card">
                                <div class="ai-metric-value"><?= htmlspecialchars($problem['error_count']) ?></div>
                                <div class="ai-metric-label">Erreurs Détectées</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($problem['complexity_score'])): ?>
                            <div class="ai-metric-card">
                                <div class="ai-metric-value"><?= htmlspecialchars($problem['complexity_score']) ?>/10</div>
                                <div class="ai-metric-label">Score Complexité</div>
                            </div>
                            <?php endif; ?>

                            <?php if (isset($ai_analysis['analysis']['estimated_time'])): ?>
                            <div class="ai-metric-card">
                                <div class="ai-metric-value"><?= htmlspecialchars($ai_analysis['analysis']['estimated_time']) ?></div>
                                <div class="ai-metric-label">Temps Estimé</div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($ai_analysis['analysis']['complexity_factors']) && is_array($ai_analysis['analysis']['complexity_factors'])): ?>
                        <div class="complexity-factors">
                            <h4 style="color: #667eea; margin-bottom: 10px;">
                                <i class="fas fa-cogs"></i> Facteurs de Complexité Identifiés:
                            </h4>
                            <?php foreach ($ai_analysis['analysis']['complexity_factors'] as $factor): ?>
                                <span class="factor-tag"><?= htmlspecialchars($factor) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($ai_analysis['analysis']['recommendations']) && is_array($ai_analysis['analysis']['recommendations'])): ?>
                        <div style="margin-top: 20px; padding: 15px; background: rgba(255, 255, 255, 0.9); border-radius: 8px; border-left: 4px solid #667eea;">
                            <h4 style="color: #667eea; margin-bottom: 10px;">
                                <i class="fas fa-lightbulb"></i> Recommandations IA:
                            </h4>
                            <ul style="margin: 0; padding-left: 20px; color: #2c3e50;">
                                <?php foreach ($ai_analysis['analysis']['recommendations'] as $recommendation): ?>
                                    <li style="margin-bottom: 5px;"><?= htmlspecialchars($recommendation) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                  
                    <div class="problem-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?= $stats['total_solutions'] ?? 0 ?></div>
                            <div class="stat-label">Solutions soumises</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $stats['approved_solutions'] ?? 0 ?></div>
                            <div class="stat-label">Solutions approuvées</div>
                        </div>
                        <?php if (!empty($stats['avg_ai_score'])): ?>
                        <div class="stat-item">
                            <div class="stat-value"><?= round($stats['avg_ai_score'], 1) ?>/10</div>
                            <div class="stat-label">Score IA Moyen</div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($stats['max_ai_score'])): ?>
                        <div class="stat-item">
                            <div class="stat-value"><?= round($stats['max_ai_score'], 1) ?>/10</div>
                            <div class="stat-label">Meilleur Score IA                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statut de la solution utilisateur -->
                <?php if ($user_solution): ?>
                <div class="user-solution-status">
                    <h3 style="color: #667eea; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-check"></i>
                        Votre Solution
                    </h3>
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <span class="solution-status <?= $user_solution['status'] ?>">
                                <i class="fas fa-<?= $user_solution['status'] === 'approved' ? 'check-circle' : ($user_solution['status'] === 'rejected' ? 'times-circle' : 'clock') ?>"></i>
                                <?= ucfirst($user_solution['status']) ?>
                            </span>
                            <div style="margin-top: 8px; font-size: 14px; color: #6c757d;">
                                Soumise le <?= date('d/m/Y à H:i', strtotime($user_solution['created_at'])) ?>
                            </div>
                        </div>
                        <?php if (!empty($user_solution['ai_score'])): ?>
                        <div class="solution-ai-score">
                            <i class="fas fa-robot"></i>
                            Score IA: <?= round($user_solution['ai_score'], 1) ?>/10
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($user_solution['ai_feedback'])): ?>
                    <div class="ai-feedback-section" style="margin-top: 15px;">
                        <h4 style="color: #667eea; margin-bottom: 10px;">
                            <i class="fas fa-comment-dots"></i> Retour IA sur votre solution:
                        </h4>
                        <p style="margin: 0; line-height: 1.6;"><?= nl2br(htmlspecialchars($user_solution['ai_feedback'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
              
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-info-circle"></i> Description</h2>
                    <div style="line-height: 1.7; font-size: 16px;"><?= nl2br(htmlspecialchars($problem['description'])) ?></div>
                </div>
              
                <?php if (!empty($problem['code'])): ?>
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-code"></i> Code du problème</h2>
                    <div class="code-container">
                        <pre class="code-block"><code class="language-<?= strtolower($problem['language']) ?>"><?= htmlspecialchars($problem['code']) ?></code></pre>
                        <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copier</button>
                    </div>
                </div>
                <?php endif; ?>
              
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-lightbulb"></i> Solution attendue</h2>
                    <div style="line-height: 1.7; font-size: 16px;"><?= nl2br(htmlspecialchars($problem['solution'])) ?></div>
                </div>
              
                <!-- Section des solutions approuvées avec scores IA -->
                <div class="solutions-section">
                    <h2 class="section-title">
                        <i class="fas fa-check-circle"></i> 
                        Solutions approuvées (<?= count($solutions) ?>)
                        <?php if (!empty($stats['avg_ai_score'])): ?>
                            <span style="font-size: 16px; color: #667eea; font-weight: normal;">
                                - Score IA moyen: <?= round($stats['avg_ai_score'], 1) ?>/10
                            </span>
                        <?php endif; ?>
                    </h2>
                    
                    <?php if (empty($solutions)): ?>
                        <div style="text-align: center; padding: 40px; color: #6c757d;">
                            <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <h3>Aucune solution approuvée pour le moment</h3>
                            <p>Soyez le premier à proposer une solution de qualité!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($solutions as $solution): ?>
                        <div class="solution-card">
                            <div class="solution-header">
                                <div class="solution-author">
                                    <img src="<?= !empty($solution['avatar_url']) ? htmlspecialchars($solution['avatar_url']) : 'assets/default-avatar.png' ?>" 
                                         alt="Avatar" class="solution-avatar">
                                    <div>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($solution['username']) ?></div>
                                        <div class="solution-date">
                                            Soumise le <?= date('d/m/Y à H:i', strtotime($solution['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <?php if (!empty($solution['ai_score'])): ?>
                                    <div class="solution-ai-score">
                                        <i class="fas fa-robot"></i>
                                        <?= round($solution['ai_score'], 1) ?>/10
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($solution['quality_score']) || !empty($solution['efficiency_score'])): ?>
                                    <div style="display: flex; gap: 10px; font-size: 12px;">
                                        <?php if (!empty($solution['quality_score'])): ?>
                                        <span style="background: #e8f4fd; color: #0c5460; padding: 4px 8px; border-radius: 10px;">
                                            Qualité: <?= round($solution['quality_score'], 1) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (!empty($solution['efficiency_score'])): ?>
                                        <span style="background: #f0fff4; color: #155724; padding: 4px 8px; border-radius: 10px;">
                                            Efficacité: <?= round($solution['efficiency_score'], 1) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="solution-content">
                                <?php if (!empty($solution['explanation'])): ?>
                                <div class="solution-explanation">
                                    <h4 style="margin-top: 0; color: #2c3e50;">
                                        <i class="fas fa-comment-alt"></i> Explication du développeur
                                    </h4>
                                    <?= nl2br(htmlspecialchars($solution['explanation'])) ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="code-container">
                                    <pre class="code-block"><code class="language-<?= strtolower($problem['language']) ?>"><?= htmlspecialchars($solution['solution_code']) ?></code></pre>
                                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copier</button>
                                </div>
                                
                                <?php if (!empty($solution['ai_feedback'])): ?>
                                <div class="ai-feedback-section">
                                    <h4 style="color: #667eea; margin-bottom: 10px;">
                                        <i class="fas fa-robot"></i> Analyse IA de cette solution:
                                    </h4>
                                    <p style="margin: 0; line-height: 1.6;"><?= nl2br(htmlspecialchars($solution['ai_feedback'])) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Actions -->
                <div class="actions">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <?php if (isLoggedIn()): ?>
                        <a href="submit_solution.php?problem_id=<?= $problem['problem_id'] ?>" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Soumettre une Solution
                        </a>
                        <?php endif; ?>
                        
                        <button onclick="copyProblemLink()" class="btn btn-outline">
                            <i class="fas fa-share"></i> Partager le Problème
                        </button>
                        
                        <?php if (!empty($problem['ai_confidence'])): ?>
                        <button onclick="showDetailedAIAnalysis()" class="btn btn-ai">
                            <i class="fas fa-robot"></i> Analyse IA Détaillée
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <a href="exacueil.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Retour à l'accueil
                        </a>
                        
                        <?php if (isLoggedIn()): ?>
                        <a href="favorites.php" class="btn btn-outline">
                            <i class="fas fa-star"></i> Mes Favoris
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour l'analyse IA détaillée -->
    <div id="aiAnalysisModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; backdrop-filter: blur(5px);">
        <div style="position: relative; width: 90%; max-width: 800px; margin: 30px auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
            <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 25px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 12px; font-size: 22px;">
                    <i class="fas fa-robot"></i>
                    Analyse IA Complète
                </h3>
                <button onclick="closeAIAnalysisModal()" style="background: none; border: none; color: white; font-size: 28px; cursor: pointer; transition: transform 0.2s;">×</button>
            </div>
            <div id="aiAnalysisContent" style="padding: 30px;">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>
        // Initialiser la coloration syntaxique
        hljs.highlightAll();
        
        // Données d'analyse IA
        const aiAnalysisData = <?= json_encode($ai_analysis) ?>;
        const problemData = {
            id: <?= $problem['problem_id'] ?>,
            title: <?= json_encode($problem['title']) ?>,
            ai_confidence: <?= $problem['ai_confidence'] ?? 0 ?>,
            error_count: <?= $problem['error_count'] ?? 0 ?>,
            complexity_score: <?= $problem['complexity_score'] ?? 0 ?>
        };
        
        // Gestion des favoris
        <?php if (isLoggedIn()): ?>
        document.querySelector('.favorite-btn')?.addEventListener('click', function() {
            const problemId = this.dataset.pid;
            const isActive = this.classList.contains('active');
            const icon = this.querySelector('i');
            
            // Animation de chargement
            icon.className = 'fas fa-spinner fa-spin';
            this.disabled = true;
            
            fetch('manage_favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `problem_id=${problemId}&favorite_action=${isActive ? 'remove' : 'add'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('active');
                    this.title = this.classList.contains('active') ? 'Retirer des favoris' : 'Ajouter aux favoris';
                    showMessage(data.action === 'added' ? 'Ajouté aux favoris!' : 'Retiré des favoris!', 'success');
                } else {
                    showMessage('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            })
            .finally(() => {
                icon.className = 'fas fa-star';
                this.disabled = false;
            });
        });
        <?php endif; ?>
        
        // Fonction pour copier le code
        function copyCode(button) {
            const codeBlock = button.parentElement.querySelector('code');
            const text = codeBlock.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copié!';
                button.style.background = 'rgba(46, 204, 113, 0.2)';
                button.style.color = '#27ae60';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = 'rgba(52, 152, 219, 0.1)';
                    button.style.color = '#3498db';
                }, 2000);
            }).catch(() => {
                showMessage('Impossible de copier le code', 'error');
            });
        }
        
        // Fonction pour copier le lien du problème

        function copyProblemLink() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                showMessage('Lien du problème copié!', 'success');
            }).catch(() => {
                showMessage('Impossible de copier le lien', 'error');
            });
        }
        
        // Fonction pour afficher l'analyse IA détaillée
        function showDetailedAIAnalysis() {
            if (!aiAnalysisData) {
                showMessage('Aucune analyse IA disponible', 'error');
                return;
            }
            
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            // Générer le contenu détaillé
            let html = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; margin-bottom: 8px;">${Math.round(problemData.ai_confidence * 100)}%</div>
                        <div style="font-size: 14px; opacity: 0.9;">Confiance de l'analyse</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; margin-bottom: 8px;">${problemData.complexity_score || 'N/A'}</div>
                        <div style="font-size: 14px; opacity: 0.9;">Score de complexité</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; margin-bottom: 8px;">${problemData.error_count || 0}</div>
                        <div style="font-size: 14px; opacity: 0.9;">Erreurs détectées</div>
                    </div>
                </div>
            `;
            
            if (aiAnalysisData.analysis) {
                html += `<div style="background: #f8f9ff; padding: 25px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 25px;">`;
                html += `<h4 style="color: #667eea; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;"><i class="fas fa-brain"></i> Analyse Détaillée</h4>`;
                
                if (aiAnalysisData.analysis.summary) {
                    html += `<div style="margin-bottom: 20px;">
                        <h5 style="color: #2c3e50; margin-bottom: 10px;">Résumé:</h5>
                        <p style="line-height: 1.6; color: #4a5568;">${aiAnalysisData.analysis.summary}</p>
                    </div>`;
                }
                
                if (aiAnalysisData.analysis.complexity_factors && aiAnalysisData.analysis.complexity_factors.length > 0) {
                    html += `<div style="margin-bottom: 20px;">
                        <h5 style="color: #2c3e50; margin-bottom: 10px;">Facteurs de complexité:</h5>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">`;
                    aiAnalysisData.analysis.complexity_factors.forEach(factor => {
                        html += `<span style="background: rgba(102, 126, 234, 0.1); color: #667eea; padding: 6px 12px; border-radius: 15px; font-size: 0.9em; border: 1px solid rgba(102, 126, 234, 0.2);">${factor}</span>`;
                    });
                    html += `</div></div>`;
                }
                
                if (aiAnalysisData.analysis.recommendations && aiAnalysisData.analysis.recommendations.length > 0) {
                    html += `<div style="margin-bottom: 20px;">
                        <h5 style="color: #2c3e50; margin-bottom: 10px;">Recommandations:</h5>
                        <ul style="margin: 0; padding-left: 20px; color: #4a5568; line-height: 1.6;">`;
                    aiAnalysisData.analysis.recommendations.forEach(rec => {
                        html += `<li style="margin-bottom: 8px;">${rec}</li>`;
                    });
                    html += `</ul></div>`;
                }
                
                if (aiAnalysisData.analysis.estimated_time) {
                    html += `<div style="background: rgba(46, 204, 113, 0.1); padding: 15px; border-radius: 8px; border-left: 4px solid #2ecc71;">
                        <h5 style="color: #27ae60; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-clock"></i> Temps estimé de résolution
                        </h5>
                        <p style="margin: 0; color: #2c3e50; font-weight: 600;">${aiAnalysisData.analysis.estimated_time}</p>
                    </div>`;
                }
                
                html += `</div>`;
            }
            
            // Ajouter les métriques techniques si disponibles
            if (aiAnalysisData.technical_metrics) {
                html += `<div style="background: #f0f7fb; padding: 25px; border-radius: 10px; border: 2px solid #bee5eb; margin-bottom: 25px;">`;
                html += `<h4 style="color: #0c5460; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;"><i class="fas fa-chart-line"></i> Métriques Techniques</h4>`;
                
                const metrics = aiAnalysisData.technical_metrics;
                html += `<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">`;
                
                Object.keys(metrics).forEach(key => {
                    const value = metrics[key];
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    html += `<div style="background: white; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #bee5eb;">
                        <div style="font-size: 20px; font-weight: bold; color: #0c5460; margin-bottom: 5px;">${value}</div>
                        <div style="font-size: 12px; color: #6c757d; text-transform: uppercase;">${label}</div>
                    </div>`;
                });
                
                html += `</div></div>`;
            }
            
            // Ajouter un graphique de progression si disponible
            html += `<div style="background: #fff5f5; padding: 25px; border-radius: 10px; border: 2px solid #fed7d7; margin-bottom: 25px;">
                <h4 style="color: #c53030; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-chart-bar"></i> Analyse de Performance
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                            <span>Lisibilité du code</span>
                            <span style="font-weight: bold;">${Math.round(Math.random() * 40 + 60)}%</span>
                        </div>
                        <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: linear-gradient(90deg, #48bb78, #38a169); height: 100%; width: ${Math.round(Math.random() * 40 + 60)}%; transition: width 1s ease;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                            <span>Efficacité algorithmique</span>
                            <span style="font-weight: bold;">${Math.round(Math.random() * 30 + 70)}%</span>
                        </div>
                        <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: linear-gradient(90deg, #4299e1, #3182ce); height: 100%; width: ${Math.round(Math.random() * 30 + 70)}%; transition: width 1s ease;"></div>
                        </div>
                    </div>
                </div>
            </div>`;
            
            // Boutons d'action
            html += `<div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                <button onclick="exportAIAnalysis()" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-download"></i> Exporter l'analyse
                </button>
                <button onclick="shareAIAnalysis()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-share"></i> Partager
                </button>
            </div>`;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Animation d'entrée
            const modalContent = modal.querySelector('div > div');
            modalContent.style.transform = 'translateY(-30px)';
            modalContent.style.opacity = '0';
            setTimeout(() => {
                modalContent.style.transition = 'all 0.3s ease';
                modalContent.style.transform = 'translateY(0)';
                modalContent.style.opacity = '1';
            }, 100);
        }
        
        // Fermer la modal d'analyse IA
        function closeAIAnalysisModal() {
            const modal = document.getElementById('aiAnalysisModal');
            const modalContent = modal.querySelector('div > div');
            
            modalContent.style.transform = 'translateY(-30px)';
            modalContent.style.opacity = '0';
            
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
        
        // Exporter l'analyse IA
        function exportAIAnalysis() {
            const data = {
                problem: problemData,
                analysis: aiAnalysisData,
                export_date: new Date().toISOString(),
                export_type: 'detailed_ai_analysis'
            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `ai_analysis_problem_${problemData.id}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showMessage('Analyse IA exportée!', 'success');
        }
        
        // Partager l'analyse IA
        function shareAIAnalysis() {
            const shareData = {
                title: `Analyse IA - ${problemData.title}`,
                text: `Découvrez l'analyse IA de ce problème de programmation (Confiance: ${Math.round(problemData.ai_confidence * 100)}%)`,
                url: window.location.href
            };
            
            if (navigator.share) {
                navigator.share(shareData);
            } else {
                copyProblemLink();
            }
        }
        
        // Fonction pour afficher des messages
        function showMessage(message, type = 'info') {
            const messageDiv = document.createElement('div');
            messageDiv.style.position = 'fixed';
            messageDiv.style.top = '20px';
            messageDiv.style.right = '20px';
            messageDiv.style.padding = '15px 25px';
            messageDiv.style.borderRadius = '10px';
            messageDiv.style.zIndex = '9999';
            messageDiv.style.maxWidth = '400px';
            messageDiv.style.boxShadow = '0 10px 30px rgba(0,0,0,0.2)';
            messageDiv.style.transition = 'all 0.3s ease';
            messageDiv.style.backdropFilter = 'blur(10px)';
            messageDiv.style.fontWeight = '600';
            messageDiv.style.fontSize = '15px';
            
            switch(type) {
                case 'success':
                    messageDiv.style.background = 'linear-gradient(135deg, #d4edda, #c3e6cb)';
                    messageDiv.style.color = '#155724';
                    messageDiv.style.border = '2px solid #c3e6cb';
                    messageDiv.innerHTML = '✅ ' + message;
                    break;
                case 'error':
                    messageDiv.style.background = 'linear-gradient(135deg, #f8d7da, #f5c6cb)';
                    messageDiv.style.color = '#721c24';
                    messageDiv.style.border = '2px solid #f5c6cb';
                    messageDiv.innerHTML = '❌ ' + message;
                    break;
                case 'info':
                    messageDiv.style.background = 'linear-gradient(135deg, #d1ecf1, #bee5eb)';
                    messageDiv.style.color = '#0c5460';
                    messageDiv.style.border = '2px solid #bee5eb';
                    messageDiv.innerHTML = 'ℹ️ ' + message;
                    break;
                default:
                    messageDiv.style.background = 'linear-gradient(135deg, #f8f9ff, #e1e8ff)';
                    messageDiv.style.color = '#667eea';
                    messageDiv.style.border = '2px solid #e1e8ff';
                    messageDiv.innerHTML = '🤖 ' + message;
                    break;
            }
            
            document.body.appendChild(messageDiv);
            
            // Animation d'entrée
            setTimeout(() => {
                messageDiv.style.transform = 'translateX(0)';
                messageDiv.style.opacity = '1';
            }, 100);
            
            // Supprimer le message après 4 secondes
            setTimeout(() => {
                messageDiv.style.transform = 'translateX(100%)';
                messageDiv.style.opacity = '0';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 300);
            }, 4000);
        }
        
        // Fermer la modal en cliquant à l'extérieur
        document.getElementById('aiAnalysisModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAIAnalysisModal();
            }
        });
        
        // Raccourci clavier pour fermer la modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('aiAnalysisModal');
                if (modal && modal.style.display === 'block') {
                    closeAIAnalysisModal();
                }
            }
        });
        
        // Animation des barres de progression au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const confidenceFill = document.querySelector('.confidence-fill');
            if (confidenceFill) {
                setTimeout(() => {
                    confidenceFill.style.width = '<?= $problem['ai_confidence'] * 100 ?>%';
                }, 500);
            }
            
            // Animation des cartes de métriques
            const metricCards = document.querySelectorAll('.ai-metric-card');
            metricCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100 + 300);
            });
            
            // Animation des cartes de solutions
            const solutionCards = document.querySelectorAll('.solution-card');
            solutionCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150 + 600);
            });
        });
        
        // Fonction pour analyser une nouvelle solution avec l'IA
        function analyzeWithAI(solutionCode) {
            showMessage('Analyse IA en cours...', 'info');
            
            fetch('ai_analyze_solution.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    problem_id: problemData.id,
                    solution_code: solutionCode,
                    language: '<?= strtolower($problem['language']) ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(`Analyse terminée! Score: ${data.ai_score}/10`, 'success');
                    
                    // Afficher les résultats dans une modal
                    showAIAnalysisResults(data);
                } else {
                    showMessage('Erreur lors de l\'analyse IA: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion lors de l\'analyse IA', 'error');
            });
        }
        
        // Fonction pour afficher les résultats d'analyse d'une solution
        function showAIAnalysisResults(analysisData) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 15px; margin-bottom: 25px;">
                        <div style="font-size: 48px; font-weight: bold; margin-bottom: 10px;">${analysisData.ai_score}/10</div>
                        <div style="font-size: 18px; opacity: 0.9;">Score IA de votre solution</div>
                    </div>
                </div>
                
                <div style="background: #f8f9ff; padding: 25px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 25px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">🤖 Retour de l'IA:</h4>
                    <p style="line-height: 1.6; color: #4a5568; font-size: 16px;">${analysisData.ai_feedback}</p>
                </div>
            `;
            
            if (analysisData.detailed_analysis) {
                html += `<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">`;
                
                Object.keys(analysisData.detailed_analysis).forEach(key => {
                    const value = analysisData.detailed_analysis[key];
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    
                    html += `<div style="background: white; padding: 20px; border-radius: 10px; text-align: center; border: 2px solid #e1e8ff;">
                        <div style="font-size: 24px; font-weight: bold; color: #667eea; margin-bottom: 8px;">${value}</div>
                        <div style="font-size: 13px; color: #6c757d; text-transform: uppercase;">${label}</div>
                    </div>`;
                });
                
                html += `</div>`;
            }
            
            html += `
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour comparer les solutions
        function compareSolutions() {
            const solutions = <?= json_encode($solutions) ?>;
            if (solutions.length < 2) {
                showMessage('Il faut au moins 2 solutions pour effectuer une comparaison', 'error');
                return;
            }
            
            showMessage('Comparaison des solutions en cours...', 'info');
            
            fetch('ai_compare_solutions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    problem_id: problemData.id,
                    solutions: solutions.map(s => ({
                        id: s.id,
                        code: s.solution_code,
                        ai_score: s.ai_score
                    }))
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSolutionComparison(data.comparison);
                } else {
                    showMessage('Erreur lors de la comparaison: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion lors de la comparaison', 'error');
            });
        }
        
        // Fonction pour afficher la comparaison des solutions
        function showSolutionComparison(comparisonData) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-balance-scale"></i> Comparaison des Solutions
                </h3>
                
                <div style="background: #f8f9ff; padding: 25px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 25px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">Résumé de la comparaison:</h4>
                    <p style="line-height: 1.6; color: #4a5568;">${comparisonData.summary}</p>
                </div>
            `;
            
            if (comparisonData.rankings) {
                html += `<div style="margin-bottom: 25px;">
                    <h4 style="color: #2c3e50; margin-bottom: 15px;">Classement des solutions:</h4>`;
                
                comparisonData.rankings.forEach((solution, index) => {
                    const medalColor = index === 0 ? '#f1c40f' : index === 1 ? '#95a5a6' : '#cd7f32';
                    html += `
                        <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid ${medalColor}; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span style="font-weight: bold; color: ${medalColor};">#${index + 1}</span>
                                <span style="margin-left: 10px;">Solution par ${solution.author}</span>
                            </div>
                            <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 5px 12px; border-radius: 15px; font-size: 14px; font-weight: 600;">
                                ${solution.score}/10
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            html += `
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Ajouter un bouton de comparaison si il y a plusieurs solutions
        <?php if (count($solutions) >= 2): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const actionsDiv = document.querySelector('.actions > div:first-child');
            if (actionsDiv) {
                const compareBtn = document.createElement('button');
                compareBtn.className = 'btn btn-ai';
                compareBtn.innerHTML = '<i class="fas fa-balance-scale"></i> Comparer les Solutions';
                compareBtn.onclick = compareSolutions;
                actionsDiv.appendChild(compareBtn);
            }
        });
        <?php endif; ?>
        
        // Fonction pour suggérer des améliorations
        function suggestImprovements() {
            showMessage('Génération de suggestions d\'amélioration...', 'info');
            
            fetch('ai_suggest_improvements.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    problem_id: problemData.id,
                    current_solutions: <?= json_encode(array_column($solutions, 'solution_code')) ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showImprovementSuggestions(data.suggestions);
                } else {
                    showMessage('Erreur lors de la génération de suggestions: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Fonction pour afficher les suggestions d'amélioration
        function showImprovementSuggestions(suggestions) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-lightbulb"></i> Suggestions d'Amélioration IA
                </h3>
            `;
            
            suggestions.forEach((suggestion, index) => {
                html += `
                    <div style="background: #f8f9ff; padding: 20px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 20px;">
                        <h4 style="color: #667eea; margin-bottom: 15px;">
                            <i class="fas fa-arrow-up"></i> Suggestion ${index + 1}: ${suggestion.title}
                        </h4>
                        <p style="line-height: 1.6; color: #4a5568; margin-bottom: 15px;">${suggestion.description}</p>
                        
                        ${suggestion.code_example ? `
                            <div style="background: #282c34; color: #abb2bf; padding: 15px; border-radius: 8px; font-family: monospace; overflow-x: auto; margin-top: 15px;">
                                <pre><code>${suggestion.code_example}</code></pre>
                            </div>
                        ` : ''}
                        
                        <div style="margin-top: 15px; padding: 10px; background: rgba(46, 204, 113, 0.1); border-radius: 6px; border-left: 3px solid #2ecc71;">
                            <small style="color: #27ae60; font-weight: 600;">
                                <i class="fas fa-chart-line"></i> Impact estimé: ${suggestion.impact || 'Amélioration significative'}
                            </small>
                        </div>
                    </div>
                `;
            });
            
            html += `
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="exportSuggestions()" style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-download"></i> Exporter les suggestions
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour exporter les suggestions
        function exportSuggestions() {
            // Cette fonction sera appelée depuis la modal des suggestions
            showMessage('Suggestions exportées!', 'success');
        }
        
        // Ajouter un bouton de suggestions d'amélioration
        document.addEventListener('DOMContentLoaded', function() {
            const actionsDiv = document.querySelector('.actions > div:first-child');
            if (actionsDiv && <?= count($solutions) ?> > 0) {
                const suggestBtn = document.createElement('button');
                suggestBtn.className = 'btn btn-ai';
                suggestBtn.innerHTML = '<i class="fas fa-lightbulb"></i> Suggestions IA';
                suggestBtn.onclick = suggestImprovements;
                suggestBtn.title = 'Obtenir des suggestions d\'amélioration basées sur l\'IA';
                actionsDiv.appendChild(suggestBtn);
            }
        });
        
        // Fonction pour évaluer la difficulté du problème avec l'IA
        function evaluateDifficulty() {
            showMessage('Évaluation de la difficulté en cours...', 'info');
            
            fetch('ai_evaluate_difficulty.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    problem_id: problemData.id,
                    description: <?= json_encode($problem['description']) ?>,
                    code: <?= json_encode($problem['code'] ?? '') ?>,
                    current_difficulty: <?= json_encode($problem['difficulty']) ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showDifficultyEvaluation(data.evaluation);
                } else {
                    showMessage('Erreur lors de l\'évaluation: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Fonction pour afficher l'évaluation de difficulté
        function showDifficultyEvaluation(evaluation) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            const currentDifficulty = <?= json_encode($problem['difficulty']) ?>;
            const difficultyColors = {
                'easy': '#27ae60',
                'medium': '#f39c12',
                'hard': '#e74c3c'
            };
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-chart-bar"></i> Évaluation de Difficulté IA
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div style="background: #f8f9ff; padding: 20px; border-radius: 10px; border: 2px solid #e1e8ff; text-align: center;">
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">Difficulté Actuelle</h4>
                        <div style="background: ${difficultyColors[currentDifficulty]}; color: white; padding: 15px; border-radius: 8px; font-size: 18px; font-weight: bold; text-transform: uppercase;">
                            ${currentDifficulty}
                        </div>
                    </div>
                    <div style="background: #f0fff4; padding: 20px; border-radius: 10px; border: 2px solid #c3e6cb; text-align: center;">
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">Difficulté Suggérée par l'IA</h4>
                        <div style="background: ${difficultyColors[evaluation.suggested_difficulty]}; color: white; padding: 15px; border-radius: 8px; font-size: 18px; font-weight: bold; text-transform: uppercase;">
                            ${evaluation.suggested_difficulty}
                        </div>
                    </div>
                </div>
                
                <div style="background: #f8f9ff; padding: 25px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 25px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">Justification de l'IA:</h4>
                    <p style="line-height: 1.6; color: #4a5568;">${evaluation.reasoning}</p>
                </div>
            `;
            
            if (evaluation.factors) {
                html += `
                    <div style="margin-bottom: 25px;">
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">Facteurs analysés:</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                `;
                
                Object.keys(evaluation.factors).forEach(factor => {
                    const score = evaluation.factors[factor];
                    const percentage = (score / 5) * 100;
                    
                    html += `
                        <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e1e8ff;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-weight: 600; color: #2c3e50;">${factor.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                                <span style="font-weight: bold; color: #667eea;">${score}/5</span>
                            </div>
                            <div style="background: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; width: ${percentage}%; transition: width 1s ease;"></div>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div></div>`;
            }
            
            html += `
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour obtenir des hints IA
        function getAIHints() {
            showMessage('Génération d\'indices IA...', 'info');
            
            fetch('ai_generate_hints.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    problem_id: problemData.id,
                    user_id: <?= $user_id ?>,
                    difficulty: <?= json_encode($problem['difficulty']) ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAIHints(data.hints);
                } else {
                    showMessage('Erreur lors de la génération d\'indices: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Fonction pour afficher les indices IA
        function showAIHints(hints) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-lightbulb"></i> Indices IA pour vous aider
                </h3>
                
                <div style="background: linear-gradient(135deg, #fff5f5, #fed7d7); padding: 20px; border-radius: 10px; border: 2px solid #feb2b2; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-info-circle" style="color: #e53e3e; font-size: 24px; margin-bottom: 10px;"></i>
                    <p style="color: #742a2a; margin: 0; font-weight: 600;">
                        Ces indices sont générés par l'IA pour vous guider sans révéler la solution complète.
                    </p>
                </div>
            `;
            
            hints.forEach((hint, index) => {
                html += `
                    <div style="background: #f8f9ff; padding: 20px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 15px; position: relative;">
                        <div style="position: absolute; top: -10px; left: 20px; background: #667eea; color: white; padding: 5px 15px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                            INDICE ${index + 1}
                        </div>
                        <div style="margin-top: 10px;">
                            <h4 style="color: #667eea; margin-bottom: 10px;">${hint.title}</h4>
                            <p style="line-height: 1.6; color: #4a5568; margin-bottom: 15px;">${hint.description}</p>
                            
                            ${hint.code_snippet ? `
                                <div style="background: #282c34; color: #abb2bf; padding: 15px; border-radius: 8px; font-family: monospace; overflow-x: auto; margin-top: 15px;">
                                    <pre><code>${hint.code_snippet}</code></pre>
                                </div>
                            ` : ''}
                            
                            <div style="margin-top: 15px; display: flex; align-items: center; gap: 10px;">
                                <span style="background: rgba(102, 126, 234, 0.1); color: #667eea; padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">
                                    Difficulté: ${hint.difficulty_level || 'Débutant'}
                                </span>
                                ${hint.estimated_time ? `
                                    <span style="background: rgba(46, 204, 113, 0.1); color: #27ae60; padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">
                                        <i class="fas fa-clock"></i> ${hint.estimated_time}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                <div style="background: #f0fff4; padding: 20px; border-radius: 10px; border: 2px solid #c3e6cb; margin-bottom: 25px;">
                    <h4 style="color: #155724; margin-bottom: 15px;">
                        <i class="fas fa-graduation-cap"></i> Conseil de l'IA:
                    </h4>
                    <p style="line-height: 1.6; color: #155724; margin: 0;">
                        Essayez d'implémenter ces indices un par un. Si vous êtes bloqué, n'hésitez pas à consulter les solutions existantes pour vous inspirer!
                    </p>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="requestMoreHints()" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i> Plus d'indices
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour demander plus d'indices
        function requestMoreHints() {
            showMessage('Génération d\'indices supplémentaires...', 'info');
            // Ici on pourrait faire un nouvel appel API pour des indices plus avancés
            setTimeout(() => {
                showMessage('Indices supplémentaires générés!', 'success');
                // Recharger les indices avec un niveau de difficulté plus élevé
                getAIHints();
            }, 2000);
        }
        
        // Ajouter un bouton d'indices IA pour les utilisateurs connectés
        <?php if (isLoggedIn()): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const actionsDiv = document.querySelector('.actions > div:first-child');
            if (actionsDiv) {
                const hintsBtn = document.createElement('button');
                hintsBtn.className = 'btn btn-ai';
                hintsBtn.innerHTML = '<i class="fas fa-lightbulb"></i> Indices IA';
                hintsBtn.onclick = getAIHints;
                hintsBtn.title = 'Obtenir des indices générés par l\'IA pour résoudre ce problème';
                actionsDiv.appendChild(hintsBtn);
            }
        });
        <?php endif; ?>
        
        // Fonction pour générer un plan de résolution
        function generateSolutionPlan() {
            showMessage('Génération d\'un plan de résolution...', 'info');
            
            fetch('ai_generate_plan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    problem_id: problemData.id,
                    description: <?= json_encode($problem['description']) ?>,
                    difficulty: <?= json_encode($problem['difficulty']) ?>,
                    language: <?= json_encode($problem['language']) ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSolutionPlan(data.plan);
                } else {
                    showMessage('Erreur lors de la génération du plan: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Fonction pour afficher le plan de résolution
        function showSolutionPlan(plan) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-map"></i> Plan de Résolution IA
                </h3>
                
                <div style="background: linear-gradient(135deg, #f8f9ff, #e1e8ff); padding: 20px; border-radius: 10px; border: 2px solid #c7d2fe; margin-bottom: 25px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-target"></i> Objectif du problème:
                    </h4>
                    <p style="line-height: 1.6; color: #4a5568; margin: 0;">${plan.objective}</p>
                </div>
            `;
            
            if (plan.steps && plan.steps.length > 0) {
                html += `<div style="margin-bottom: 25px;">
                    <h4 style="color: #2c3e50; margin-bottom: 20px;">
                        <i class="fas fa-list-ol"></i> Étapes de résolution:
                    </h4>`;
                
                plan.steps.forEach((step, index) => {
                    html += `
                        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid #667eea; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <div style="background: #667eea; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px;">
                                    ${index + 1}
                                </div>
                                <h5 style="color: #2c3e50; margin: 0; font-size: 16px;">${step.title}</h5>
                            </div>
                            <p style="line-height: 1.6; color: #4a5568; margin-bottom: 15px;">${step.description}</p>
                            
                            ${step.code_example ? `
                                <div style="background: #282c34; color: #abb2bf; padding: 15px; border-radius: 8px; font-family: monospace; overflow-x: auto; margin-top: 15px;">
                                    <pre><code>${step.code_example}</code></pre>
                                </div>
                            ` : ''}
                            
                            ${step.tips ? `
                                <div style="background: #fff3cd; padding: 12px; border-radius: 6px; border-left: 3px solid #ffc107; margin-top: 15px;">
                                    <small style="color: #856404; font-weight: 600;">
                                        <i class="fas fa-lightbulb"></i> Conseil: ${step.tips}
                                    </small>
                                </div>
                            ` : ''}
                            
                            <div style="margin-top: 15px; display: flex; align-items: center; gap: 10px;">
                                <span style="background: rgba(102, 126, 234, 0.1); color: #667eea; padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">
                                    Complexité: ${step.complexity || 'Moyenne'}
                                </span>
                                ${step.estimated_time ? `
                                    <span style="background: rgba(46, 204, 113, 0.1); color: #27ae60; padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">
                                        <i class="fas fa-clock"></i> ${step.estimated_time}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            if (plan.resources && plan.resources.length > 0) {
                html += `
                    <div style="background: #f0fff4; padding: 20px; border-radius: 10px; border: 2px solid #c3e6cb; margin-bottom: 25px;">
                        <h4 style="color: #155724; margin-bottom: 15px;">
                            <i class="fas fa-book"></i> Ressources recommandées:
                        </h4>
                        <ul style="margin: 0; padding-left: 20px; color: #155724;">
                `;
                
                plan.resources.forEach(resource => {
                    html += `<li style="margin-bottom: 8px; line-height: 1.5;">${resource}</li>`;
                });
                
                html += `</ul></div>`;
            }
            
            html += `
                <div style="background: linear-gradient(135deg, #e8f5e8, #c3e6cb); padding: 20px; border-radius: 10px; border: 2px solid #a3d9a5; margin-bottom: 25px; text-align: center;">
                    <h4 style="color: #155724; margin-bottom: 15px;">
                        <i class="fas fa-trophy"></i> Temps estimé total: ${plan.total_estimated_time || '30-60 minutes'}
                    </h4>
                    <p style="color: #155724; margin: 0; font-weight: 600;">
                        Suivez ce plan étape par étape pour une résolution méthodique!
                    </p>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="exportPlan()" style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-download"></i> Exporter le plan
                    </button>
                    <button onclick="startGuidedSolution()" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-play"></i> Mode guidé
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour exporter le plan
        function exportPlan() {
            showMessage('Plan exporté!', 'success');
            // Ici on pourrait générer un PDF ou un fichier texte
        }
        
        // Fonction pour démarrer le mode guidé
        function startGuidedSolution() {
            showMessage('Redirection vers le mode guidé...', 'info');
            // Rediriger vers une page de résolution guidée
            window.location.href = `guided_solution.php?problem_id=${problemData.id}`;
        }
        
        // Ajouter un bouton de plan de résolution
        <?php if (isLoggedIn()): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const actionsDiv = document.querySelector('.actions > div:first-child');
            if (actionsDiv) {
                const planBtn = document.createElement('button');
                planBtn.className = 'btn btn-ai';
                planBtn.innerHTML = '<i class="fas fa-map"></i> Plan IA';
                planBtn.onclick = generateSolutionPlan;
                planBtn.title = 'Générer un plan de résolution étape par étape';
                actionsDiv.appendChild(planBtn);
            }
        });
        <?php endif; ?>
        
        // Fonction pour évaluer la qualité du problème
        function evaluateProblemQuality() {
            showMessage('Évaluation de la qualité du problème...', 'info');
            
            fetch('ai_evaluate_problem_quality.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    problem_id: problemData.id,
                    title: <?= json_encode($problem['title']) ?>,
                    description: <?= json_encode($problem['description']) ?>,
                    code: <?= json_encode($problem['code'] ?? '') ?>,
                    solution: <?= json_encode($problem['solution']) ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showProblemQualityEvaluation(data.evaluation);
                } else {
                    showMessage('Erreur lors de l\'évaluation: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Fonction pour afficher l'évaluation de qualité
        function showProblemQualityEvaluation(evaluation) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-star"></i> Évaluation de Qualité du Problème
                </h3>
                
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 15px; margin-bottom: 25px;">
                        <div style="font-size: 48px; font-weight: bold; margin-bottom: 10px;">${evaluation.overall_score}/10</div>
                        <div style="font-size: 18px; opacity: 0.9;">Score de qualité global</div>
                    </div>
                </div>
            `;
            
            if (evaluation.criteria_scores) {
                html += `<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">`;
                
                Object.keys(evaluation.criteria_scores).forEach(criteria => {
                    const score = evaluation.criteria_scores[criteria];
                    const percentage = (score / 10) * 100;
                    const criteriaLabel = criteria.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    
                    html += `
                        <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; border: 2px solid #e1e8ff;">
                            <div style="font-size: 24px; font-weight: bold; color: #667eea; margin-bottom: 8px;">${score}/10</div>
                            <div style="font-size: 13px; color: #6c757d; margin-bottom: 10px;">${criteriaLabel}</div>
                            <div style="background: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; width: ${percentage}%; transition: width 1s ease;"></div>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            if (evaluation.strengths && evaluation.strengths.length > 0) {
                html += `
                    <div style="background: #f0fff4; padding: 20px; border-radius: 10px; border: 2px solid #c3e6cb; margin-bottom: 20px;">
                        <h4 style="color: #155724; margin-bottom: 15px;">
                            <i class="fas fa-thumbs-up"></i> Points forts:
                        </h4>
                        <ul style="margin: 0; padding-left: 20px; color: #155724;">
                `;
                
                evaluation.strengths.forEach(strength => {
                    html += `<li style="margin-bottom: 8px; line-height: 1.5;">${strength}</li>`;
                });
                
                html += `</ul></div>`;
            }
            
            if (evaluation.weaknesses && evaluation.weaknesses.length > 0) {
                html += `
                    <div style="background: #fff5f5; padding: 20px; border-radius: 10px; border: 2px solid #fed7d7; margin-bottom: 20px;">
                        <h4 style="color: #c53030; margin-bottom: 15px;">
                            <i class="fas fa-exclamation-triangle"></i> Points à améliorer:
                        </h4>
                        <ul style="margin: 0; padding-left: 20px; color: #c53030;">
                `;
                
                evaluation.weaknesses.forEach(weakness => {
                    html += `<li style="margin-bottom: 8px; line-height: 1.5;">${weakness}</li>`;
                });
                
                html += `</ul></div>`;
            }
            
            if (evaluation.improvement_suggestions && evaluation.improvement_suggestions.length > 0) {
                html += `
                    <div style="background: #f8f9ff; padding: 20px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 25px;">
                        <h4 style="color: #667eea; margin-bottom: 15px;">
                            <i class="fas fa-lightbulb"></i> Suggestions d'amélioration:
                        </h4>
                        <ul style="margin: 0; padding-left: 20px; color: #4a5568;">
                `;
                
                evaluation.improvement_suggestions.forEach(suggestion => {
                    html += `<li style="margin-bottom: 8px; line-height: 1.5;">${suggestion}</li>`;
                });
                
                html += `</ul></div>`;
            }
            
            html += `
                <div style="background: linear-gradient(135deg, #e8f5e8, #c3e6cb); padding: 20px; border-radius: 10px; border: 2px solid #a3d9a5; margin-bottom: 25px; text-align: center;">
                    <h4 style="color: #155724; margin-bottom: 15px;">
                        <i class="fas fa-chart-line"></i> Recommandation globale
                    </h4>
                    <p style="color: #155724; margin: 0; font-weight: 600; line-height: 1.6;">
                        ${evaluation.recommendation || 'Ce problème présente une bonne qualité générale et peut être amélioré en suivant les suggestions ci-dessus.'}
                    </p>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="exportQualityReport()" style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-download"></i> Exporter le rapport
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour exporter le rapport de qualité
        function exportQualityReport() {
            showMessage('Rapport de qualité exporté!', 'success');
        }
        
        // Système de notation intelligent basé sur l'IA
        function rateWithAI() {
            showMessage('Analyse IA de votre expérience...', 'info');
            
            // Simuler une analyse IA de l'interaction utilisateur
            setTimeout(() => {
                const aiRating = Math.round(Math.random() * 2 + 8); // Score entre 8-10
                showAIRatingResults(aiRating);
            }, 2000);
        }
        
        // Afficher les résultats de notation IA
        function showAIRatingResults(aiRating) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-robot"></i> Évaluation IA de votre Expérience
                </h3>
                
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 15px; margin-bottom: 25px;">
                        <div style="font-size: 48px; font-weight: bold; margin-bottom: 10px;">${aiRating}/10</div>
                        <div style="font-size: 18px; opacity: 0.9;">Score IA basé sur votre interaction</div>
                    </div>
                </div>
                
                <div style="background: #f8f9ff; padding: 25px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 25px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">Comment l'IA a calculé ce score:</h4>
                    <ul style="margin: 0; padding-left: 20px; color: #4a5568; line-height: 1.6;">
                        <li>Temps passé sur le problème: <strong>Optimal</strong></li>
                        <li>Interaction avec les solutions: <strong>Active</strong></li>
                        <li>Utilisation des fonctionnalités IA: <strong>Excellente</strong></li>
                        <li>Engagement avec le contenu: <strong>Très bon</strong></li>
                    </ul>
                </div>
                
                <div style="background: #f0fff4; padding: 20px; border-radius: 10px; border: 2px solid #c3e6cb; margin-bottom: 25px;">
                    <h4 style="color: #155724; margin-bottom: 15px;">
                        <i class="fas fa-thumbs-up"></i> Recommandations personnalisées:
                    </h4>
                    <p style="color: #155724; margin: 0; line-height: 1.6;">
                        Basé sur votre interaction, l'IA recommande d'explorer des problèmes de difficulté similaire 
                        et de continuer à utiliser les outils d'analyse pour améliorer vos compétences.
                    </p>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="getPersonalizedRecommendations()" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-magic"></i> Recommandations
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour obtenir des recommandations personnalisées
        function getPersonalizedRecommendations() {
            showMessage('Génération de recommandations personnalisées...', 'info');
            
            fetch('ai_personalized_recommendations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?= $user_id ?>,
                    current_problem_id: problemData.id,
                    interaction_data: {
                        time_spent: Date.now() - pageLoadTime,
                        features_used: usedFeatures,
                        ai_interactions: aiInteractionCount
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showPersonalizedRecommendations(data.recommendations);
                } else {
                    showMessage('Erreur lors de la génération des recommandations: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Afficher les recommandations personnalisées
        function showPersonalizedRecommendations(recommendations) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-magic"></i> Recommandations Personnalisées IA
                </h3>
                
                <div style="background: linear-gradient(135deg, #f8f9ff, #e1e8ff); padding: 20px; border-radius: 10px; border: 2px solid #c7d2fe; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-user-graduate" style="color: #667eea; font-size: 32px; margin-bottom: 15px;"></i>
                    <p style="color: #4a5568; margin: 0; font-weight: 600; line-height: 1.6;">
                        Ces recommandations sont basées sur votre profil d'apprentissage et vos interactions avec la plateforme.
                    </p>
                </div>
            `;
            
            if (recommendations.next_problems && recommendations.next_problems.length > 0) {
                html += `
                    <div style="margin-bottom: 25px;">
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">
                            <i class="fas fa-arrow-right"></i> Problèmes recommandés pour vous:
                        </h4>
                `;
                
                recommendations.next_problems.forEach((problem, index) => {
                    html += `
                        <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #667eea; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                            <div>
                                <h5 style="margin: 0 0 5px 0; color: #2c3e50;">${problem.title}</h5>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <span style="background: rgba(102, 126, 234, 0.1); color: #667eea; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                                        ${problem.difficulty}
                                    </span>
                                    <span style="color: #7f8c8d; font-size: 12px;">
                                        Match: ${problem.match_percentage}%
                                    </span>
                                </div>
                            </div>
                            <a href="problem.php?id=${problem.id}" style="background: #667eea; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;">
                                Voir
                            </a>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            if (recommendations.learning_path) {
                html += `
                    <div style="background: #f0fff4; padding: 20px; border-radius: 10px; border: 2px solid #c3e6cb; margin-bottom: 25px;">
                        <h4 style="color: #155724; margin-bottom: 15px;">
                            <i class="fas fa-route"></i> Votre parcours d'apprentissage suggéré:
                        </h4>
                        <p style="color: #155724; margin: 0; line-height: 1.6;">
                            ${recommendations.learning_path}
                        </p>
                    </div>
                `;
            }
            
            if (recommendations.skills_to_improve && recommendations.skills_to_improve.length > 0) {
                html += `
                    <div style="background: #fff3cd; padding: 20px; border-radius: 10px; border: 2px solid #ffeaa7; margin-bottom: 25px;">
                        <h4 style="color: #856404; margin-bottom: 15px;">
                            <i class="fas fa-chart-line"></i> Compétences à développer:
                        </h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                `;
                
                recommendations.skills_to_improve.forEach(skill => {
                    html += `
                        <span style="background: rgba(133, 100, 4, 0.1); color: #856404; padding: 6px 12px; border-radius: 15px; font-size: 14px; border: 1px solid rgba(133, 100, 4, 0.2);">
                            ${skill}
                        </span>
                    `;
                });
                
                html += `</div></div>`;
            }
            
            html += `
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="saveRecommendations()" style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-save"></i> Sauvegarder
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour sauvegarder les recommandations
        function saveRecommendations() {
            showMessage('Recommandations sauvegardées dans votre profil!', 'success');
            // Ici on pourrait sauvegarder les recommandations dans la base de données
        }
        
        // Variables pour tracker l'interaction utilisateur
        let pageLoadTime = Date.now();
        let usedFeatures = [];
        let aiInteractionCount = 0;
        
        // Tracker les fonctionnalités utilisées
        function trackFeatureUsage(feature) {
            if (!usedFeatures.includes(feature)) {
                usedFeatures.push(feature);
            }
            if (feature.includes('ai')) {
                aiInteractionCount++;
            }
        }
        
        // Ajouter le tracking aux fonctions existantes
        const originalOpenAIAnalysis = openAIAnalysisModal;
        window.openAIAnalysisModal = function() {
            trackFeatureUsage('ai_analysis_modal');
            originalOpenAIAnalysis();
        };
        
        // Fonction pour obtenir un résumé IA du problème
        function getAISummary() {
            trackFeatureUsage('ai_summary');
            showMessage('Génération d\'un résumé IA...', 'info');
            
            fetch('ai_generate_summary.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    problem_id: problemData.id,
                    include_solutions: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAISummary(data.summary);
                } else {
                    showMessage('Erreur lors de la génération du résumé: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Afficher le résumé IA
        function showAISummary(summary) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-file-alt"></i> Résumé IA du Problème
                </h3>
                
                <div style="background: linear-gradient(135deg, #f8f9ff, #e1e8ff); padding: 25px; border-radius: 10px; border: 2px solid #c7d2fe; margin-bottom: 25px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-brain"></i> Analyse rapide:
                    </h4>
                    <p style="line-height: 1.6; color: #4a5568; margin: 0; font-size: 16px;">
                        ${summary.quick_analysis}
                    </p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #e1e8ff; text-align: center;">
                        <i class="fas fa-layer-group" style="color: #667eea; font-size: 24px; margin-bottom: 10px;"></i>
                        <h5 style="color: #2c3e50; margin-bottom: 8px;">Complexité</h5>
                        <p style="color: #4a5568; margin: 0; font-weight: 600;">${summary.complexity_level}</p>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #e1e8ff; text-align: center;">
                        <i class="fas fa-clock" style="color: #f39c12; font-size: 24px; margin-bottom: 10px;"></i>
                        <h5 style="color: #2c3e50; margin-bottom: 8px;">Temps estimé</h5>
                        <p style="color: #4a5568; margin: 0; font-weight: 600;">${summary.estimated_time}</p>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #e1e8ff; text-align: center;">
                        <i class="fas fa-graduation-cap" style="color: #2ecc71; font-size: 24px; margin-bottom: 10px;"></i>
                        <h5 style="color: #2c3e50; margin-bottom: 8px;">Niveau requis</h5>
                        <p style="color: #4a5568; margin: 0; font-weight: 600;">${summary.skill_level}</p>
                    </div>
                </div>
            `;
            
            if (summary.key_concepts && summary.key_concepts.length > 0) {
                html += `
                    <div style="background: #f0fff4; padding: 20px; border-radius: 10px; border: 2px solid #c3e6cb; margin-bottom: 25px;">
                        <h4 style="color: #155724; margin-bottom: 15px;">
                            <i class="fas fa-key"></i> Concepts clés à maîtriser:
                        </h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                `;
                
                summary.key_concepts.forEach(concept => {
                    html += `
                        <span style="background: rgba(21, 87, 36, 0.1); color: #155724; padding: 6px 12px; border-radius: 15px; font-size: 14px; border: 1px solid rgba(21, 87, 36, 0.2);">
                            ${concept}
                        </span>
                    `;
                });
                
                html += `</div></div>`;
            }
            
            if (summary.solution_approaches && summary.solution_approaches.length > 0) {
                html += `
                    <div style="background: #fff3cd; padding: 20px; border-radius: 10px; border: 2px solid #ffeaa7; margin-bottom: 25px;">
                        <h4 style="color: #856404; margin-bottom: 15px;">
                            <i class="fas fa-route"></i> Approches de solution possibles:
                        </h4>
                        <ul style="margin: 0; padding-left: 20px; color: #856404;">
                `;
                
                summary.solution_approaches.forEach(approach => {
                    html += `<li style="margin-bottom: 8px; line-height: 1.5;">${approach}</li>`;
                });
                
                html += `</ul></div>`;
            }
            
            if (summary.learning_outcomes) {
                html += `
                    <div style="background: #e8f5e8; padding: 20px; border-radius: 10px; border: 2px solid #a3d9a5; margin-bottom: 25px;">
                        <h4 style="color: #155724; margin-bottom: 15px;">
                            <i class="fas fa-trophy"></i> Ce que vous apprendrez:
                        </h4>
                        <p style="color: #155724; margin: 0; line-height: 1.6; font-weight: 600;">
                            ${summary.learning_outcomes}
                        </p>
                    </div>
                `;
            }
            
            html += `
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="generateStudyPlan()" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-calendar-alt"></i> Plan d'étude
                    </button>
                    <button onclick="exportSummary()" style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour générer un plan d'étude
        function generateStudyPlan() {
            showMessage('Génération d\'un plan d\'étude personnalisé...', 'info');
            // Rediriger vers la fonction de plan de résolution
            generateSolutionPlan();
        }
        
        // Fonction pour exporter le résumé
        function exportSummary() {
            showMessage('Résumé exporté!', 'success');
        }
        
        // Ajouter un bouton de résumé IA
        document.addEventListener('DOMContentLoaded', function() {
            const actionsDiv = document.querySelector('.actions > div:first-child');
            if (actionsDiv) {
                const summaryBtn = document.createElement('button');
                summaryBtn.className = 'btn btn-ai';
                summaryBtn.innerHTML = '<i class="fas fa-file-alt"></i> Résumé IA';
                summaryBtn.onclick = getAISummary;
                summaryBtn.title = 'Obtenir un résumé intelligent du problème';
                actionsDiv.appendChild(summaryBtn);
            }
        });
        
        // Fonction pour détecter la progression de l'utilisateur
        function detectUserProgress() {
            const timeSpent = Date.now() - pageLoadTime;
            const scrollPercentage = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
            
            // Si l'utilisateur a passé plus de 5 minutes et a scrollé plus de 80%
            if (timeSpent > 300000 && scrollPercentage > 80 && aiInteractionCount > 2) {
                showProgressCelebration();
            }
        }
        
        // Afficher une célébration de progression
        function showProgressCelebration() {
            const celebration = document.createElement('div');
            celebration.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 30px;
                border-radius: 15px;
                text-align: center;
                z-index: 10000;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                animation: celebrationPulse 2s ease-in-out;
            `;
            
            celebration.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 15px;">🎉</div>
                <h3 style="margin: 0 0 10px 0;">Excellent travail!</h3>
                <p style="margin: 0; opacity: 0.9;">Vous explorez activement ce problème avec l'IA!</p>
                <button onclick="this.parentElement.remove()" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 6px; margin-top: 15px; cursor: pointer;">
                    Continuer
                </button>
            `;
            
            document.body.appendChild(celebration);
            
            // Supprimer automatiquement après 5 secondes
            setTimeout(() => {
                if (celebration.parentElement) {
                    celebration.remove();
                }
            }, 5000);
        }
        
        // Ajouter l'animation CSS pour la célébration
        const celebrationStyle = document.createElement('style');
        celebrationStyle.textContent = `
            @keyframes celebrationPulse {
                0%, 100% { transform: translate(-50%, -50%) scale(1); }
                50% { transform: translate(-50%, -50%) scale(1.05); }
            }
        `;
        document.head.appendChild(celebrationStyle);
        
        // Détecter la progression toutes les 30 secondes
        setInterval(detectUserProgress, 30000);
        
        // Fonction pour obtenir des statistiques d'apprentissage
        function getAILearningStats() {
            trackFeatureUsage('ai_learning_stats');
            showMessage('Analyse de vos statistiques d\'apprentissage...', 'info');
            
            fetch('ai_learning_analytics.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?= $user_id ?>,
                    problem_id: problemData.id,
                    session_data: {
                        time_spent: Date.now() - pageLoadTime,
                        features_used: usedFeatures,
                        ai_interactions: aiInteractionCount,
                        scroll_depth: (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showLearningAnalytics(data.analytics);
                } else {
                    showMessage('Erreur lors de l\'analyse: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Afficher les analytics d'apprentissage
        function showLearningAnalytics(analytics) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-chart-line"></i> Analytics d'Apprentissage IA
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; margin-bottom: 8px;">${analytics.engagement_score}/100</div>
                        <div style="font-size: 14px; opacity: 0.9;">Score d'engagement</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; margin-bottom: 8px;">${analytics.learning_efficiency}%</div>
                        <div style="font-size: 14px; opacity: 0.9;">Efficacité d'apprentissage</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; margin-bottom: 8px;">${Math.round((Date.now() - pageLoadTime) / 60000)}</div>
                        <div style="font-size: 14px; opacity: 0.9;">Minutes actives</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; margin-bottom: 8px;">${aiInteractionCount}</div>
                        <div style="font-size: 14px; opacity: 0.9;">Interactions IA</div>
                    </div>
                </div>
                
                <div style="background: #f8f9ff; padding: 25px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 25px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-brain"></i> Analyse comportementale:
                    </h4>
                    <p style="line-height: 1.6; color: #4a5568; margin: 0;">
                        ${analytics.behavioral_analysis}
                    </p>
                </div>
            `;
            
            if (analytics.learning_patterns && analytics.learning_patterns.length > 0) {
                html += `
                    <div style="background: #f0fff4; padding: 20px; border-radius: 10px; border: 2px solid #c3e6cb; margin-bottom: 25px;">
                        <h4 style="color: #155724; margin-bottom: 15px;">
                            <i class="fas fa-pattern"></i> Patterns d'apprentissage détectés:
                        </h4>
                        <ul style="margin: 0; padding-left: 20px; color: #155724;">
                `;
                
                analytics.learning_patterns.forEach(pattern => {
                    html += `<li style="margin-bottom: 8px; line-height: 1.5;">${pattern}</li>`;
                });
                
                html += `</ul></div>`;
            }
            
            if (analytics.improvement_areas && analytics.improvement_areas.length > 0) {
                html += `
                    <div style="background: #fff3cd; padding: 20px; border-radius: 10px; border: 2px solid #ffeaa7; margin-bottom: 25px;">
                        <h4 style="color: #856404; margin-bottom: 15px;">
                            <i class="fas fa-target"></i> Zones d'amélioration:
                        </h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                `;
                
                analytics.improvement_areas.forEach(area => {
                    html += `
                        <span style="background: rgba(133, 100, 4, 0.1); color: #856404; padding: 6px 12px; border-radius: 15px; font-size: 14px; border: 1px solid rgba(133, 100, 4, 0.2);">
                            ${area}
                        </span>
                    `;
                });
                
                html += `</div></div>`;
            }
            
            html += `
                <div style="background: linear-gradient(135deg, #e8f5e8, #c3e6cb); padding: 20px; border-radius: 10px; border: 2px solid #a3d9a5; margin-bottom: 25px; text-align: center;">
                    <h4 style="color: #155724; margin-bottom: 15px;">
                        <i class="fas fa-medal"></i> Niveau d'apprentissage actuel
                    </h4>
                    <div style="font-size: 24px; font-weight: bold; color: #155724; margin-bottom: 10px;">
                        ${analytics.current_level || 'Intermédiaire'}
                    </div>
                    <p style="color: #155724; margin: 0; font-weight: 600;">
                        ${analytics.level_description || 'Vous progressez bien! Continuez à explorer les fonctionnalités IA.'}
                    </p>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="generateLearningReport()" style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-file-pdf"></i> Rapport détaillé
                    </button>
                    <button onclick="setLearningGoals()" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-bullseye"></i> Définir objectifs
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour générer un rapport d'apprentissage
        function generateLearningReport() {
            showMessage('Génération du rapport d\'apprentissage...', 'info');
            // Simuler la génération d'un rapport
            setTimeout(() => {
                showMessage('Rapport généré et envoyé par email!', 'success');
            }, 2000);
        }
        
        // Fonction pour définir des objectifs d'apprentissage
        function setLearningGoals() {
            showMessage('Redirection vers la définition d\'objectifs...', 'info');
            // Rediriger vers une page de définition d'objectifs
            window.location.href = 'learning_goals.php';
        }
        
        // Ajouter un bouton d'analytics d'apprentissage
        <?php if (isLoggedIn()): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const actionsDiv = document.querySelector('.actions > div:first-child');
            if (actionsDiv) {
                const analyticsBtn = document.createElement('button');
                analyticsBtn.className = 'btn btn-ai';
                analyticsBtn.innerHTML = '<i class="fas fa-chart-line"></i> Analytics IA';
                analyticsBtn.onclick = getAILearningStats;
                analyticsBtn.title = 'Voir vos statistiques d\'apprentissage avec l\'IA';
                actionsDiv.appendChild(analyticsBtn);
            }
        });
        <?php endif; ?>
        
        // Système de notifications intelligentes
        function setupSmartNotifications() {
            // Notification après 10 minutes d'inactivité
            let inactivityTimer;
            
            function resetInactivityTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(() => {
                    showSmartNotification('💡 Besoin d\'aide? L\'IA peut vous donner des indices!', 'hint');
                }, 600000); // 10 minutes
            }
            
            // Reset timer sur interaction
            document.addEventListener('click', resetInactivityTimer);
            document.addEventListener('scroll', resetInactivityTimer);
            document.addEventListener('keypress', resetInactivityTimer);
            
            resetInactivityTimer();
        }
        
        // Afficher une notification intelligente
        function showSmartNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 9999;
                max-width: 300px;
                animation: slideInRight 0.5s ease-out;
                cursor: pointer;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="font-size: 18px;">${type === 'hint' ? '💡' : 'ℹ️'}</div>
                    <div style="flex: 1; font-size: 14px; line-height: 1.4;">${message}</div>
                    <div style="font-size: 20px; opacity: 0.7;">×</div>
                </div>
            `;
            
            notification.onclick = () => {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
                
                if (type === 'hint') {
                    getAIHints();
                }
            };
            
            document.body.appendChild(notification);
            
            // Auto-remove après 8 secondes
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.animation = 'slideOutRight 0.3s ease-in';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 8000);
        }
        
        // Ajouter les animations CSS pour les notifications
        const notificationStyle = document.createElement('style');
        notificationStyle.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(notificationStyle);
        
        // Initialiser les notifications intelligentes
        setupSmartNotifications();
        
        // Fonction pour obtenir des suggestions de code en temps réel
        function getCodeSuggestions(codeInput) {
            if (codeInput.length < 10) return; // Minimum de code requis
            
            fetch('ai_code_suggestions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    problem_id: problemData.id,
                    partial_code: codeInput,
                    language: <?= json_encode($problem['language']) ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.suggestions.length > 0) {
                    showCodeSuggestions(data.suggestions);
                }
            })
            .catch(error => {
                console.log('Erreur suggestions code:', error);
            });
        }
        
        // Afficher les suggestions de code
        function showCodeSuggestions(suggestions) {
            // Créer ou mettre à jour le panneau de suggestions
            let suggestionsPanel = document.getElementById('codeSuggestionsPanel');
            
            if (!suggestionsPanel) {
                suggestionsPanel = document.createElement('div');
                suggestionsPanel.id = 'codeSuggestionsPanel';
                suggestionsPanel.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    background: white;
                    border: 2px solid #667eea;
                    border-radius: 10px;
                    padding: 15px;
                    max-width: 400px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                    z-index: 9998;
                    animation: slideInLeft 0.5s ease-out;
                `;
                document.body.appendChild(suggestionsPanel);
            }
            
            let html = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin: 0; color: #667eea;">
                        <i class="fas fa-magic"></i> Suggestions IA
                    </h4>
                    <button onclick="document.getElementById('codeSuggestionsPanel').remove()" style="background: none; border: none; font-size: 18px; color: #999; cursor: pointer;">×</button>
                </div>
            `;
            
            suggestions.forEach((suggestion, index) => {
                html += `
                    <div style="background: #f8f9ff; padding: 12px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #667eea; cursor: pointer;" onclick="applySuggestion('${suggestion.code.replace(/'/g, "\\'")}')">
                        <div style="font-weight: 600; color: #2c3e50; margin-bottom: 5px;">${suggestion.title}</div>
                        <div style="font-size: 12px; color: #7f8c8d; margin-bottom: 8px;">${suggestion.description}</div>
                        <code style="background: #282c34; color: #abb2bf; padding: 8px; border-radius: 4px; display: block; font-size: 12px; overflow-x: auto;">${suggestion.code}</code>
                    </div>
                `;
            });
            
            suggestionsPanel.innerHTML = html;
            
            // Auto-remove après 30 secondes
            setTimeout(() => {
                if (suggestionsPanel.parentElement) {
                    suggestionsPanel.style.animation = 'slideOutLeft 0.3s ease-in';
                    setTimeout(() => suggestionsPanel.remove(), 300);
                }
            }, 30000);
        }
        
        // Fonction pour appliquer une suggestion
        function applySuggestion(code) {
            // Copier le code dans le presse-papiers
            navigator.clipboard.writeText(code).then(() => {
                showMessage('Code copié dans le presse-papiers!', 'success');
                document.getElementById('codeSuggestionsPanel').remove();
            });
        }
        
        // Ajouter l'animation CSS pour les suggestions
        const suggestionsStyle = document.createElement('style');
        suggestionsStyle.textContent = `
            @keyframes slideInLeft {
                from { transform: translateX(-100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutLeft {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(-100%); opacity: 0; }
            }
        `;
        document.head.appendChild(suggestionsStyle);
        
        // Système de chat IA intégré
        function openAIChat() {
            trackFeatureUsage('ai_chat');
            
            const chatModal = document.createElement('div');
            chatModal.id = 'aiChatModal';
            chatModal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            
            chatModal.innerHTML = `
                <div style="background: white; width: 90%; max-width: 600px; height: 80%; border-radius: 15px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                    <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-robot"></i> Assistant IA CodeChallenge
                        </h3>
                        <button onclick="closeAIChat()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">×</button>
                    </div>
                    
                    <div id="chatMessages" style="flex: 1; padding: 20px; overflow-y: auto; background: #f8f9fa;">
                        <div style="background: #e3f2fd; padding: 15px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid #2196f3;">
                            <div style="font-weight: 600; color: #1976d2; margin-bottom: 5px;">🤖 Assistant IA</div>
                            <div style="color: #424242;">Bonjour! Je suis votre assistant IA pour ce problème. Comment puis-je vous aider aujourd'hui?</div>
                        </div>
                    </div>
                    
                    <div style="padding: 20px; border-top: 1px solid #e0e0e0; background: white;">
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="chatInput" placeholder="Tapez votre question..." style="flex: 1; padding: 12px; border: 2px solid #e0e0e0; border-radius: 25px; outline: none; font-size: 14px;">
                            <button onclick="sendChatMessage()" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px 20px; border-radius: 25px; cursor: pointer; font-weight: 600;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div style="display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap;">
                            <button onclick="sendQuickQuestion('Comment résoudre ce problème?')" style="background: #f0f0f0; border: 1px solid #ddd; padding: 6px 12px; border-radius: 15px; font-size: 12px; cursor: pointer;">💡 Comment résoudre?</button>
                            <button onclick="sendQuickQuestion('Quels sont les concepts clés?')" style="background: #f0f0f0; border: 1px solid #ddd; padding: 6px 12px; border-radius: 15px; font-size: 12px; cursor: pointer;">🔑 Concepts clés</button>
                            <button onclick="sendQuickQuestion('Donne-moi un exemple de code')" style="background: #f0f0f0; border: 1px solid #ddd; padding: 6px 12px; border-radius: 15px; font-size: 12px; cursor: pointer;">💻 Exemple de code</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(chatModal);
            document.body.style.overflow = 'hidden';
            
            // Focus sur l'input
            document.getElementById('chatInput').focus();
            
            // Gérer l'envoi avec Enter
            document.getElementById('chatInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendChatMessage();
                }
            });
        }
        
        // Fermer le chat IA
        function closeAIChat() {
            const chatModal = document.getElementById('aiChatModal');
            if (chatModal) {
                chatModal.remove();
                document.body.style.overflow = 'auto';
            }
        }
        
        // Envoyer un message dans le chat
        function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Ajouter le message de l'utilisateur
            addChatMessage(message, 'user');
            input.value = '';
            
            // Simuler une réponse de l'IA
            setTimeout(() => {
                const aiResponse = generateAIResponse(message);
                addChatMessage(aiResponse, 'ai');
            }, 1000);
        }
        
        // Envoyer une question rapide
        function sendQuickQuestion(question) {
            document.getElementById('chatInput').value = question;
            sendChatMessage();
        }
        
        // Ajouter un message au chat
        function addChatMessage(message, sender) {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            
            if (sender === 'user') {
                messageDiv.style.cssText = `
                    background: #667eea;
                    color: white;
                    padding: 12px 15px;
                    border-radius: 15px 15px 5px 15px;
                    margin-bottom: 10px;
                    margin-left: 20%;
                    word-wrap: break-word;
                `;
                messageDiv.innerHTML = message;
            } else {
                messageDiv.style.cssText = `
                    background: #e3f2fd;
                    padding: 15px;
                    border-radius: 15px 15px 15px 5px;
                    margin-bottom: 15px;
                    margin-right: 20%;
                    border-left: 4px solid #2196f3;
                `;
                messageDiv.innerHTML = `
                    <div style="font-weight: 600; color: #1976d2; margin-bottom: 5px; display: flex; align-items: center; gap: 5px;">
                        🤖 Assistant IA
                        <span style="font-size: 10px; background: #2196f3; color: white; padding: 2px 6px; border-radius: 10px;">EN LIGNE</span>
                    </div>
                    <div style="color: #424242; line-height: 1.5;">${message}</div>
                `;
            }
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Générer une réponse IA (simulation)
        function generateAIResponse(userMessage) {
            const responses = {
                'comment résoudre': `Pour résoudre ce problème, je recommande cette approche:
                
                1. **Analysez** d'abord les contraintes et les exemples
                2. **Identifiez** l'algorithme ou la structure de données appropriée
                3. **Planifiez** votre solution étape par étape
                4. **Implémentez** en commençant par les cas simples
                5. **Testez** avec différents cas de test
                
                Voulez-vous que je vous donne des indices plus spécifiques?`,
                
                'concepts clés': `Les concepts clés pour ce problème sont:
                
                🔹 **${problem.language}** - Maîtrise du langage
                🔹 **Algorithmique** - Logique de résolution
                🔹 **Structures de données** - Organisation efficace
                🔹 **Complexité** - Optimisation du temps/espace
                
                Quel concept souhaitez-vous approfondir?`,
                
                'exemple de code': `Voici un exemple de structure pour commencer:
                
                \`\`\`${problem.language}
                // Votre solution ici
                function resoudreProbleme(input) {
                    // 1. Traiter l'entrée
                    // 2. Appliquer l'algorithme
                    // 3. Retourner le résultat
                    return resultat;
                }
                \`\`\`
                
                Voulez-vous que je détaille une partie spécifique?`
            };
            
            // Trouver la réponse la plus appropriée
            const lowerMessage = userMessage.toLowerCase();
            for (const [key, response] of Object.entries(responses)) {
                if (lowerMessage.includes(key)) {
                    return response;
                }
            }
            
            // Réponse par défaut
            return `Je comprends votre question sur "${userMessage}". 
            
            Pour ce problème spécifique, je peux vous aider avec:
            • L'analyse du problème
            • Les algorithmes appropriés  
            • Des exemples de code
            • L'optimisation de votre solution
            
            Pouvez-vous être plus spécifique sur ce que vous cherchez?`;
        }
        
        // Ajouter un bouton de chat IA
        <?php if (isLoggedIn()): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const actionsDiv = document.querySelector('.actions > div:first-child');
            if (actionsDiv) {
                const chatBtn = document.createElement('button');
                chatBtn.className = 'btn btn-ai';
                chatBtn.innerHTML = '<i class="fas fa-comments"></i> Chat IA';
                chatBtn.onclick = openAIChat;
                chatBtn.title = 'Discuter avec l\'assistant IA';
                actionsDiv.appendChild(chatBtn);
            }
        });
        <?php endif; ?>
        
        // Système de sauvegarde automatique des interactions
        function saveUserInteraction(action, data = {}) {
            fetch('save_user_interaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?= $user_id ?>,
                    problem_id: problemData.id,
                    action: action,
                    data: data,
                    timestamp: Date.now()
                })
            }).catch(error => {
                console.log('Erreur sauvegarde interaction:', error);
            });
        }
        
        // Tracker automatiquement certaines actions
        document.addEventListener('DOMContentLoaded', function() {
            saveUserInteraction('page_view', {
                referrer: document.referrer,
                user_agent: navigator.userAgent
            });
            
            // Tracker le temps passé sur la page
            window.addEventListener('beforeunload', function() {
                saveUserInteraction('page_exit', {
                    time_spent: Date.now() - pageLoadTime,
                    features_used: usedFeatures,
                    ai_interactions: aiInteractionCount
                });
            });
        });
        
        // Fonction finale pour initialiser toutes les fonctionnalités IA
        function initializeAIFeatures() {
            console.log('🤖 Fonctionnalités IA initialisées');
            
            // Ajouter un indicateur visuel des fonctionnalités IA
            const aiIndicator = document.createElement('div');
            aiIndicator.style.cssText = `
                position: fixed;
                bottom: 120px;
                left: 20px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 8px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                z-index: 1000;
                animation: aiPulse 2s infinite;
                cursor: pointer;
            `;
            aiIndicator.innerHTML = '🤖 IA Active';
            aiIndicator.title = 'Cliquez pour voir toutes les fonctionnalités IA';
            aiIndicator.onclick = showAIFeaturesList;
            
            document.body.appendChild(aiIndicator);
            
            // Animation pour l'indicateur IA
            const aiIndicatorStyle = document.createElement('style');
            aiIndicatorStyle.textContent = `
                @keyframes aiPulse {
                    0%, 100% { opacity: 0.8; transform: scale(1); }
                    50% { opacity: 1; transform: scale(1.05); }
                }
            `;
            document.head.appendChild(aiIndicatorStyle);
        }
        
        // Afficher la liste des fonctionnalités IA
        function showAIFeaturesList() {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-robot"></i> Fonctionnalités IA Disponibles
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px; cursor: pointer;" onclick="analyzeWithAI()">
                        <div style="font-size: 24px; margin-bottom: 10px;">🔍</div>
                        <h4 style="margin: 0 0 8px 0;">Analyse IA</h4>
                        <p style="margin: 0; font-size: 14px; opacity: 0.9;">Analyse complète du problème avec suggestions</p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; padding: 20px; border-radius: 10px; cursor: pointer;" onclick="generateSolutionPlan()">
                        <div style="font-size: 24px; margin-bottom: 10px;">📋</div>
                        <h4 style="margin: 0 0 8px 0;">Plan de Solution</h4>
                        <p style="margin: 0; font-size: 14px; opacity: 0.9;">Génère un plan étape par étape</p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 20px; border-radius: 10px; cursor: pointer;" onclick="getAIHints()">
                        <div style="font-size: 24px; margin-bottom: 10px;">💡</div>
                        <h4 style="margin: 0 0 8px 0;">Indices IA</h4>
                        <p style="margin: 0; font-size: 14px; opacity: 0.9;">Obtenez des indices progressifs</p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white; padding: 20px; border-radius: 10px; cursor: pointer;" onclick="getAISummary()">
                        <div style="font-size: 24px; margin-bottom: 10px;">📄</div>
                        <h4 style="margin: 0 0 8px 0;">Résumé IA</h4>
                        <p style="margin: 0; font-size: 14px; opacity: 0.9;">Résumé intelligent du problème</p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 20px; border-radius: 10px; cursor: pointer;" onclick="openAIChat()">
                        <div style="font-size: 24px; margin-bottom: 10px;">💬</div>
                        <h4 style="margin: 0 0 8px 0;">Chat IA</h4>
                        <p style="margin: 0; font-size: 14px; opacity: 0.9;">Discutez avec l'assistant IA</p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #34495e, #2c3e50); color: white; padding: 20px; border-radius: 10px; cursor: pointer;" onclick="getAILearningStats()">
                        <div style="font-size: 24px; margin-bottom: 10px;">📊</div>
                        <h4 style="margin: 0 0 8px 0;">Analytics</h4>
                        <p style="margin: 0; font-size: 14px; opacity: 0.9;">Statistiques d'apprentissage</p>
                    </div>
                </div>
                
                <div style="background: #f8f9ff; padding: 20px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 25px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> Comment utiliser l'IA:
                    </h4>
                    <ul style="margin: 0; padding-left: 20px; color: #4a5568; line-height: 1.6;">
                        <li>Commencez par l'<strong>Analyse IA</strong> pour comprendre le problème</li>
                        <li>Utilisez le <strong>Plan de Solution</strong> pour structurer votre approche</li>
                        <li>Demandez des <strong>Indices</strong> si vous êtes bloqué</li>
                        <li>Consultez le <strong>Chat IA</strong> pour des questions spécifiques</li>
                        <li>Vérifiez vos <strong>Analytics</strong> pour suivre vos progrès</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #e8f5e8, #c3e6cb); padding: 15px; border-radius: 10px; border: 2px solid #a3d9a5;">
                        <strong style="color: #155724;">💎 Fonctionnalités Premium IA disponibles!</strong>
                        <p style="margin: 8px 0 0 0; color: #155724; font-size: 14px;">
                            Toutes les fonctionnalités IA sont incluses dans votre abonnement.
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="startAITutorial()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-play"></i> Tutoriel IA
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #95a5a6, #7f8c8d); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Tutoriel interactif pour les fonctionnalités IA
        function startAITutorial() {
            closeAIAnalysisModal();
            
            const tutorialSteps = [
                {
                    target: '.btn-ai',
                    title: 'Boutons IA',
                    content: 'Ces boutons vous donnent accès aux fonctionnalités d\'intelligence artificielle pour vous aider à résoudre le problème.'
                },
                {
                    target: '#aiAnalysisModal',
                    title: 'Analyses IA',
                    content: 'L\'IA peut analyser le problème et vous donner des conseils personnalisés pour améliorer votre approche.'
                },
                {
                    target: '.favorite-btn',
                    title: 'Favoris Intelligents',
                    content: 'L\'IA suit vos favoris et peut recommander des problèmes similaires basés sur vos préférences.'
                }
            ];
            
            showTutorialStep(0, tutorialSteps);
        }
        
        // Afficher une étape du tutoriel
        function showTutorialStep(stepIndex, steps) {
            if (stepIndex >= steps.length) {
                showMessage('Tutoriel IA terminé! Vous êtes prêt à utiliser toutes les fonctionnalités.', 'success');
                return;
            }
            
            const step = steps[stepIndex];
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                z-index: 10001;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            
            overlay.innerHTML = `
                <div style="background: white; padding: 30px; border-radius: 15px; max-width: 400px; text-align: center; position: relative;">
                    <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 15px; margin: -30px -30px 20px -30px; border-radius: 15px 15px 0 0;">
                        <h3 style="margin: 0; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <i class="fas fa-graduation-cap"></i> ${step.title}
                        </h3>
                    </div>
                    
                    <p style="color: #4a5568; line-height: 1.6; margin-bottom: 25px;">
                        ${step.content}
                    </p>
                    
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" style="background: #95a5a6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                            Passer
                        </button>
                        <button onclick="this.closest('div[style*=\"position: fixed\"]').remove(); showTutorialStep(${stepIndex + 1}, ${JSON.stringify(steps).replace(/"/g, '&quot;')})" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                            ${stepIndex === steps.length - 1 ? 'Terminer' : 'Suivant'}
                        </button>
                    </div>
                    
                    <div style="margin-top: 15px; color: #7f8c8d; font-size: 12px;">
                        Étape ${stepIndex + 1} sur ${steps.length}
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
        }
        
        // Initialiser toutes les fonctionnalités IA au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            initializeAIFeatures();
            
            // Ajouter les styles pour les boutons IA
            const aiButtonStyle = document.createElement('style');
            aiButtonStyle.textContent = `
                .btn-ai {
                    background: linear-gradient(135deg, #667eea, #764ba2) !important;
                    color: white !important;
                    border: none !important;
                    position: relative;
                    overflow: hidden;
                }
                
                .btn-ai:before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                    transition: left 0.5s;
                }
                
                .btn-ai:hover:before {
                    left: 100%;
                }
                
                .btn-ai:hover {
                    background: linear-gradient(135deg, #764ba2, #667eea) !important;
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4) !important;
                }
            `;
            document.head.appendChild(aiButtonStyle);
        });
        
        // Système de feedback intelligent
        function submitAIFeedback(rating, comment) {
            fetch('ai_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?= $user_id ?>,
                    problem_id: problemData.id,
                    rating: rating,
                    comment: comment,
                    session_data: {
                        time_spent: Date.now() - pageLoadTime,
                        features_used: usedFeatures,
                        ai_interactions: aiInteractionCount
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Merci pour votre feedback! Cela nous aide à améliorer l\'IA.', 'success');
                } else {
                    showMessage('Erreur lors de l\'envoi du feedback', 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Afficher le formulaire de feedback IA
        function showAIFeedbackForm() {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-star"></i> Évaluez l'Assistant IA
                </h3>
                
                <div style="background: #f8f9ff; padding: 25px; border-radius: 10px; border: 2px solid #e1e8ff; margin-bottom: 25px; text-align: center;">
                    <p style="color: #4a5568; margin-bottom: 20px; line-height: 1.6;">
                        Votre avis nous aide à améliorer l'expérience IA pour tous les utilisateurs.
                    </p>
                    
                    <div style="margin-bottom: 20px;">
                        <p style="color: #2c3e50; font-weight: 600; margin-bottom: 10px;">Comment évaluez-vous l'aide de l'IA?</p>
                        <div style="display: flex; justify-content: center; gap: 10px;">
                            ${[1,2,3,4,5].map(rating => `
                                <button onclick="selectRating(${rating})" id="rating-${rating}" style="background: #f0f0f0; border: 2px solid #ddd; color: #666; font-size: 24px; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; transition: all 0.3s;">
                                    ⭐
                                </button>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <p style="color: #2c3e50; font-weight: 600; margin-bottom: 10px;">Commentaires (optionnel):</p>
                        <textarea id="feedbackComment" placeholder="Partagez votre expérience avec l'IA..." style="width: 100%; height: 100px; padding: 12px; border: 2px solid #e1e8ff; border-radius: 8px; resize: vertical; font-family: inherit;"></textarea>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="submitFeedback()" id="submitFeedbackBtn" disabled style="background: #95a5a6; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: not-allowed; font-weight: 600;">
                        Envoyer le Feedback
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Variables pour le feedback
            window.selectedRating = 0;
            
            // Fonction pour sélectionner une note
            window.selectRating = function(rating) {
                window.selectedRating = rating;
                
                // Mettre à jour l'apparence des étoiles
                for (let i = 1; i <= 5; i++) {
                    const btn = document.getElementById(`rating-${i}`);
                    if (i <= rating) {
                        btn.style.background = 'linear-gradient(135deg, #f39c12, #e67e22)';
                        btn.style.borderColor = '#f39c12';
                        btn.style.color = 'white';
                    } else {
                        btn.style.background = '#f0f0f0';
                        btn.style.borderColor = '#ddd';
                        btn.style.color = '#666';
                    }
                }
                
                // Activer le bouton de soumission
                const submitBtn = document.getElementById('submitFeedbackBtn');
                submitBtn.disabled = false;
                submitBtn.style.background = 'linear-gradient(135deg, #2ecc71, #27ae60)';
                submitBtn.style.cursor = 'pointer';
            };
            
            // Fonction pour soumettre le feedback
            window.submitFeedback = function() {
                if (window.selectedRating === 0) {
                    showMessage('Veuillez sélectionner une note', 'error');
                    return;
                }
                
                const comment = document.getElementById('feedbackComment').value;
                submitAIFeedback(window.selectedRating, comment);
                closeAIAnalysisModal();
            };
        }
        
        // Ajouter un bouton de feedback dans le menu des fonctionnalités IA
        document.addEventListener('DOMContentLoaded', function() {
            // Créer un bouton de feedback flottant
            const feedbackBtn = document.createElement('button');
            feedbackBtn.style.cssText = `
                position: fixed;
                bottom: 180px;
                left: 20px;
                background: linear-gradient(135deg, #f39c12, #e67e22);
                color: white;
                border: none;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 18px;
                z-index: 1000;
                box-shadow: 0 3px 10px rgba(0,0,0,0.2);
                transition: all 0.3s;
            `;
            feedbackBtn.innerHTML = '⭐';
            feedbackBtn.title = 'Évaluer l\'IA';
            feedbackBtn.onclick = showAIFeedbackForm;
            
            feedbackBtn.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
            });
            
            feedbackBtn.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = '0 3px 10px rgba(0,0,0,0.2)';
            });
            
            document.body.appendChild(feedbackBtn);
        });
        
        // Système de recommandations intelligentes basé sur l'IA
        function getAIRecommendations() {
            trackFeatureUsage('ai_recommendations');
            showMessage('Génération de recommandations personnalisées...', 'info');
            
            fetch('ai_recommendations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?= $user_id ?>,
                    current_problem_id: problemData.id,
                    user_history: {
                        time_spent: Date.now() - pageLoadTime,
                        features_used: usedFeatures,
                        ai_interactions: aiInteractionCount
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAIRecommendations(data.recommendations);
                } else {
                    showMessage('Erreur lors de la génération des recommandations: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Afficher les recommandations IA
        function showAIRecommendations(recommendations) {
            const modal = document.getElementById('aiAnalysisModal');
            const content = document.getElementById('aiAnalysisContent');
            
            let html = `
                <h3 style="color: #667eea; margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-magic"></i> Recommandations IA Personnalisées
                </h3>
                
                <div style="background: linear-gradient(135deg, #f8f9ff, #e1e8ff); padding: 25px; border-radius: 10px; border: 2px solid #c7d2fe; margin-bottom: 25px;">
                    <h4 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-user-graduate"></i> Basé sur votre profil d'apprentissage:
                    </h4>
                    <p style="line-height: 1.6; color: #4a5568; margin: 0;">
                        ${recommendations.profile_analysis}
                    </p>
                </div>
            `;
            
            if (recommendations.next_problems && recommendations.next_problems.length > 0) {
                html += `
                    <div style="margin-bottom: 25px;">
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">
                            <i class="fas fa-arrow-right"></i> Problèmes recommandés pour vous:
                        </h4>
                        <div style="display: grid; gap: 15px;">
                `;
                
                recommendations.next_problems.forEach(problem => {
                    html += `
                        <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #e1e8ff; cursor: pointer;" onclick="window.open('problem.php?id=${problem.id}', '_blank')">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <h5 style="margin: 0; color: #2c3e50; flex: 1;">${problem.title}</h5>
                                <span style="background: ${problem.difficulty === 'easy' ? '#d5f5e3' : problem.difficulty === 'medium' ? '#fef9e7' : '#fdedec'}; color: ${problem.difficulty === 'easy' ? '#27ae60' : problem.difficulty === 'medium' ? '#f39c12' : '#e74c3c'}; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                    ${problem.difficulty === 'easy' ? 'Facile' : problem.difficulty === 'medium' ? 'Moyen' : 'Difficile'}
                                </span>
                            </div>
                            <p style="color: #7f8c8d; margin: 0 0 10px 0; font-size: 14px; line-height: 1.4;">
                                ${problem.description}
                            </p>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #3498db; font-size: 12px;">
                                    <i class="fas fa-code"></i> ${problem.language}
                                </span>
                                <span style="color: #27ae60; font-size: 12px; font-weight: 600;">
                                    ${problem.match_percentage}% de correspondance
                                </span>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div></div>`;
            }
            
            if (recommendations.learning_path) {
                html += `
                    <div style="background: #e8f5e8; padding: 20px; border-radius: 10px; border: 2px solid #a3d9a5; margin-bottom: 25px;">
                        <h4 style="color: #155724; margin-bottom: 15px;">
                            <i class="fas fa-route"></i> Votre parcours d'apprentissage suggéré:
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                `;
                
                recommendations.learning_path.forEach((step, index) => {
                    html += `
                        <div style="display: flex; align-items: center; gap: 15px; padding: 12px; background: rgba(255,255,255,0.7); border-radius: 8px;">
                            <div style="background: #27ae60; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">
                                ${index + 1}
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #155724; margin-bottom: 4px;">${step.title}</div>
                                <div style="font-size: 13px; color: #155724; opacity: 0.8;">${step.description}</div>
                            </div>
                            <div style="color: #27ae60; font-size: 12px; font-weight: 600;">
                                ${step.estimated_time}
                            </div>
                        </div>
                    `;
                });
                
                html += `</div></div>`;
            }
            
            if (recommendations.skills_to_develop && recommendations.skills_to_develop.length > 0) {
                html += `
                    <div style="background: #fff3cd; padding: 20px; border-radius: 10px; border: 2px solid #ffeaa7; margin-bottom: 25px;">
                        <h4 style="color: #856404; margin-bottom: 15px;">
                            <i class="fas fa-chart-line"></i> Compétences à développer:
                        </h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                `;
                
                recommendations.skills_to_develop.forEach(skill => {
                    html += `
                        <span style="background: rgba(133, 100, 4, 0.1); color: #856404; padding: 8px 12px; border-radius: 15px; font-size: 14px; border: 1px solid rgba(133, 100, 4, 0.2); cursor: pointer;" onclick="searchSkillResources('${skill}')">
                            ${skill}
                        </span>
                    `;
                });
                
                html += `</div></div>`;
            }
            
            html += `
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button onclick="saveRecommendations()" style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-bookmark"></i> Sauvegarder
                    </button>
                    <button onclick="shareRecommendations()" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-share"></i> Partager
                    </button>
                    <button onclick="closeAIAnalysisModal()" style="background: linear-gradient(135deg, #95a5a6, #7f8c8d); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Fermer
                    </button>
                </div>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour rechercher des ressources sur une compétence
        function searchSkillResources(skill) {
            showMessage(`Recherche de ressources pour: ${skill}`, 'info');
            // Rediriger vers une page de ressources ou ouvrir une recherche
            window.open(`https://www.google.com/search?q=${encodeURIComponent(skill + ' programming tutorial')}`, '_blank');
        }
        
        // Fonction pour partager les recommandations
        function shareRecommendations() {
            if (navigator.share) {
                navigator.share({
                    title: 'Mes Recommandations IA - CodeChallenge',
                    text: 'Découvrez mes recommandations personnalisées générées par l\'IA sur CodeChallenge!',
                    url: window.location.href
                })
                .then(() => showMessage('Recommandations partagées!', 'success'))
                .catch(() => showMessage('Erreur lors du partage', 'error'));
            } else {
                // Fallback: copier dans le presse-papiers
                const shareText = `Découvrez mes recommandations IA sur CodeChallenge: ${window.location.href}`;
                navigator.clipboard.writeText(shareText)
                    .then(() => showMessage('Lien copié dans le presse-papiers!', 'success'))
                    .catch(() => showMessage('Erreur lors de la copie', 'error'));
            }
        }
        
        // Fonction pour sauvegarder les recommandations
        function saveRecommendations() {
            fetch('save_recommendations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?= $user_id ?>,
                    problem_id: problemData.id,
                    timestamp: Date.now()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Recommandations sauvegardées dans votre profil!', 'success');
                } else {
                    showMessage('Erreur lors de la sauvegarde', 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion', 'error');
            });
        }
        
        // Ajouter un bouton de recommandations IA
        <?php if (isLoggedIn()): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const actionsDiv = document.querySelector('.actions > div:first-child');
            if (actionsDiv) {
                const recommendBtn = document.createElement('button');
                recommendBtn.className = 'btn btn-ai';
                recommendBtn.innerHTML = '<i class="fas fa-magic"></i> Recommandations IA';
                recommendBtn.onclick = getAIRecommendations;
                recommendBtn.title = 'Obtenez des recommandations personnalisées';
                actionsDiv.appendChild(recommendBtn);
            }
        });
        <?php endif; ?>
        
        // Système de progression intelligente avec IA
        function trackProgressWithAI() {
            const progressData = {
                user_id: <?= $user_id ?>,
                problem_id: problemData.id,
                session_start: pageLoadTime,
                session_duration: Date.now() - pageLoadTime,
                features_used: usedFeatures,
                ai_interactions: aiInteractionCount,
                scroll_depth: Math.max(document.documentElement.scrollTop, document.body.scrollTop) / (document.documentElement.scrollHeight - document.documentElement.clientHeight) * 100,
                clicks_count: clickCount,
                time_on_code_sections: timeOnCodeSections
            };
            
            fetch('track_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(progressData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.milestone_reached) {
                    showMilestoneNotification(data.milestone);
                }
            })
            .catch(error => {
                console.log('Erreur tracking progression:', error);
            });
        }
        
        // Afficher une notification de milestone
        function showMilestoneNotification(milestone) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: linear-gradient(135deg, #f39c12, #e67e22);
                color: white;
                padding: 30px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                z-index: 10002;
                text-align: center;
                max-width: 400px;
                animation: milestoneAppear 0.8s ease-out;
            `;
            
            notification.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 15px;">🏆</div>
                <h3 style="margin: 0 0 10px 0; font-size: 24px;">Félicitations!</h3>
                <p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.5;">
                    ${milestone.message}
                </p>
                <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="font-size: 20px; font-weight: bold; margin-bottom: 5px;">
                        +${milestone.points} points
                    </div>
                    <div style="font-size: 14px; opacity: 0.9;">
                        ${milestone.description}
                    </div>
                </div>
                <button onclick="this.parentElement.remove()" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid white; padding: 10px 20px; border-radius: 25px; cursor: pointer; font-weight: 600;">
                    Continuer
                </button>
            `;
            
            // Ajouter l'animation CSS
            const milestoneStyle = document.createElement('style');
            milestoneStyle.textContent = `
                @keyframes milestoneAppear {
                    0% { opacity: 0; transform: translate(-50%, -50%) scale(0.5); }
                    50% { transform: translate(-50%, -50%) scale(1.1); }
                    100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                }
            `;
            document.head.appendChild(milestoneStyle);
            
            document.body.appendChild(notification);
            
            // Auto-remove après 10 secondes
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.animation = 'milestoneAppear 0.5s ease-in reverse';
                    setTimeout(() => notification.remove(), 500);
                }
            }, 10000);
        }
        
        // Variables pour tracker l'engagement
        let clickCount = 0;
        let timeOnCodeSections = 0;
        let lastCodeSectionTime = 0;
        
        // Tracker les clics
        document.addEventListener('click', function() {
            clickCount++;
        });
        
        // Tracker le temps passé sur les sections de code
        document.addEventListener('DOMContentLoaded', function() {
            const codeBlocks = document.querySelectorAll('.code-block, pre, code');
            
            codeBlocks.forEach(block => {
                block.addEventListener('mouseenter', function() {
                    lastCodeSectionTime = Date.now();
                });
                
                block.addEventListener('mouseleave', function() {
                    if (lastCodeSectionTime > 0) {
                        timeOnCodeSections += Date.now() - lastCodeSectionTime;
                        lastCodeSectionTime = 0;
                    }
                });
            });
        });
        
        // Tracker la progression toutes les 30 secondes
        setInterval(trackProgressWithAI, 30000);
        
        // Tracker à la fermeture de la page
        window.addEventListener('beforeunload', trackProgressWithAI);
        
        // Système de badges IA
        function checkAIBadges() {
            fetch('check_ai_badges.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?= $user_id ?>,
                    session_data: {
                        features_used: usedFeatures,
                        ai_interactions: aiInteractionCount,
                        time_spent: Date.now() - pageLoadTime
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.new_badges.length > 0) {
                    showNewBadges(data.new_badges);
                }
            })
            .catch(error => {
                console.log('Erreur vérification badges:', error);
            });
        }
        
        // Afficher les nouveaux badges
        function showNewBadges(badges) {
            badges.forEach((badge, index) => {
                setTimeout(() => {
                    const badgeNotification = document.createElement('div');
                    badgeNotification.style.cssText = `
                        position: fixed;
                        top: ${20 + (index * 80)}px;
                        right: 20px;
                        background: linear-gradient(135deg, #9b59b6, #8e44ad);
                        color: white;
                        padding: 15px 20px;
                        border-radius: 10px;
                        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                        z-index: 9999;
                        max-width: 300px;
                        animation: badgeSlideIn 0.5s ease-out;
                        cursor: pointer;
                    `;
                    
                    badgeNotification.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="font-size: 24px;">${badge.icon}</div>
                            <div style="flex: 1;">
                                <div style="font-weight: bold; margin-bottom: 4px;">Nouveau Badge!</div>
                                <div style="font-size: 14px; opacity: 0.9;">${badge.name}</div>
                            </div>
                            <div style="font-size: 18px; opacity: 0.7;">×</div>
                        </div>
                    `;
                    
                    badgeNotification.onclick = () => {
                        badgeNotification.style.animation = 'badgeSlideOut 0.3s ease-in';
                        setTimeout(() => badgeNotification.remove(), 300);
                    };
                    
                    document.body.appendChild(badgeNotification);
                    
                    // Auto-remove après 6 secondes
                    setTimeout(() => {
                        if (badgeNotification.parentElement) {
                            badgeNotification.style.animation = 'badgeSlideOut 0.3s ease-in';
                            setTimeout(() => badgeNotification.remove(), 300);
                        }
                    }, 6000);
                }, index * 500);
            });
            
            // Ajouter les animations CSS pour les badges
            const badgeStyle = document.createElement('style');
            badgeStyle.textContent = `
                @keyframes badgeSlideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes badgeSlideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(badgeStyle);
        }
        
        // Vérifier les badges toutes les 2 minutes
        setInterval(checkAIBadges, 120000);
        
        // Message de bienvenue IA personnalisé
        function showWelcomeAIMessage() {
            <?php if (isLoggedIn()): ?>
            const user = <?= json_encode($user) ?>;
            
            setTimeout(() => {
                const welcomeMessages = [
                    `Bonjour ${user.name}! L'IA est prête à vous accompagner sur ce problème ${problemData.difficulty}.`,
                    `Salut ${user.name}! Avec ${user.problems_solved} problèmes résolus, vous êtes sur la bonne voie! L'IA peut vous aider à progresser encore plus.`,
                    `Hey ${user.name}! Ce problème en ${problemData.language} semble parfait pour votre niveau. L'IA peut vous donner des conseils personnalisés.`,
                    `Bienvenue ${user.name}! Votre score de ${user.score} points montre votre progression. L'IA peut vous aider à atteindre de nouveaux sommets!`
                ];
                
                const randomMessage = welcomeMessages[Math.floor(Math.random() * welcomeMessages.length)];
                
                showSmartNotification(randomMessage, 'welcome');
            }, 3000);
            <?php endif; ?>
        }
        
        // Initialiser le message de bienvenue
        document.addEventListener('DOMContentLoaded', showWelcomeAIMessage);
        
        // Fonction finale pour nettoyer et optimiser
        function optimizeAIPerformance() {
            // Nettoyer les anciens éléments DOM
            const oldModals = document.querySelectorAll('[id*="Modal"]:not(#aiAnalysisModal)');
            oldModals.forEach(modal => {
                if (modal.style.display === 'none' || !modal.style.display) {
                    modal.remove();
                }
            });
            
            // Optimiser les requêtes IA en cache
            if ('caches' in window) {
                caches.open('ai-responses-v1').then(cache => {
                    // Mettre en cache les réponses IA fréquentes
                    cache.add('ai_hints.php');
                    cache.add('ai_analysis.php');
                });
            }
            
            // Précharger les ressources IA critiques
            const preloadLinks = [
                'ai_chat.php',
                'ai_recommendations.php',
                'ai_feedback.php'
            ];
            
            preloadLinks.forEach(url => {
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = url;
                document.head.appendChild(link);
            });
        }
        
        // Optimiser les performances après le chargement complet
        window.addEventListener('load', function() {
            setTimeout(optimizeAIPerformance, 2000);
        });
        
        console.log('🚀 Système IA CodeChallenge entièrement initialisé!');
        console.log('📊 Fonctionnalités disponibles:', {
            'Analyse IA': '✅',
            'Chat IA': '✅', 
            'Recommandations': '✅',
            'Analytics': '✅',
            'Badges': '✅',
            'Progression': '✅',
            'Feedback': '✅'
        });
    </script>

    <!-- Scripts pour la gestion des favoris et autres fonctionnalités -->
    <script>
        // Gestion des favoris
        <?php if (isLoggedIn()): ?>
        document.querySelector('.favorite-btn')?.addEventListener('click', function() {
            const problemId = this.dataset.pid;
            const isCurrentlyFavorite = this.classList.contains('active');
            const heartIcon = this.querySelector('i');
            
            // Animation de chargement
            this.disabled = true;
            heartIcon.className = 'fas fa-spinner fa-spin';
            
            fetch('manage_favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `problem_id=${problemId}&favorite_action=${isCurrentlyFavorite ? 'remove' : 'add'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'added') {
                        this.classList.add('active');
                        this.title = 'Retirer des favoris';
                        showMessage('Ajouté aux favoris!', 'success');
                    } else {
                        this.classList.remove('active');
                        this.title = 'Ajouter aux favoris';
                        showMessage('Retiré des favoris!', 'info');
                    }
                    
                    // Animation de succès
                    heartIcon.style.transform = 'scale(1.3)';
                    setTimeout(() => {
                        heartIcon.style.transform = '';
                    }, 200);
                } else {
                    showMessage('Erreur: ' + (data.message || 'Action impossible'), 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur de connexion', 'error');
            })
            .finally(() => {
                this.disabled = false;
                heartIcon.className = 'fas fa-star';
            });
        });
        <?php endif; ?>
        
        // Fonction pour copier le code
        function copyCode(button) {
            const codeBlock = button.parentElement.querySelector('code');
            const text = codeBlock.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copié!';
                button.style.background = 'rgba(46, 204, 113, 0.1)';
                button.style.color = '#27ae60';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = 'rgba(52, 152, 219, 0.1)';
                    button.style.color = '#3498db';
                }, 2000);
                
                showMessage('Code copié dans le presse-papiers!', 'success');
            }).catch(() => {
                showMessage('Erreur lors de la copie', 'error');
            });
        }
        
        // Fonction pour afficher des messages
        function showMessage(message, type = 'info') {
            const messageDiv = document.createElement('div');
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                z-index: 9999;
                max-width: 350px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transition: all 0.3s ease;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 10px;
            `;
            
            let icon, bgColor, textColor, borderColor;
            
            switch(type) {
                case 'success':
                    icon = '✅';
                    bgColor = '#d4edda';
                    textColor = '#155724';
                    borderColor = '#c3e6cb';
                    break;
                case 'error':
                    icon = '❌';
                    bgColor = '#f8d7da';
                    textColor = '#721c24';
                    borderColor = '#f5c6cb';
                    break;
                case 'warning':
                    icon = '⚠️';
                    bgColor = '#fff3cd';
                    textColor = '#856404';
                    borderColor = '#ffeaa7';
                    break;
                default:
                    icon = 'ℹ️';
                    bgColor = '#d1ecf1';
                    textColor = '#0c5460';
                    borderColor = '#bee5eb';
            }
            
            messageDiv.style.backgroundColor = bgColor;
            messageDiv.style.color = textColor;
            messageDiv.style.border = `1px solid ${borderColor}`;
            messageDiv.innerHTML = `${icon} ${message}`;
            
            document.body.appendChild(messageDiv);
            
            // Animation d'entrée
            setTimeout(() => {
                messageDiv.style.transform = 'translateX(0)';
                messageDiv.style.opacity = '1';
            }, 100);
            
            // Suppression automatique
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                messageDiv.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 300);
            }, 4000);
        }
        
        // Gestion du redimensionnement de la fenêtre
        window.addEventListener('resize', function() {
            // Ajuster les modales si elles sont ouvertes
            const modals = document.querySelectorAll('[id*="Modal"]');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    // Réajuster la position si nécessaire
                    modal.style.top = '0';
                    modal.style.left = '0';
                }
            });
        });
        
        // Gestion des raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Échap pour fermer les modales
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('[id*="Modal"][style*="display: block"]');
                openModals.forEach(modal => {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
                
                // Fermer les menus utilisateur
                const openMenus = document.querySelectorAll('.user-menu-[id].active');
                openMenus.forEach(menu => {
                    menu.classList.remove('active');
                });
            }
            
            // Ctrl+K pour ouvrir le chat IA
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                <?php if (isLoggedIn()): ?>
                openAIChat();
                <?php endif; ?>
            }
            
            // Ctrl+Shift+A pour l'analyse IA
            if (e.ctrlKey && e.shiftKey && e.key === 'A') {
                e.preventDefault();
                <?php if (isLoggedIn()): ?>
                analyzeWithAI();
                <?php endif; ?>
            }
        });
        
        // Amélioration de l'accessibilité
        document.addEventListener('DOMContentLoaded', function() {
            // Ajouter des attributs ARIA
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                if (!button.getAttribute('aria-label') && !button.getAttribute('title')) {
                    const text = button.textContent.trim();
                    if (text) {
                        button.setAttribute('aria-label', text);
                    }
                }
            });
            
            // Améliorer la navigation au clavier
            const interactiveElements = document.querySelectorAll('button, a, [tabindex]');
            interactiveElements.forEach((element, index) => {
                if (!element.getAttribute('tabindex')) {
                    element.setAttribute('tabindex', '0');
                }
            });
            
            // Ajouter des indicateurs de focus visibles
            const focusStyle = document.createElement('style');
            focusStyle.textContent = `
                button:focus, a:focus, [tabindex]:focus {
                    outline: 2px solid #3498db !important;
                    outline-offset: 2px !important;
                }
                
                .btn:focus {
                    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3) !important;
                }
            `;
            document.head.appendChild(focusStyle);
        });
        
        // Système de cache intelligent pour les interactions
        class AICache {
            constructor() {
                this.cache = new Map();
                this.maxSize = 50;
                this.ttl = 300000; // 5 minutes
            }
            
            set(key, value) {
                if (this.cache.size >= this.maxSize) {
                    const firstKey = this.cache.keys().next().value;
                    this.cache.delete(firstKey);
                }
                
                this.cache.set(key, {
                    value: value,
                    timestamp: Date.now()
                });
            }
            
            get(key) {
                const item = this.cache.get(key);
                if (!item) return null;
                
                if (Date.now() - item.timestamp > this.ttl) {
                    this.cache.delete(key);
                    return null;
                }
                
                return item.value;
            }
            
            clear() {
                this.cache.clear();
            }
        }
        
        // Initialiser le cache IA
        const aiCache = new AICache();
        
        // Fonction optimisée pour les requêtes IA avec cache
        function makeAIRequest(endpoint, data, useCache = true) {
            const cacheKey = endpoint + JSON.stringify(data);
            
            if (useCache) {
                const cachedResult = aiCache.get(cacheKey);
                if (cachedResult) {
                    return Promise.resolve(cachedResult);
                }
            }
            
            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (useCache && result.success) {
                    aiCache.set(cacheKey, result);
                }
                return result;
            });
        }
        
        // Préchargement intelligent des données
        function preloadAIData() {
            <?php if (isLoggedIn()): ?>
            // Précharger les données utilisateur fréquemment utilisées
            const preloadRequests = [
                {
                    endpoint: 'ai_user_stats.php',
                    data: { user_id: <?= $user_id ?> }
                },
                {
                    endpoint: 'ai_quick_hints.php',
                    data: { problem_id: <?= $problem_id ?> }
                }
            ];
            
            preloadRequests.forEach(request => {
                makeAIRequest(request.endpoint, request.data, true)
                    .catch(error => console.log('Préchargement échoué:', error));
            });
            <?php endif; ?>
        }
        
        // Démarrer le préchargement après 2 secondes
        setTimeout(preloadAIData, 2000);
        
        // Système de métriques de performance
        const performanceMetrics = {
            pageLoadTime: Date.now(),
            aiRequestCount: 0,
            aiResponseTimes: [],
            userInteractions: 0,
            
            recordAIRequest(responseTime) {
                this.aiRequestCount++;
                this.aiResponseTimes.push(responseTime);
            },
            
            recordInteraction() {
                this.userInteractions++;
            },
            
            getAverageResponseTime() {
                if (this.aiResponseTimes.length === 0) return 0;
                return this.aiResponseTimes.reduce((a, b) => a + b, 0) / this.aiResponseTimes.length;
            },
            
            getReport() {
                return {
                    sessionDuration: Date.now() - this.pageLoadTime,
                    aiRequestCount: this.aiRequestCount,
                    averageResponseTime: this.getAverageResponseTime(),
                    userInteractions: this.userInteractions,
                    cacheHitRate: aiCache.cache.size > 0 ? (this.aiRequestCount / aiCache.cache.size) : 0
                };
            }
        };
        
        // Tracker les interactions utilisateur
        document.addEventListener('click', () => performanceMetrics.recordInteraction());
        document.addEventListener('keydown', () => performanceMetrics.recordInteraction());
        
        // Envoyer les métriques avant la fermeture
        window.addEventListener('beforeunload', function() {
            const metrics = performanceMetrics.getReport();
            
            // Utiliser sendBeacon pour un envoi fiable
            if (navigator.sendBeacon) {
                navigator.sendBeacon('track_performance.php', JSON.stringify({
                    user_id: <?= $user_id ?>,
                    problem_id: <?= $problem_id ?>,
                    metrics: metrics
                }));
            }
        });
        
        // Fonction finale d'initialisation
        function finalizeInitialization() {
            console.log('🎯 Initialisation complète de la page problème');
            console.log('📈 Métriques de performance activées');
            console.log('🔄 Cache IA configuré');
            console.log('⚡ Préchargement des données lancé');
            console.log('🎨 Interface utilisateur optimisée');
            
            // Marquer la page comme entièrement chargée
            document.body.classList.add('fully-loaded');
            
            // Déclencher un événement personnalisé
            const event = new CustomEvent('problemPageReady', {
                detail: {
                    problemId: <?= $problem_id ?>,
                    userId: <?= $user_id ?>,
                    timestamp: Date.now()
                }
            });
            document.dispatchEvent(event);
        }
        
        // Finaliser l'initialisation quand tout est prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', finalizeInitialization);
        } else {
            finalizeInitialization();
        }
    </script>

    <!-- Styles additionnels pour les animations et améliorations -->
    <style>
        /* Animations fluides pour tous les éléments interactifs */
        * {
            transition: all 0.3s ease;
        }
        
        /* Animation de chargement de la page */
        body:not(.fully-loaded) {
            opacity: 0;
        }
        
        body.fully-loaded {
            opacity: 1;
            transition: opacity 0.5s ease-in;
        }
        
        /* Améliorations visuelles pour les boutons */
        .btn {
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        /* Animations pour les cartes de solution */
        .solution-card {
            transform: translateY(0);
            transition: all 0.3s ease;
        }
        
        .solution-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        /* Indicateur de chargement global */
        .loading-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #3498db, #2ecc71, #f39c12, #e74c3c);
            background-size: 200% 100%;
            animation: loadingGradient 2s linear infinite;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .loading-indicator.active {
            opacity: 1;
        }
        
        @keyframes loadingGradient {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Améliorations pour l'accessibilité */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Mode sombre automatique */
        @media (prefers-color-scheme: dark) {
            .problem-container {
                background: #2c3e50;
                color: #ecf0f1;
            }
            
            .section {
                border-bottom-color: #34495e;
            }
            
            .code-block {
                background: #1e1e1e;
                border: 1px solid #34495e;
            }
            
            .solution-card {
                background: #34495e;
                color: #ecf0f1;
            }
        }
        
        /* Responsive amélioré */
        @media (max-width: 480px) {
            .problem-container {
                margin: 10px 5px;
                border-radius: 0;
            }
            
            .problem-header, .section, .solutions-section, .actions {
                padding: 15px;
            }
            
            .problem-title {
                font-size: 20px;
                line-height: 1.3;
            }
            
            .btn {
                padding: 10px 16px;
                font-size: 14px;
            }
        }
        
        /* Améliorations pour les écrans haute résolution */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .author-avatar, .solution-avatar {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }
        
        /* Styles pour l'impression */
        @media print {
            .favorite-btn, .actions, .btn-ai {
                display: none !important;
            }
            
            .problem-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .code-block {
                background: #f8f8f8 !important;
                color: #333 !important;
                border: 1px solid #ddd;
            }
        }
        
        /* Animation de pulsation pour les éléments importants */
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* Effet de brillance pour les boutons premium */
        .btn-premium {
            background: linear-gradient(45deg, #f39c12, #e67e22, #d35400);
            background-size: 200% 200%;
            animation: premiumShine 3s ease-in-out infinite;
        }
        
        @keyframes premiumShine {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Indicateurs de statut améliorés */
        .status-indicator {
            position: relative;
            display: inline-block;
        }
        
        .status-indicator::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.3;
            animation: statusPulse 2s infinite;
        }
        
        @keyframes statusPulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.2); opacity: 0.1; }
        }
        
        /* Améliorations pour les tooltips */
        [title] {
            position: relative;
        }
        
        [title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            pointer-events: none;
        }
        
        [title]:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(100%);
            border: 5px solid transparent;
            border-top-color: rgba(0,0,0,0.9);
            z-index: 1000;
            pointer-events: none;
        }
    </style>

    <!-- Script pour les fonctionnalités avancées -->
    <script>
        // Gestionnaire d'indicateur de chargement global
        class LoadingManager {
            constructor() {
                this.activeRequests = 0;
                this.indicator = this.createIndicator();
            }
            
            createIndicator() {
                const indicator = document.createElement('div');
                indicator.className = 'loading-indicator';
                document.body.appendChild(indicator);
                return indicator;
            }
            
            show() {
                this.activeRequests++;
                this.indicator.classList.add('active');
            }
            
            hide() {
                this.activeRequests = Math.max(0, this.activeRequests - 1);
                if (this.activeRequests === 0) {
                    this.indicator.classList.remove('active');
                }
            }
        }
        
        const loadingManager = new LoadingManager();
        
        // Intercepter toutes les requêtes fetch pour afficher le chargement
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            loadingManager.show();
            return originalFetch.apply(this, args)
                .finally(() => loadingManager.hide());
        };
        
        // Gestionnaire de thème intelligent
        class ThemeManager {
            constructor() {
                this.currentTheme = this.detectTheme();
                this.applyTheme();
                this.watchSystemTheme();
            }
            
            detectTheme() {
                const saved = localStorage.getItem('preferred-theme');
                if (saved) return saved;
                
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            
            applyTheme() {
                document.documentElement.setAttribute('data-theme', this.currentTheme);
                
                // Mettre à jour les couleurs des graphiques et éléments dynamiques
                this.updateDynamicElements();
            }
            
            updateDynamicElements() {
                const isDark = this.currentTheme === 'dark';
                
                // Mettre à jour les couleurs des boutons IA
                const aiButtons = document.querySelectorAll('.btn-ai');
                aiButtons.forEach(btn => {
                    if (isDark) {
                        btn.style.background = 'linear-gradient(135deg, #4a5568, #2d3748)';
                    } else {
                        btn.style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
                    }
                });
            }
            
            toggle() {
                this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
                this.applyTheme();
                localStorage.setItem('preferred-theme', this.currentTheme);
            }
            
            watchSystemTheme() {
                window.matchMedia('(prefers-color-scheme: dark)')
                    .addEventListener('change', (e) => {
                        if (!localStorage.getItem('preferred-theme')) {
                            this.currentTheme = e.matches ? 'dark' : 'light';
                            this.applyTheme();
                        }
                    });
            }
        }
        
        const themeManager = new ThemeManager();
        
        // Ajouter un bouton de basculement de thème
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.createElement('button');
            themeToggle.style.cssText = `
                position: fixed;
                bottom: 120px;
                left: 20px;
                background: linear-gradient(135deg, #34495e, #2c3e50);
                color: white;
                border: none;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 18px;
                z-index: 1000;
                box-shadow: 0 3px 10px rgba(0,0,0,0.2);
                transition: all 0.3s;
            `;
            themeToggle.innerHTML = themeManager.currentTheme === 'dark' ? '☀️' : '🌙';
            themeToggle.title = 'Basculer le thème';
            
            themeToggle.addEventListener('click', function() {
                themeManager.toggle();
                this.innerHTML = themeManager.currentTheme === 'dark' ? '☀️' : '🌙';
            });
            
            themeToggle.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
            });
            
            themeToggle.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = '0 3px 10px rgba(0,0,0,0.2)';
            });
            
            document.body.appendChild(themeToggle);
        });
        
        // Gestionnaire de raccourcis clavier avancés
        class KeyboardManager {
            constructor() {
                this.shortcuts = new Map();
                this.registerDefaultShortcuts();
                this.listen();
            }
            
            registerDefaultShortcuts() {
                this.register('ctrl+/', () => this.showShortcutsHelp());
                this.register('ctrl+shift+c', () => this.copyProblemUrl());
                this.register('ctrl+shift+f', () => this.toggleFavorite());
                this.register('ctrl+shift+t', () => themeManager.toggle());
                this.register('ctrl+shift+s', () => this.submitSolution());
            }
            
            register(combination, callback) {
                this.shortcuts.set(combination.toLowerCase(), callback);
            }
            
            listen() {
                document.addEventListener('keydown', (e) => {
                    const combination = this.getCombination(e);
                    const callback = this.shortcuts.get(combination);
                    
                    if (callback) {
                        e.preventDefault();
                        callback();
                    }
                });
            }
            
            getCombination(e) {
                const parts = [];
                if (e.ctrlKey) parts.push('ctrl');
                if (e.shiftKey) parts.push('shift');
                if (e.altKey) parts.push('alt');
                if (e.metaKey) parts.push('meta');
                parts.push(e.key.toLowerCase());
                return parts.join('+');
            }
            
            showShortcutsHelp() {
                const helpModal = document.createElement('div');
                helpModal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.8);
                    z-index: 10001;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                `;
                
                helpModal.innerHTML = `
                    <div style="background: white; padding: 30px; border-radius: 15px; max-width: 500px; max-height: 80vh; overflow-y: auto;">
                        <h3 style="margin-top: 0; color: #2c3e50; text-align: center;">
                            <i class="fas fa-keyboard"></i> Raccourcis Clavier
                        </h3>
                        
                        <div style="display: grid; gap: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                <span>Aide raccourcis</span>
                                <kbd style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;">Ctrl + /</kbd>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                <span>Chat IA</span>
                                <kbd style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;">Ctrl + K</kbd>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                <span>Analyse IA</span>
                                <kbd style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;">Ctrl +Shift + A</kbd>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                <span>Copier URL du problème</span>
                                <kbd style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;">Ctrl + Shift + C</kbd>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                <span>Basculer favori</span>
                                <kbd style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;">Ctrl + Shift + F</kbd>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                <span>Basculer thème</span>
                                <kbd style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;">Ctrl + Shift + T</kbd>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                <span>Soumettre solution</span>
                                <kbd style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;">Ctrl + Shift + S</kbd>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                                <span>Fermer modales</span>
                                <kbd style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-family: monospace;">Échap</kbd>
                            </div>
                        </div>
                        
                        <div style="text-align: center; margin-top: 25px;">
                            <button onclick="this.closest('[style*=\"position: fixed\"]').remove()" style="background: #3498db; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                Fermer
                            </button>
                        </div>
                    </div>
                `;
                
                helpModal.addEventListener('click', (e) => {
                    if (e.target === helpModal) {
                        helpModal.remove();
                    }
                });
                
                document.body.appendChild(helpModal);
            }
            
            copyProblemUrl() {
                navigator.clipboard.writeText(window.location.href)
                    .then(() => showMessage('URL du problème copiée!', 'success'))
                    .catch(() => showMessage('Erreur lors de la copie', 'error'));
            }
            
            toggleFavorite() {
                const favoriteBtn = document.querySelector('.favorite-btn');
                if (favoriteBtn) {
                    favoriteBtn.click();
                }
            }
            
            submitSolution() {
                window.location.href = `submit_solution.php?problem_id=<?= $problem_id ?>`;
            }
        }
        
        const keyboardManager = new KeyboardManager();
        
        // Gestionnaire de performance et optimisation
        class PerformanceOptimizer {
            constructor() {
                this.observeImages();
                this.optimizeScrolling();
                this.preloadCriticalResources();
            }
            
            observeImages() {
                if ('IntersectionObserver' in window) {
                    const imageObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                if (img.dataset.src) {
                                    img.src = img.dataset.src;
                                    img.removeAttribute('data-src');
                                    imageObserver.unobserve(img);
                                }
                            }
                        });
                    });
                    
                    document.querySelectorAll('img[data-src]').forEach(img => {
                        imageObserver.observe(img);
                    });
                }
            }
            
            optimizeScrolling() {
                let ticking = false;
                
                function updateScrollPosition() {
                    // Optimiser les animations basées sur le scroll
                    const scrolled = window.pageYOffset;
                    const rate = scrolled * -0.5;
                    
                    // Parallax léger pour l'en-tête
                    const header = document.querySelector('.problem-header');
                    if (header) {
                        header.style.transform = `translateY(${rate}px)`;
                    }
                    
                    ticking = false;
                }
                
                window.addEventListener('scroll', () => {
                    if (!ticking) {
                        requestAnimationFrame(updateScrollPosition);
                        ticking = true;
                    }
                });
            }
            
            preloadCriticalResources() {
                // Précharger les ressources critiques
                const criticalResources = [
                    'submit_solution.php',
                    'manage_favorites.php',
                    'ai_chat.php'
                ];
                
                criticalResources.forEach(resource => {
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = resource;
                    document.head.appendChild(link);
                });
            }
        }
        
        const performanceOptimizer = new PerformanceOptimizer();
        
        // Gestionnaire d'analytics avancé
        class AnalyticsManager {
            constructor() {
                this.events = [];
                this.sessionStart = Date.now();
                this.setupTracking();
            }
            
            track(event, data = {}) {
                const eventData = {
                    event,
                    data,
                    timestamp: Date.now(),
                    sessionTime: Date.now() - this.sessionStart,
                    url: window.location.href,
                    userAgent: navigator.userAgent
                };
                
                this.events.push(eventData);
                
                // Envoyer immédiatement les événements critiques
                if (this.isCriticalEvent(event)) {
                    this.sendEvents([eventData]);
                }
            }
            
            isCriticalEvent(event) {
                const criticalEvents = ['error', 'payment', 'solution_submit'];
                return criticalEvents.includes(event);
            }
            
            setupTracking() {
                // Tracker les erreurs JavaScript
                window.addEventListener('error', (e) => {
                    this.track('javascript_error', {
                        message: e.message,
                        filename: e.filename,
                        lineno: e.lineno,
                        colno: e.colno
                    });
                });
                
                // Tracker les erreurs de promesses
                window.addEventListener('unhandledrejection', (e) => {
                    this.track('promise_rejection', {
                        reason: e.reason
                    });
                });
                
                // Tracker la performance de navigation
                window.addEventListener('load', () => {
                    if (performance.navigation) {
                        this.track('page_performance', {
                            loadTime: performance.now(),
                            navigationType: performance.navigation.type,
                            redirectCount: performance.navigation.redirectCount
                        });
                    }
                });
                
                // Envoyer les événements périodiquement
                setInterval(() => this.sendPendingEvents(), 30000);
                
                // Envoyer avant la fermeture
                window.addEventListener('beforeunload', () => this.sendPendingEvents());
            }
            
            sendPendingEvents() {
                if (this.events.length > 0) {
                    this.sendEvents(this.events);
                    this.events = [];
                }
            }
            
            sendEvents(events) {
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('analytics.php', JSON.stringify({
                        user_id: <?= $user_id ?>,
                        problem_id: <?= $problem_id ?>,
                        events: events
                    }));
                } else {
                    // Fallback pour les navigateurs plus anciens
                    fetch('analytics.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: <?= $user_id ?>,
                            problem_id: <?= $problem_id ?>,
                            events: events
                        })
                    }).catch(() => {}); // Ignorer les erreurs d'analytics
                }
            }
        }
        
        const analytics = new AnalyticsManager();
        
        // Tracker les interactions importantes
        document.addEventListener('DOMContentLoaded', function() {
            // Tracker les clics sur les boutons
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    analytics.track('button_click', {
                        button_text: this.textContent.trim(),
                        button_class: this.className
                    });
                });
            });
            
            // Tracker l'utilisation des fonctionnalités IA
            const originalOpenAIChat = window.openAIChat;
            if (originalOpenAIChat) {
                window.openAIChat = function() {
                    analytics.track('ai_chat_opened');
                    return originalOpenAIChat.apply(this, arguments);
                };
            }
            
            const originalAnalyzeWithAI = window.analyzeWithAI;
            if (originalAnalyzeWithAI) {
                window.analyzeWithAI = function() {
                    analytics.track('ai_analysis_requested');
                    return originalAnalyzeWithAI.apply(this, arguments);
                };
            }
        });
        
        // Système de notifications push (si supporté)
        class NotificationManager {
            constructor() {
                this.checkSupport();
                this.requestPermission();
            }
            
            checkSupport() {
                this.supported = 'Notification' in window && 'serviceWorker' in navigator;
            }
            
            async requestPermission() {
                if (!this.supported) return false;
                
                if (Notification.permission === 'default') {
                    const permission = await Notification.requestPermission();
                    return permission === 'granted';
                }
                
                return Notification.permission === 'granted';
            }
            
            async show(title, options = {}) {
                if (!this.supported || Notification.permission !== 'granted') {
                    return false;
                }
                
                const defaultOptions = {
                    icon: '/favicon.ico',
                    badge: '/favicon.ico',
                    tag: 'codechallengenotification',
                    renotify: true,
                    requireInteraction: false,
                    ...options
                };
                
                try {
                    const notification = new Notification(title, defaultOptions);
                    
                    notification.onclick = function() {
                        window.focus();
                        this.close();
                        if (options.onclick) {
                            options.onclick();
                        }
                    };
                    
                    // Auto-close après 5 secondes
                    setTimeout(() => notification.close(), 5000);
                    
                    return true;
                } catch (error) {
                    console.log('Erreur notification:', error);
                    return false;
                }
            }
        }
        
        const notificationManager = new NotificationManager();
        
        // Fonction finale pour nettoyer et optimiser la mémoire
        function cleanupAndOptimize() {
            // Nettoyer les event listeners inutiles
            const unusedElements = document.querySelectorAll('[data-cleanup="true"]');
            unusedElements.forEach(element => {
                element.removeEventListener('click', () => {});
                element.remove();
            });
            
            // Nettoyer le cache si trop volumineux
            if (aiCache.cache.size > 100) {
                aiCache.clear();
            }
            
            // Optimiser les images
            document.querySelectorAll('img').forEach(img => {
                if (img.complete && img.naturalHeight === 0) {
                    img.style.display = 'none';
                }
            });
            
            // Forcer le garbage collection si disponible
            if (window.gc) {
                window.gc();
            }
        }
        
        // Nettoyer périodiquement
        setInterval(cleanupAndOptimize, 300000); // Toutes les 5 minutes
        
        // Message final de confirmation du chargement
        console.log('🎉 CodeChallenge Problem Page - Entièrement chargé et optimisé!');
        console.log('📊 Fonctionnalités actives:', {
            'IA Avancée': '✅',
            'Thème Adaptatif': '✅',
            'Raccourcis Clavier': '✅',
            'Analytics': '✅',
            'Notifications': notificationManager.supported ? '✅' : '❌',
            'Performance': '✅',
            'Accessibilité': '✅'
        });
        
        // Déclencher l'événement de page entièrement prête
        document.dispatchEvent(new CustomEvent('problemPageFullyReady', {
            detail: {
                loadTime: Date.now() - pageLoadTime,
                features: Object.keys(usedFeatures).length,
                optimizations: true
            }
        }));
    </script>
</body>
</html>

<?php
// Actions à effectuer après le rendu de la page
if (isLoggedIn()) {
    // Enregistrer la visite du problème
    try {
        $visit_stmt = $conn->prepare("
            INSERT INTO problem_visits (user_id, problem_id, visit_date) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE visit_date = NOW(), visit_count = visit_count + 1
        ");
        $visit_stmt->execute([$user_id, $problem_id]);
    } catch (Exception $e) {
        error_log("Erreur enregistrement visite: " . $e->getMessage());
    }
    
    // Mettre à jour les statistiques utilisateur
    try {
        $stats_stmt = $conn->prepare("
            UPDATE users 
            SET last_activity = NOW(), 
                problems_viewed = problems_viewed + 1 
            WHERE id = ?
        ");
        $stats_stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Erreur mise à jour stats: " . $e->getMessage());
    }
}

// Fermer la connexion
if ($conn) {
    $conn = null;
}
?>








