<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'solution_scorer.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Vérifier si un ID de paiement est fourni
$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

if ($payment_id <= 0) {
    header('Location: user_feedback.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer les détails du paiement avec analyse IA
try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    $stmt = $pdo->prepare("
        SELECT p.*, s.solution_code, s.explanation, 
               pr.title as problem_title, pr.description as problem_description,
               pr.difficulty, pr.points as base_points, pr.ai_analysis, pr.ai_confidence,
               u.username as solver_username, u.name as solver_name,
               ss.final_score, ss.bonus_first_accepted, ss.bonus_persistence
        FROM payments p
        JOIN solutions s ON p.solution_id = s.id
        JOIN problems pr ON s.problem_id = pr.problem_id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN solution_scores ss ON s.id = ss.solution_id
        WHERE p.payment_id = ? AND p.payer_id = ?
    ");
    
    $stmt->execute([$payment_id, $user_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception("Paiement non trouvé");
    }
    
    // Décoder l'analyse IA si disponible
    $ai_analysis = null;
    if (!empty($payment['ai_analysis'])) {
        $ai_analysis = json_decode($payment['ai_analysis'], true);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du paiement: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors de la récupération des détails du paiement";
    header('Location: user_feedback.php');
    exit;
}

// Configuration de la page
$page_title = "Paiement Réussi - Solution IA Analysée";

// CSS amélioré avec informations IA
$additional_css = "
    .success-container {
        max-width: 900px;
        margin: 0 auto;
        text-align: center;
    }

    .ai-analysis-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 25px;
        margin: 25px 0;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .ai-analysis-title {
        font-size: 1.4em;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .ai-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }

    .ai-stat-card {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 15px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .ai-stat-value {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .ai-stat-label {
        font-size: 12px;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .score-breakdown {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }

    .score-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .score-item:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 1.1em;
        color: #28a745;
    }

    .score-description {
        color: #6c757d;
        font-size: 0.9em;
    }

    .score-value {
        color: #28a745;
        font-weight: 600;
    }

    .difficulty-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
    }

    .difficulty-easy { background: #d5f5e3; color: #27ae60; }
    .difficulty-medium { background: #fef9e7; color: #f39c12; }
    .difficulty-hard { background: #fdedec; color: #e74c3c; }

    .confidence-meter {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        height: 8px;
        margin: 10px 0;
        overflow: hidden;
    }

    .confidence-fill {
        height: 100%;
        background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #0abde3);
        border-radius: 10px;
        transition: width 1s ease-in-out;
    }

    .solution-quality-indicator {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 15px 0;
        padding: 15px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
    }

    .quality-stars {
        color: #ffd700;
        font-size: 18px;
    }

    /* Styles existants améliorés */
    .success-icon {
        font-size: 4em;
        color: #27ae60;
        margin-bottom: 20px;
        animation: checkmark 0.6s ease-in-out, pulse 2s infinite 1s;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .payment-details {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        text-align: left;
        border-left: 4px solid #3498db;
    }

    .solution-preview {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        margin: 25px 0;
        border-left: 4px solid #27ae60;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }

    .solution-code {
        background: #2c3e50;
        color: #ecf0f1;
        padding: 20px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        overflow-x: auto;
        margin: 15px 0;
        line-height: 1.6;
        position: relative;
    }

    .code-language-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(52, 152, 219, 0.8);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        text-transform: uppercase;
    }

    /* Responsive amélioré */
    @media (max-width: 768px) {
        .ai-stats-grid {
            grid-template-columns: 1fr;
        }
        
        .score-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
";

// Inclure l'en-tête
include 'header.php';
?>

<div class="success-container">
    <!-- Animation de confettis -->
    <div class="confetti" id="confetti"></div>
    
    <div class="success-icon">
        <i class="fas fa-check-circle"></i>
    </div>
    
    <h1 class="success-title">Paiement Réussi !</h1>
    
    <p class="success-message">
        Félicitations ! Votre paiement a été traité avec succès.<br>
        Vous avez maintenant accès à cette solution analysée par l'IA.
    </p>

    <!-- Section d'analyse IA -->
    <?php if ($ai_analysis): ?>
    <div class="ai-analysis-section">
        <div class="ai-analysis-title">
            <i class="fas fa-robot"></i>
            Analyse IA de cette Solution
        </div>
        
        <div class="ai-stats-grid">
            <div class="ai-stat-card">
                <div class="ai-stat-value">
                    <span class="difficulty-badge difficulty-<?= htmlspecialchars($payment['difficulty']) ?>">
                        <?php 
                        $difficulty_labels = ['easy' => 'Facile', 'medium' => 'Moyen', 'hard' => 'Difficile'];
                        echo $difficulty_labels[$payment['difficulty']] ?? ucfirst($payment['difficulty']);
                        ?>
                    </span>
                </div>
                <div class="ai-stat-label">Difficulté IA</div>
            </div>
            
            <div class="ai-stat-card">
                <div class="ai-stat-value"><?= htmlspecialchars($payment['base_points']) ?> pts</div>
                <div class="ai-stat-label">Score de Base</div>
            </div>
            
            <div class="ai-stat-card">
                <div class="ai-stat-value">
                    <?= isset($ai_analysis['analysis']['error_count']) ? $ai_analysis['analysis']['error_count'] : 'N/A' ?>
                </div>
                <div class="ai-stat-label">Erreurs Détectées</div>
            </div>
            
            <div class="ai-stat-card">
                <div class="ai-stat-value"><?= round($payment['ai_confidence'] * 100, 1) ?>%</div>
                <div class="ai-stat-label">Confiance IA</div>
                <div class="confidence-meter">
                    <div class="confidence-fill" style="width: <?= $payment['ai_confidence'] * 100 ?>%"></div>
                </div>
            </div>
        </div>

        <?php if (isset($ai_analysis['analysis']['complexity_factors']) && !empty($ai_analysis['analysis']['complexity_factors'])): ?>
        <div class="solution-quality-indicator">
            <div class="quality-stars">
                <?php 
                $stars = min(5, max(1, ceil($payment['ai_confidence'] * 5)));
                for ($i = 0; $i < $stars; $i++) echo '⭐';
                ?>
            </div>
            <div>
                <strong>Facteurs de complexité détectés :</strong><br>
                <small><?= implode(', ', array_slice($ai_analysis['analysis']['complexity_factors'], 0, 3)) ?></small>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Détails du paiement avec score -->
    <div class="payment-details">
        <h3><i class="fas fa-receipt"></i> Détails du Paiement</h3>
        
        <div class="detail-row">
            <span class="detail-label">Numéro de transaction :</span>
            <span class="detail-value"><?= htmlspecialchars($payment['transaction_id']) ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Problème :</span>
            <span class="detail-value"><?= htmlspecialchars($payment['problem_title']) ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Développeur :</span>
            <span class="detail-value"><?= htmlspecialchars($payment['solver_name']) ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Date du paiement :</span>
            <span class="detail-value"><?= date('d/m/Y à H:i', strtotime($payment['payment_date'])) ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Montant payé :</span>
            <span class="detail-value"><?= number_format($payment['amount'], 2) ?> <?= htmlspecialchars($payment['currency']) ?></span>
        </div>

        <!-- Breakdown du score si disponible -->
        <?php if ($payment['final_score']): ?>
        <div class="score-breakdown">
            <h4><i class="fas fa-calculator"></i> Détail du Score</h4>
            
            <div class="score-item">
                <div>
                    <strong>Score de base (IA)</strong>
                    <div class="score-description">Déterminé par l'analyse automatique</div>
                </div>
                <span class="score-value">+<?= $payment['base_points'] ?> pts</span>
            </div>
            
            <?php if ($payment['bonus_first_accepted'] > 0): ?>
            <div class="score-item">
                <div>
                    <strong>Bonus première solution</strong>
                    <div class="score-description">Première solution acceptée pour ce problème</div>
                </div>
                <span class="score-value">+<?= $payment['bonus_first_accepted'] ?> pts</span>
            </div>
            <?php endif; ?>
            
            <?php if ($payment['bonus_persistence'] > 0): ?>
            <div class="score-item">
                <div>
                    <strong>Bonus persévérance</strong>
                    <div class="score-description">Récompense pour les tentatives précédentes</div>
                </div>
                <span class="score-value">+<?= $payment['bonus_persistence'] ?> pts</span>
            </div>
            <?php endif; ?>
            
            <div class="score-item">
                <div><strong>Score Total Gagné</strong></div>
                <span class="score-value"><?= $payment['final_score'] ?> points</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Aperçu de la solution avec métadonnées IA -->
    <div class="solution-preview">
        <h3><i class="fas fa-code"></i> Solution Achetée</h3>
        
        <div class="solution-code">
            <div class="code-language-badge"><?= htmlspecialchars(strtoupper($payment['language'] ?? 'CODE')) ?></div>
            <pre><?= htmlspecialchars($payment['solution_code']) ?></pre>
        </div>
        
        <?php if (!empty($payment['explanation'])): ?>
            <div class="solution-explanation">
                <h4><i class="fas fa-comment-alt"></i> Explication du développeur</h4>
                <?= nl2br(htmlspecialchars($payment['explanation'])) ?>
            </div>
        <?php endif; ?>

        <?php if ($ai_analysis && isset($ai_analysis['report']['recommendations'])): ?>
            <div class="ai-recommendations" style="background: #e8f4fd; padding: 15px; border-radius: 8px; margin-top: 15px;">
                <h4><i class="fas fa-lightbulb"></i> Recommandations IA</h4>
                <ul style="text-align: left; margin: 10px 0;">
                    <?php foreach ($ai_analysis['report']['recommendations'] as $recommendation): ?>
                        <li><?= htmlspecialchars($recommendation) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Boutons d'action améliorés -->
    <div class="action-buttons">
        <button onclick="downloadSolutionWithMetadata()" class="btn btn-success">
            <i class="fas fa-download"></i> Télécharger avec Métadonnées IA
        </button>
        
        <a href="problem.php?id=<?= $payment['problem_id'] ?>" class="btn btn-primary">
            <i class="fas fa-eye"></i> Voir le Problème
        </a>
        
        <button onclick="copyToClipboard()" class="btn btn-outline">
            <i class="fas fa-copy"></i> Copier la Solution
        </button>
        
        <a href="user_feedback.php" class="btn btn-outline">
            <i class="fas fa-list"></i> Mes Achats
        </a>
        
        <a href="exacueil.php" class="btn btn-outline">
            <i class="fas fa-home"></i> Accueil
        </a>
    </div>
</div>

<?php
// Scripts JavaScript améliorés
$additional_scripts = "
    // Fonction de téléchargement avec métadonnées IA
    function downloadSolutionWithMetadata() {
        const solutionCode = `" . addslashes($payment['solution_code']) . "`;
        const problemTitle = `" . addslashes($payment['problem_title']) . "`;
        const solverName = `" . addslashes($payment['solver_name']) . "`;
        const explanation = `" . addslashes($payment['explanation'] ?? '') . "`;
        const difficulty = `" . addslashes($payment['difficulty']) . "`;
        const aiConfidence = `" . round($payment['ai_confidence'] * 100, 1) . "`;
        const baseScore = `" . $payment['base_points'] . "`;
        const finalScore = `" . ($payment['final_score'] ?? $payment['base_points']) . "`;
        
        let content = `/*\\n`;
        content += `=== SOLUTION ANALYSÉE PAR IA ===\\n`;
        content += `Problème: \${problemTitle}\\n`;
        content += `Développeur: \${solverName}\\n`;
        content += `Date d'achat: " . date('d/m/Y à H:i') . "\\n`;
        content += `Transaction ID: " . htmlspecialchars($payment['transaction_id']) . "\\n\\n`;
        
        content += `=== ANALYSE IA ===\\n`;
        content += `Difficulté détectée: \${difficulty}\\n`;
        content += `Score de base: \${baseScore} points\\n`;
        content += `Score final: \${finalScore} points\\n`;
        content += `Confiance IA: \${aiConfidence}%\\n\\n`;
        
        " . (isset($ai_analysis['analysis']['complexity_factors']) ? "
        content += `Facteurs de complexité:\\n`;
        " . json_encode($ai_analysis['analysis']['complexity_factors']) . ".forEach(factor => {
            content += `- \${factor}\\n`;
        });
        content += `\\n`;
        " : "") . "
        
        " . (isset($ai_analysis['analysis']['error_types']) && !empty($ai_analysis['analysis']['error_types']) ? "
        content += `Types d'erreurs détectés:\\n`;
        " . json_encode($ai_analysis['analysis']['error_types']) . ".forEach(error => {
            content += `- \${error}\\n`;
        });
        content += `\\n`;
        " : "") . "
        
        if (explanation) {
            content += `=== EXPLICATION DU DÉVELOPPEUR ===\\n`;
            content += `\${explanation}\\n\\n`;
        }
        
        " . (isset($ai_analysis['report']['recommendations']) ? "
        content += `=== RECOMMANDATIONS IA ===\\n`;
        " . json_encode($ai_analysis['report']['recommendations']) . ".forEach(rec => {
            content += `- \${rec}\\n`;
        });
        content += `\\n`;
        " : "") . "
        
        content += `*/\\n\\n`;
        content += `// === CODE SOLUTION ===\\n`;
        content += solutionCode;
        
        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `solution_ia_\${problemTitle.replace(/[^a-zA-Z0-9]/g, '_')}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showMessage('Solution avec métadonnées IA téléchargée!', 'success');
    }
    
    // Animation de la barre de confiance
    document.addEventListener('DOMContentLoaded', function() {
        const confidenceFill = document.querySelector('.confidence-fill');
        if (confidenceFill) {
            const targetWidth = confidenceFill.style.width;
            confidenceFill.style.width = '0%';
            setTimeout(() => {
                confidenceFill.style.width = targetWidth;
            }, 1000);
        }
        
        // Animation des statistiques IA
        const statValues = document.querySelectorAll('.ai-stat-value');
        statValues.forEach((stat, index) => {
            stat.style.opacity = '0';
            stat.style.transform = 'translateY(20px)';
            setTimeout(() => {
                stat.style.transition = 'all 0.6s ease';
                stat.style.opacity = '1';
                stat.style.transform = 'translateY(0)';
            }, 500 + (index * 200));
        });
    });
    
    // Fonction pour copier avec métadonnées
    function copyToClipboard() {
        const solutionCode = `" . addslashes($payment['solution_code']) . "`;
        const metadata = `// Analysé par IA - Difficulté: " . $payment['difficulty'] . " - Score: " . ($payment['final_score'] ?? $payment['base_points']) . " pts\\n\\n`;
        
        navigator.clipboard.writeText(metadata + solutionCode).then(function() {
            showMessage('Solution avec métadonnées copiée!', 'success');
        }).catch(function(err) {
            console.error('Erreur lors de la copie:', err);
            showMessage('Erreur lors de la copie', 'error');
        });
    }
    
    // Fonction pour partager les résultats IA
    function shareAIAnalysis() {
        const shareData = {
            title: 'Solution IA analysée - " . addslashes($payment['problem_title']) . "',
            text: `J'ai acheté une solution analysée par IA! Difficulté: " . $payment['difficulty'] . ", Score: " . ($payment['final_score'] ?? $payment['base_points']) . " points, Confiance IA: " . round($payment['ai_confidence'] * 100, 1) . "%`,
            url: window.location.href
        };
        
        if (navigator.share) {
            navigator.share(shareData);
        } else {
            // Fallback: copier dans le presse-papiers
            navigator.clipboard.writeText(shareData.text + ' - ' + shareData.url).then(() => {
                showMessage('Informations copiées pour partage!', 'success');
            });
        }
    }
    
    // Ajouter le bouton de partage IA
    document.addEventListener('DOMContentLoaded', function() {
        const actionButtons = document.querySelector('.action-buttons');
        if (actionButtons) {
            const shareButton = document.createElement('button');
            shareButton.className = 'btn';
            shareButton.style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
            shareButton.style.color = 'white';
            shareButton.innerHTML = '<i class=\"fas fa-share-alt\"></i> Partager l\\'Analyse IA';
            shareButton.onclick = shareAIAnalysis;
            
            actionButtons.appendChild(shareButton);
        }
    });
    
    // Animation des confettis améliorée
    document.addEventListener('DOMContentLoaded', function() {
        createEnhancedConfetti();
        
        setTimeout(() => {
            const confetti = document.getElementById('confetti');
            if (confetti) {
                confetti.style.opacity = '0';
                setTimeout(() => confetti.remove(), 1000);
            }
        }, 6000);
    });
    
    function createEnhancedConfetti() {
        const confettiContainer = document.getElementById('confetti');
        const colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe'];
        const shapes = ['circle', 'square', 'triangle'];
        
        for (let i = 0; i < 60; i++) {
            const confettiPiece = document.createElement('div');
            const shape = shapes[Math.floor(Math.random() * shapes.length)];
            
            confettiPiece.className = 'confetti-piece';
            confettiPiece.style.left = Math.random() * 100 + '%';
            confettiPiece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confettiPiece.style.animationDelay = Math.random() * 3 + 's';
            confettiPiece.style.animationDuration = (Math.random() * 3 + 2) + 's';
            
            // Formes différentes
            if (shape === 'circle') {
                confettiPiece.style.borderRadius = '50%';
            } else if (shape === 'triangle') {
                confettiPiece.style.width = '0';
                confettiPiece.style.height = '0';
                confettiPiece.style.backgroundColor = 'transparent';
                confettiPiece.style.borderLeft = '5px solid transparent';
                confettiPiece.style.borderRight = '5px solid transparent';
                confettiPiece.style.borderBottom = '10px solid ' + colors[Math.floor(Math.random() * colors.length)];
            }
            
            confettiContainer.appendChild(confettiPiece);
        }
    }
    
    // Fonction pour afficher des messages améliorés
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
            default:
                messageDiv.style.background = 'linear-gradient(135deg, #d1ecf1, #bee5eb)';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = '🤖 ' + message;
        }
        
        document.body.appendChild(messageDiv);
        
        // Animation d'entrée
        messageDiv.style.transform = 'translateX(100%)';
        setTimeout(() => {
            messageDiv.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            messageDiv.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 4000);
    }
    
    // Sauvegarder les détails avec analyse IA
    const paymentDetailsWithAI = {
        transaction_id: '" . htmlspecialchars($payment['transaction_id']) . "',
        problem_title: '" . addslashes($payment['problem_title']) . "',
        amount: '" . $payment['amount'] . "',
        currency: '" . htmlspecialchars($payment['currency']) . "',
        payment_date: '" . $payment['payment_date'] . "',
        solver_name: '" . addslashes($payment['solver_name']) . "',
        ai_analysis: {
            difficulty: '" . $payment['difficulty'] . "',
            base_score: " . $payment['base_points'] . ",
            final_score: " . ($payment['final_score'] ?? $payment['base_points']) . ",
            confidence: " . $payment['ai_confidence'] . "
        }
    };
    
    localStorage.setItem('last_ai_payment_' + paymentDetailsWithAI.transaction_id, JSON.stringify(paymentDetailsWithAI));
    
    console.log('🤖 Paiement avec analyse IA enregistré:', paymentDetailsWithAI);
";

// Inclure le pied de page
include 'footer.php';

// Nettoyer les variables de session
unset($_SESSION['payment_success']);
unset($_SESSION['payment_id']);
unset($_SESSION['transaction_id']);
?>
