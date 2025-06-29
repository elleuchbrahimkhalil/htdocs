<?php
header("Access-Control-Allow-Origin: https://elleuchbrahimkhalil.github.io");
header("X-Frame-Options: ALLOW-FROM https://elleuchbrahimkhalil.github.io");

session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Configuration de la page
$page_title = "Problèmes récents - Analysés par IA";
$additional_css = "
    .publications-grid{
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 15px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }
    .publication-card{
        border: 1px solid #e0e6ed;
        border-radius: 12px;
        padding: 20px;
        background: #fff;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        position: relative;
        width: 100%;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .publication-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    .publication-header{display:flex;align-items:center;margin-bottom:15px}
    .author-avatar{width:45px;height:45px;border-radius:50%;margin-right:12px;object-fit:cover;border:3px solid #f0f4f8}
    .username{font-weight:600;color:#2c3e50;font-size:1.1em}
    .date{color:#7f8c8d;font-size:0.85em}
    .publication-title{font-weight:600;font-size:1.3em;margin:12px 0;color:#2c3e50;line-height:1.4}
    .publication-description{color:#4a5568;line-height:1.6;margin-bottom:18px;font-size:0.95em;max-height:80px;overflow:hidden;text-overflow:ellipsis}
    .publication-code{background:#f8fafc;padding:15px;border-radius:8px;font-family:monospace;overflow-x:auto;border:1px solid #e2e8f0;max-height:180px;font-size:0.9em;margin-bottom:15px;position:relative}
    .tags-container{display:flex;flex-wrap:wrap;gap:8px;margin:15px 0}
    .tag{padding:5px 12px;border-radius:20px;font-size:0.8em;background:#edf2f7;color:#4a5568;font-weight:500}
    
    /* Badges de difficulté avec style IA */
    .difficulty-easy{background:linear-gradient(135deg, #d5f5e3, #c3e6cb);color:#27ae60;border:1px solid #27ae60}
    .difficulty-medium{background:linear-gradient(135deg, #fef9e7, #fdeaa7);color:#f39c12;border:1px solid #f39c12}
    .difficulty-hard{background:linear-gradient(135deg, #fdedec, #f5c6cb);color:#e74c3c;border:1px solid #e74c3c}
    
    /* Section IA */
    .ai-analysis-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75em;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        z-index: 10;
    }
    
    .ai-info-section {
        background: linear-gradient(135deg, #f8f9ff, #f0f4ff);
        border: 1px solid #e1e8ff;
        border-radius: 8px;
        padding: 12px;
        margin: 12px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .ai-metrics {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .ai-metric {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 60px;
    }
    
    .ai-metric-value {
        font-weight: bold;
        font-size: 1.1em;
        color: #667eea;
    }
    
    .ai-metric-label {
        font-size: 0.7em;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 2px;
    }
    
    .confidence-indicator {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.7);
        padding: 6px 10px;
        border-radius: 15px;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }
    
    .confidence-bar {
        width: 50px;
        height: 4px;
        background: #e9ecef;
        border-radius: 2px;
        overflow: hidden;
    }
    
    .confidence-fill {
        height: 100%;
        background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #0abde3);
        border-radius: 2px;
        transition: width 0.8s ease-in-out;
    }
    
    .publication-footer{display:flex;justify-content:space-between;align-items:center;margin-top:15px;padding-top:15px;border-top:1px solid #f0f4f8}
    .publication-stats{display:flex;gap:15px;color:#718096;font-size:0.85em;flex-wrap:wrap}
    .favorite-btn{position:absolute;top:60px;right:15px;background:none;border:none;font-size:1.3em;color:#cbd5e0;cursor:pointer;transition:all .3s;z-index:5}
    .favorite-btn:hover{transform:scale(1.15)}
    .favorite-btn.active{color:#EC4899}
    .favorite-btn.loading{color:#ffc107;transform:scale(1.1)}
    
    .status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    .unsolved {
        background-color: #e74c3c;
        box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
    }
    .solved {
        background-color: #27ae60;
        box-shadow: 0 0 0 2px rgba(39, 174, 96, 0.2);
    }
    .action-buttons { display: flex; gap: 12px; flex-wrap: wrap; }
    .btn { padding: 10px 16px; border-radius: 6px; text-decoration: none; font-size: 0.9em; font-weight: 500; transition: all 0.3s; }
    .btn-primary { background: #4299e1; color: white; border: 1px solid #4299e1; }
    .btn-success { background: #48bb78; color: white; border: 1px solid #48bb78; }
    .btn:hover { opacity: 0.9; transform: translateY(-1px); }
    
    /* Indicateur de complexité */
    .complexity-indicator {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75em;
        color: #667eea;
        background: rgba(102, 126, 234, 0.1);
        padding: 3px 8px;
        border-radius: 12px;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }
    
    /* Animation pour les nouvelles cartes */
    .publication-card.new-entry {
        animation: slideInUp 0.6s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Responsive amélioré */
    @media (max-width: 768px) {
        .ai-info-section {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .ai-metrics {
            width: 100%;
            justify-content: space-around;
        }
        
        .confidence-indicator {
            width: 100%;
            justify-content: center;
        }
        
        .ai-analysis-badge {
            position: relative;
            top: 0;
            right: 0;
            margin-bottom: 10px;
            align-self: flex-start;
        }
    }
";

$conn = connect();
if ($conn === null) die("Database connection failed.");
if (!isLoggedIn()) header('Location: login.php');

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();

// Préparer les messages pour header.php
$_SESSION['success_message'] = $_SESSION['success_message'] ?? '';
$_SESSION['error_message'] = $_SESSION['error_message'] ?? '';

// Récupérer les publications avec analyse IA
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id) AS favorite_count,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite,
               (SELECT COUNT(*) FROM solutions WHERE problem_id = p.problem_id AND user_id = ?) AS has_solution,
               p.ai_confidence,
               p.error_count,
               p.complexity_score,
               JSON_EXTRACT(p.ai_analysis, '$.analysis.complexity_factors') as complexity_factors,
               JSON_EXTRACT(p.ai_analysis, '$.analysis.error_types') as error_types
        FROM problems p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user['id'], $user['id']]);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error: ".$e->getMessage());
    $publications = [];
}

// Inclure l'en-tête qui contient la structure de base et la sidebar
include 'header.php';
?>

<h2><i class="fas fa-robot"></i> Problèmes récents - Analysés par IA</h2>

<div class="publications-grid">
    <?php foreach($publications as $pub): ?>
    <div class="publication-card">
        <!-- Badge IA -->
        <?php if (!empty($pub['ai_confidence'])): ?>
        <div class="ai-analysis-badge">
            <i class="fas fa-robot"></i>
            IA <?= round($pub['ai_confidence'] * 100) ?>%
        </div>
        <?php endif; ?>
        
        <button class="favorite-btn <?= $pub['is_favorite'] > 0 ? 'active' : '' ?>" 
                data-problem-id="<?= htmlspecialchars($pub['problem_id']) ?>"
                title="<?= $pub['is_favorite'] > 0 ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
            <i class="fas fa-heart"></i>
        </button>
        
        <div class="publication-header">
            <img src="<?= htmlspecialchars($pub['avatar_url']??'default.png') ?>" 
                 class="author-avatar" alt="Avatar">
            <div>
                <div class="username"><?= htmlspecialchars($pub['username']) ?></div>
                <div class="date"><?= date('d/m/Y H:i', strtotime($pub['created_at'])) ?></div>
            </div>
        </div>
        
        <div>
            <div class="status-dot <?= $pub['has_solution'] ? 'solved' : 'unsolved'; ?>"></div>
            <h3 class="publication-title"><?= htmlspecialchars($pub['title']) ?></h3>
        </div>
        
        <!-- Section d'analyse IA -->
        <?php if (!empty($pub['ai_confidence'])): ?>
        <div class="ai-info-section">
            <div class="ai-metrics">
                <div class="ai-metric">
                    <div class="ai-metric-value"><?= htmlspecialchars($pub['points']) ?></div>
                    <div class="ai-metric-label">Points</div>
                </div>
                
                <?php if (!empty($pub['error_count'])): ?>
                <div class="ai-metric">
                    <div class="ai-metric-value"><?= htmlspecialchars($pub['error_count']) ?></div>
                    <div class="ai-metric-label">Erreurs</div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($pub['complexity_score'])): ?>
                <div class="ai-metric">
                    <div class="ai-metric-value"><?= htmlspecialchars($pub['complexity_score']) ?></div>
                    <div class="ai-metric-label">Complexité</div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="confidence-indicator">
                <span style="font-size: 0.75em; font-weight: 600;">Confiance IA</span>
                <div class="confidence-bar">
                    <div class="confidence-fill" style="width: <?= $pub['ai_confidence'] * 100 ?>%"></div>
                </div>
                <span style="font-size: 0.75em; font-weight: bold;"><?= round($pub['ai_confidence'] * 100) ?>%</span>
            </div>
        </div>
        
        <!-- Facteurs de complexité si disponibles -->
        <?php if (!empty($pub['complexity_factors'])): ?>
            <?php
            $complexityFactors = json_decode($pub['complexity_factors'], true);
            if (is_array($complexityFactors) && !empty($complexityFactors)):
            ?>
            <div style="margin: 10px 0;">
                <?php foreach (array_slice($complexityFactors, 0, 3) as $factor): ?>
                    <span class="complexity-indicator">
                        <i class="fas fa-cog"></i>
                        <?= htmlspecialchars($factor) ?>
                    </span>
                <?php endforeach; ?>
                <?php if (count($complexityFactors) > 3): ?>
                    <span class="complexity-indicator" title="<?= implode(', ', array_slice($complexityFactors, 3)) ?>">
                        <i class="fas fa-plus"></i>
                        +<?= count($complexityFactors) - 3 ?> autres
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
        
        <div class="publication-description">
            <?= nl2br(htmlspecialchars(substr($pub['description'], 0, 120).(strlen($pub['description']) > 120 ? '...' : ''))) ?>
        </div>
        
        <?php if(!empty($pub['code'])): ?>
            <div class="publication-code">
                <div style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.7); color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.7em;">
                    <?= htmlspecialchars(strtoupper($pub['language'])) ?>
                </div>
                <pre><?= htmlspecialchars(substr($pub['code'], 0, 300) . (strlen($pub['code']) > 300 ? '...' : '')) ?></pre>
            </div>
        <?php endif; ?>
        
        <div class="tags-container">
            <span class="difficulty-<?= htmlspecialchars($pub['difficulty']) ?> tag">
                <i class="fas fa-signal"></i>
                <?= ucfirst(htmlspecialchars($pub['difficulty'])) ?>
                <?php if (!empty($pub['ai_confidence'])): ?>
                    <small>(IA: <?= round($pub['ai_confidence'] * 100) ?>%)</small>
                <?php endif; ?>
            </span>
            <?php if(!empty($pub['tags'])): ?>
                <?php foreach(explode(',', $pub['tags']) as $tag): ?>
                    <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="publication-footer">
            <div class="publication-stats">
                <span><i class="fas fa-code"></i> <?= htmlspecialchars(ucfirst($pub['language'])) ?></span>
                <span><i class="fas fa-check-circle"></i> <?= htmlspecialchars($pub['solution_count']??0) ?> résolutions</span>
                <span><i class="fas fa-heart"></i> <?= htmlspecialchars($pub['favorite_count']) ?> favoris</span>
                <?php if (!empty($pub['ai_confidence'])): ?>
                <span><i class="fas fa-robot"></i> Analysé par IA</span>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <a href="problem.php?id=<?= htmlspecialchars($pub['problem_id']) ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-eye"></i> Voir
                </a>
                <a href="submit_solution.php?problem_id=<?= htmlspecialchars($pub['problem_id']) ?>" 
                   class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Soumettre
                </a>
                <?php if (!empty($pub['ai_confidence'])): ?>
                <button onclick="showAIDetails(<?= htmlspecialchars($pub['problem_id']) ?>)" 
                        class="btn" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                    <i class="fas fa-robot"></i> Détails IA
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal pour les détails IA -->
<div id="aiDetailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; backdrop-filter: blur(5px);">
    <div style="position: relative; width: 90%; max-width: 600px; margin: 50px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
        <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-robot"></i>
                Analyse IA Détaillée
            </h3>
            <button onclick="closeAIModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">×</button>
        </div>
        <div id="aiDetailsContent" style="padding: 20px; max-height: 70vh; overflow-y: auto;">
            <!-- Contenu chargé dynamiquement -->
        </div>
    </div>
</div>

<?php
// Ajout du script spécifique pour cette page avec fonctionnalités IA
$additional_scripts = "
    // Données des analyses IA pour accès JavaScript
    const aiAnalysisData = " . json_encode(array_map(function($pub) {
        return [
            'problem_id' => $pub['problem_id'],
            'ai_confidence' => $pub['ai_confidence'],
            'error_count' => $pub['error_count'],
            'complexity_score' => $pub['complexity_score'],
            'complexity_factors' => json_decode($pub['complexity_factors'] ?? '[]', true),
            'error_types' => json_decode($pub['error_types'] ?? '[]', true),
            'difficulty' => $pub['difficulty'],
            'points' => $pub['points']
        ];
    }, $publications)) . ";
    
    // Fonction pour afficher les détails IA
    function showAIDetails(problemId) {
        const aiData = aiAnalysisData.find(data => data.problem_id == problemId);
        if (!aiData) {
            showMessage('Données IA non disponibles', 'error');
            return;
        }
        
        const modal = document.getElementById('aiDetailsModal');
        const content = document.getElementById('aiDetailsContent');
        
        let html = `
            <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;'>
                <div style='text-align: center; padding: 15px; background: linear-gradient(135deg, #f8f9ff, #f0f4ff); border-radius: 8px; border: 1px solid #e1e8ff;'>
                    <div style='font-size: 24px; font-weight: bold; color: #667eea;'>\${Math.round(aiData.ai_confidence * 100)}%</div>
                    <div style='font-size: 12px; color: #6c757d; text-transform: uppercase;'>Confiance IA</div>
                </div>
                <div style='text-align: center; padding: 15px; background: linear-gradient(135deg, #f8f9ff, #f0f4ff); border-radius: 8px; border: 1px solid #e1e8ff;'>
                    <div style='font-size: 24px; font-weight: bold; color: #667eea;'>\${aiData.points || 'N/A'}</div>
                    <div style='font-size: 12px; color: #6c757d; text-transform: uppercase;'>Points Attribués</div>
                </div>
                <div style='text-align: center; padding: 15px; background: linear-gradient(135deg, #f8f9ff, #f0f4ff); border-radius: 8px; border: 1px solid #e1e8ff;'>
                    <div style='font-size: 24px; font-weight: bold; color: #667eea;'>\${aiData.error_count || 0}</div>
                    <div style='font-size: 12px; color: #6c757d; text-transform: uppercase;'>Erreurs Détectées</div>
                </div>
                <div style='text-align: center; padding: 15px; background: linear-gradient(135deg, #f8f9ff, #f0f4ff); border-radius: 8px; border: 1px solid #e1e8ff;'>
                    <div style='font-size: 24px; font-weight: bold; color: #667eea;'>\${aiData.complexity_score || 'N/A'}</div>
                    <div style='font-size: 12px; color: #6c757d; text-transform: uppercase;'>Score Complexité</div>
                </div>
            </div>
        `;
        
        if (aiData.complexity_factors && aiData.complexity_factors.length > 0) {
            html += `
                <div style='margin: 20px 0;'>
                    <h4 style='color: #2c3e50; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;'>
                        <i class='fas fa-cogs'></i> Facteurs de Complexité Détectés
                    </h4>
                    <div style='display: flex; flex-wrap: wrap; gap: 8px;'>
            `;
            aiData.complexity_factors.forEach(factor => {
                html += `<span style='background: #e8f4fd; color: #0c5460; padding: 6px 12px; border-radius: 15px; font-size: 0.85em; border: 1px solid #bee5eb;'>\${factor}</span>`;
            });
            html += `</div></div>`;
        }
        
        if (aiData.error_types && aiData.error_types.length > 0) {
            html += `
                <div style='margin: 20px 0;'>
                    <h4 style='color: #2c3e50; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;'>
                        <i class='fas fa-exclamation-triangle'></i> Types d'Erreurs Identifiés
                    </h4>
                    <div style='display: flex; flex-wrap: wrap; gap: 8px;'>
            `;
            aiData.error_types.forEach(errorType => {
                html += `<span style='background: #f8d7da; color: #721c24; padding: 6px 12px; border-radius: 15px; font-size: 0.85em; border: 1px solid #f5c6cb;'>\${errorType}</span>`;
            });
            html += `</div></div>`;
        }
        
        // Recommandations basées sur l'analyse
        html += `
            <div style='margin: 20px 0; padding: 15px; background: #d1ecf1; border-radius: 8px; border-left: 4px solid #0c5460;'>
                <h4 style='color: #0c5460; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;'>
                    <i class='fas fa-lightbulb'></i> Recommandations IA
                </h4>
                <ul style='margin: 0; padding-left: 20px; color: #0c5460;'>
        `;
        
        if (aiData.ai_confidence < 0.7) {
            html += `<li>Confiance IA faible - Révision manuelle recommandée</li>`;
        }
        if (aiData.error_count > 3) {
            html += `<li>Nombre d'erreurs élevé - Vérification approfondie nécessaire</li>`;
        }
        if (aiData.complexity_score > 7) {
            html += `<li>Complexité élevée - Problème avancé nécessitant expertise</li>`;
        }
        if (aiData.difficulty === 'hard' && aiData.ai_confidence > 0.8) {
            html += `<li>Problème difficile bien analysé - Excellent pour l'apprentissage</li>`;
        }
        
        html += `
                </ul>
            </div>
            <div style='text-align: center; margin-top: 20px;'>
                <button onclick='closeAIModal()' style='background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;'>
                    Fermer
                </button>
            </div>
        `;
        
        content.innerHTML = html;
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    // Fermer la modal IA
    function closeAIModal() {
        document.getElementById('aiDetailsModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Fermer la modal en cliquant à l'extérieur
    document.getElementById('aiDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAIModal();
        }
    });
    
    // Gestion des favoris - VERSION CORRIGÉE avec support IA
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const problemId = this.dataset.problemId;
            const isCurrentlyActive = this.classList.contains('active');
            const heartIcon = this.querySelector('i');
            
            // Désactiver le bouton pendant la requête
            this.disabled = true;
            this.classList.add('loading');
            heartIcon.className = 'fas fa-spinner fa-spin';
            
            // Préparer les données pour la requête
            const formData = new FormData();
            formData.append('problem_id', problemId);
            formData.append('favorite_action', isCurrentlyActive ? 'remove' : 'add');
            
            // Envoyer la requête vers manage_favorites.php
            fetch('manage_favorites.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Succès - changer l'état du bouton
                    if (data.action === 'removed') {
                        // Retirer des favoris
                        this.classList.remove('active');
                        this.title = 'Ajouter aux favoris';
                        
                        // Décrémenter le compteur de favoris
                        const statsSpan = this.closest('.publication-card').querySelector('.publication-stats span:nth-child(3)');
                        if (statsSpan) {
                            const currentCount = parseInt(statsSpan.textContent.match(/\\d+/)[0]);
                            statsSpan.innerHTML = '<i class=\"fas fa-heart\"></i> ' + Math.max(0, currentCount - 1) + ' favoris';
                        }
                        
                        showMessage('Retiré des favoris!', 'info');
                    } else if (data.action === 'added') {
                        // Ajouter aux favoris
                        this.classList.add('active');
                        this.title = 'Retirer des favoris';
                        
                        // Incrémenter le compteur de favoris
                        const statsSpan = this.closest('.publication-card').querySelector('.publication-stats span:nth-child(3)');
                        if (statsSpan) {
                            const currentCount = parseInt(statsSpan.textContent.match(/\\d+/)[0]);
                            statsSpan.innerHTML = '<i class=\"fas fa-heart\"></i> ' + (currentCount + 1) + ' favoris';
                        }
                        
                        showMessage('Ajouté aux favoris!', 'success');
                    }
                    
                    // Animation
                    heartIcon.style.transform = 'scale(1.3)';
                    setTimeout(() => {
                        heartIcon.style.transform = '';
                    }, 200);
                } else {
                    throw new Error(data.message || 'Erreur serveur');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur: ' + error.message, 'error');
            })
            .finally(() => {
                // Réactiver le bouton
                this.disabled = false;
                this.classList.remove('loading');
                heartIcon.className = 'fas fa-heart';
            });
        });
    });
    
    // Fonction pour afficher des messages avec style IA
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.padding = '15px 20px';
        messageDiv.style.borderRadius = '8px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.maxWidth = '350px';
        messageDiv.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
        messageDiv.style.transition = 'all 0.3s ease';
        messageDiv.style.backdropFilter = 'blur(10px)';
        messageDiv.style.fontWeight = '500';
        
        switch(type) {
            case 'success':
                messageDiv.style.background = 'linear-gradient(135deg, #d4edda, #c3e6cb)';
                messageDiv.style.color = '#155724';
                messageDiv.style.border = '1px solid #c3e6cb';
                messageDiv.innerHTML = '✅ ' + message;
                break;
            case 'error':
                messageDiv.style.background = 'linear-gradient(135deg, #f8d7da, #f5c6cb)';
                messageDiv.style.color = '#721c24';
                messageDiv.style.border = '1px solid #f5c6cb';
                messageDiv.innerHTML = '❌ ' + message;
                break;
            case 'info':
                messageDiv.style.background = 'linear-gradient(135deg, #d1ecf1, #bee5eb)';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = 'ℹ️ ' + message;
                break;
            default:
                messageDiv.style.background = 'linear-gradient(135deg, #f8f9ff, #f0f4ff)';
                messageDiv.style.color = '#667eea';
                messageDiv.style.border = '1px solid #e1e8ff';
                messageDiv.innerHTML = '🤖 ' + message;
        }
        
        document.body.appendChild(messageDiv);
        
        // Animation d'entrée
        messageDiv.style.transform = 'translateX(100%)';
        setTimeout(() => {
            messageDiv.style.transform = 'translateX(0)';
        }, 100);
        
        // Supprimer le message après 4 secondes
        setTimeout(() => {
            messageDiv.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 4000);
    }
    
    // Animation des cartes avec effet IA
    document.querySelectorAll('.publication-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.boxShadow = '0 8px 25px rgba(102, 126, 234, 0.15)';
            
            // Animation spéciale pour les cartes avec IA
            const aiBadge = card.querySelector('.ai-analysis-badge');
            if (aiBadge) {
                aiBadge.style.transform = 'scale(1.05)';
                aiBadge.style.boxShadow = '0 4px 15px rgba(102, 126, 234, 0.4)';
            }
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.boxShadow = '0 3px 15px rgba(0,0,0,0.08)';
            
            const aiBadge = card.querySelector('.ai-analysis-badge');
            if (aiBadge) {
                aiBadge.style.transform = 'scale(1)';
                aiBadge.style.boxShadow = '0 2px 8px rgba(102, 126, 234, 0.3)';
            }
        });
    });
    
    // Animation d'apparition des cartes avec délai progressif
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.publication-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
                card.classList.add('new-entry');
            }, index * 150);
        });
        
        // Animation des barres de confiance IA
        setTimeout(() => {
            const confidenceBars = document.querySelectorAll('.confidence-fill');
            confidenceBars.forEach(bar => {
                const targetWidth = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = targetWidth;
                }, 100);
            });
        }, 1000);
    });
    
    // Gestion des erreurs de chargement d'images
    document.addEventListener('DOMContentLoaded', function() {
        const avatars = document.querySelectorAll('.author-avatar');
        avatars.forEach(avatar => {
            avatar.addEventListener('error', function() {
                this.src = 'default.png';
            });
        });
    });
    
    // Fonction pour filtrer par confiance IA
    function filterByAIConfidence(minConfidence = 0) {
        const cards = document.querySelectorAll('.publication-card');
        cards.forEach(card => {
            const aiBadge = card.querySelector('.ai-analysis-badge');
            if (aiBadge) {
                const confidence = parseInt(aiBadge.textContent.match(/\\d+/)[0]) / 100;
                if (confidence >= minConfidence) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            } else if (minConfidence === 0) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        
        showMessage(`Filtrage appliqué: confiance IA ≥ \${Math.round(minConfidence * 100)}%`, 'ai');
    }
    
    // Ajouter des boutons de filtrage IA
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.querySelector('h2');
        if (header) {
            const filterContainer = document.createElement('div');
            filterContainer.style.marginTop = '15px';
            filterContainer.style.display = 'flex';
            filterContainer.style.gap = '10px';
            filterContainer.style.flexWrap = 'wrap';
            filterContainer.style.alignItems = 'center';
            
            const filterLabel = document.createElement('span');
            filterLabel.textContent = 'Filtrer par confiance IA:';
            filterLabel.style.fontWeight = '600';
            filterLabel.style.color = '#667eea';
            filterContainer.appendChild(filterLabel);
            
            const filters = [
                { label: 'Tous', value: 0 },
                { label: '≥ 50%', value: 0.5 },
                { label: '≥ 70%', value: 0.7 },
                { label: '≥ 90%', value: 0.9 }
            ];
            
            filters.forEach(filter => {
                const btn = document.createElement('button');
                btn.textContent = filter.label;
                btn.style.padding = '6px 12px';
                btn.style.border = '1px solid #667eea';
                btn.style.borderRadius = '15px';
                btn.style.background = filter.value === 0 ? '#667eea' : 'white';
                btn.style.color = filter.value === 0 ? 'white' : '#667eea';
                btn.style.cursor = 'pointer';
                btn.style.fontSize = '0.85em';
                btn.style.fontWeight = '500';
                btn.style.transition = 'all 0.3s';
                
                btn.addEventListener('click', () => {
                    // Réinitialiser tous les boutons
                    filterContainer.querySelectorAll('button').forEach(b => {
                        b.style.background = 'white';
                        b.style.color = '#667eea';
                    });
                    
                    // Activer le bouton cliqué
                    btn.style.background = '#667eea';
                    btn.style.color = 'white';
                    
                    // Appliquer le filtre
                    filterByAIConfidence(filter.value);
                });
                
                btn.addEventListener('mouseenter', () => {
                    if (btn.style.background !== 'rgb(102, 126, 234)') {
                        btn.style.background = '#f8f9ff';
                    }
                });
                
                btn.addEventListener('mouseleave', () => {
                    if (btn.style.background !== 'rgb(102, 126, 234)') {
                        btn.style.background = 'white';
                    }
                });
                
                filterContainer.appendChild(btn);
            });
            
            header.parentNode.insertBefore(filterContainer, header.nextSibling);
        }
    });
    
    // Fonction pour exporter les données IA (pour les administrateurs)
    function exportAIData() {
        const aiData = aiAnalysisData.filter(data => data.ai_confidence > 0);
        const csvContent = 'data:text/csv;charset=utf-8,' + 
            'Problem ID,AI Confidence,Error Count,Complexity Score,Difficulty,Points\\n' +
            aiData.map(data => 
                `\${data.problem_id},\${data.ai_confidence},\${data.error_count || 0},\${data.complexity_score || 0},\${data.difficulty},\${data.points}`
            ).join('\\n');
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'ai_analysis_data.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showMessage('Données IA exportées!', 'success');
    }
    
    // Raccourci clavier pour les fonctionnalités IA (Ctrl+Shift+A)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'A') {
            e.preventDefault();
            const hasAIData = aiAnalysisData.some(data => data.ai_confidence > 0);
            if (hasAIData) {
                showMessage('Fonctionnalités IA disponibles! Utilisez les boutons \"Détails IA\" sur les cartes.', 'ai');
            } else {
                showMessage('Aucune analyse IA disponible pour le moment.', 'info');
            }
        }
    });
    
    console.log('🤖 Interface IA chargée avec', aiAnalysisData.filter(d => d.ai_confidence > 0).length, 'analyses disponibles');
";

// Inclure le pied de page
include 'footer.php';
?>

<?php if (isset($_SESSION['solution_submitted']) && $_SESSION['solution_submitted']): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sélectionner l'indicateur correspondant au problème
        var indicators = document.querySelectorAll('.status-dot[data-problem-id="<?php echo $_SESSION['submitted_problem_id']; ?>"]');
        indicators.forEach(function(indicator) {
            indicator.classList.remove('unsolved');
            indicator.classList.add('solved');
        });
        
        // Message de succès avec mention IA
        showMessage('Solution soumise! Elle sera analysée par notre IA.', 'success');
    });
</script>
<?php 
    // Nettoyer les variables de session
    unset($_SESSION['solution_submitted']);
    unset($_SESSION['submitted_problem_id']);
endif; 
?>
