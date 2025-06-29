<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$page_title = "Solutions Premium";

// CSS pour l'effet de flou et le système de paiement
$additional_css = "
    .premium-solutions-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }

    .solution-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border: 1px solid #e0e6ed;
        position: relative;
        overflow: hidden;
    }

    .solution-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f4f8;
    }

    .problem-info h3 {
        color: #2c3e50;
        margin: 0 0 10px 0;
        font-size: 1.4em;
    }

    .problem-meta {
        display: flex;
        gap: 15px;
        font-size: 14px;
        color: #7f8c8d;
        flex-wrap: wrap;
    }

    .difficulty {
        padding: 4px 12px;
        border-radius: 15px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
    }

    .difficulty.easy { background: #d5f5e3; color: #27ae60; }
    .difficulty.medium { background: #fef9e7; color: #f39c12; }
    .difficulty.hard { background: #fdedec; color: #e74c3c; }

    .price-tag {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 12px 20px;
        border-radius: 25px;
        font-weight: bold;
        font-size: 1.2em;
        text-align: center;
        min-width: 120px;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .solution-content {
        position: relative;
        margin: 20px 0;
    }

    /* Effet de flou pour les solutions non payées */
    .solution-blurred {
        filter: blur(8px);
        -webkit-filter: blur(8px);
        pointer-events: none;
        user-select: none;
        position: relative;
    }

    .solution-blurred::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, 
            rgba(255,255,255,0.8) 0%, 
            rgba(240,240,240,0.9) 50%, 
            rgba(255,255,255,0.8) 100%);
        backdrop-filter: blur(2px);
        z-index: 1;
    }

    .unlock-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
        text-align: center;
        background: rgba(255,255,255,0.95);
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        border: 2px solid #667eea;
        backdrop-filter: blur(10px);
    }

    .unlock-overlay h4 {
        color: #2c3e50;
        margin: 0 0 15px 0;
        font-size: 1.3em;
    }

    .unlock-overlay p {
        color: #7f8c8d;
        margin: 0 0 20px 0;
        line-height: 1.5;
    }

    .solution-code {
        background: #2c3e50;
        color: #ecf0f1;
        padding: 20px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        overflow-x: auto;
        line-height: 1.6;
        margin: 15px 0;
        position: relative;
    }

    .solution-explanation {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #3498db;
        margin: 15px 0;
        line-height: 1.6;
    }

    .author-info {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 15px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .author-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #3498db;
    }

    .author-details h5 {
        margin: 0;
        color: #2c3e50;
        font-size: 1.1em;
    }

    .author-details p {
        margin: 5px 0 0 0;
        color: #7f8c8d;
        font-size: 0.9em;
    }

    .payment-section {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        padding: 25px;
        border-radius: 12px;
        margin-top: 20px;
        border: 2px dashed #667eea;
        text-align: center;
    }

    .payment-methods {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .payment-btn {
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        font-size: 14px;
    }

    .payment-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }

    .btn-paypal {
        background: #0070ba;
        color: white;
    }

    .btn-stripe {
        background: #635bff;
        color: white;
    }

    .btn-crypto {
        background: #f7931a;
        color: white;
    }

    .solution-unlocked {
        border: 3px solid #27ae60;
        background: linear-gradient(135deg, #d5f5e3, #a8e6cf);
    }

    .solution-unlocked .unlock-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #27ae60;
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .stats-bar {
        display: flex;
        justify-content: space-between;
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        font-size: 14px;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #7f8c8d;
    }

    .preview-section {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
    }

    .preview-section h5 {
        color: #856404;
        margin: 0 0 10px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .payment-processing {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .payment-modal {
        background: white;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
        max-width: 400px;
        width: 90%;
    }

    @media (max-width: 768px) {
        .solution-header {
            flex-direction: column;
            gap: 15px;
        }
        
        .payment-methods {
            flex-direction: column;
        }
        
        .payment-btn {
            width: 100%;
            justify-content: center;
        }
    }
";

include 'header.php';
?>

<div class="premium-solutions-container">
    <h1><i class="fas fa-gem"></i> Solutions Premium</h1>
    <p>Découvrez des solutions de qualité développées par notre communauté d'experts.</p>

    <?php
    try {
        $conn = connect();
        
        // Récupérer les solutions premium avec informations complètes
        $stmt = $conn->prepare("
            SELECT 
                s.id as solution_id,
                s.solution_code,
                s.explanation,
                s.price,
                s.created_at as solution_date,
                p.problem_id,
                p.title as problem_title,
                p.description as problem_description,
                p.difficulty,
                p.language,
                p.points,
                u.id as author_id,
                u.username as author_username,
                u.name as author_name,
                u.avatar_url as author_avatar,
                (SELECT COUNT(*) FROM payments pay WHERE pay.solution_id = s.id AND pay.status = 'completed') as purchase_count,
                (SELECT COUNT(*) FROM solution_ratings sr WHERE sr.solution_id = s.id) as rating_count,
                (SELECT AVG(sr.rating) FROM solution_ratings sr WHERE sr.solution_id = s.id) as avg_rating,
                CASE WHEN EXISTS(
                    SELECT 1 FROM payments pay 
                    WHERE pay.solution_id = s.id 
                    AND pay.payer_id = ? 
                    AND pay.status = 'completed'
                ) THEN 1 ELSE 0 END as is_purchased
            FROM solutions s
            JOIN problems p ON s.problem_id = p.problem_id
            JOIN users u ON s.user_id = u.id
            WHERE s.status = 'approved' 
            AND s.price > 0
            AND s.user_id != ?
            ORDER BY s.created_at DESC
        ");
        
        $stmt->execute([$user['id'], $user['id']]);
        $premium_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($premium_solutions)) {
            echo '<div style="text-align: center; padding: 50px; background: white; border-radius: 12px; margin: 20px 0;">
                    <i class="fas fa-gem" style="font-size: 4em; color: #ddd; margin-bottom: 20px;"></i>
                    <h3>Aucune solution premium disponible</h3>
                    <p>Les solutions premium apparaîtront ici une fois publiées par la communauté.</p>
                  </div>';
        } else {
            foreach ($premium_solutions as $solution) {
                $isPurchased = $solution['is_purchased'];
                $cardClass = $isPurchased ? 'solution-card solution-unlocked' : 'solution-card';
                ?>
                
                <div class="<?= $cardClass ?>">
                    <?php if ($isPurchased): ?>
                        <div class="unlock-badge">
                            <i class="fas fa-unlock"></i> Déverrouillé
                        </div>
                    <?php endif; ?>
                    
                    <div class="solution-header">
                        <div class="problem-info">
                            <h3><?= htmlspecialchars($solution['problem_title']) ?></h3>
                            <div class="problem-meta">
                                <span><i class="fas fa-code"></i> <?= htmlspecialchars(ucfirst($solution['language'])) ?></span>
                                <span class="difficulty <?= $solution['difficulty'] ?>">
                                    <?php
                                    $difficulty_labels = ['easy' => 'Facile', 'medium' => 'Moyen', 'hard' => 'Difficile'];
                                    echo $difficulty_labels[$solution['difficulty']] ?? ucfirst($solution['difficulty']);
                                    ?>
                                </span>
                                <span><i class="fas fa-award"></i> <?= $solution['points'] ?> points</span>
                                <span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($solution['solution_date'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="price-tag">
                            <?= number_format($solution['price'], 2) ?> €
                        </div>
                    </div>

                    <!-- Informations sur l'auteur -->
                    <div class="author-info">
                        <img src="<?= htmlspecialchars($solution['author_avatar'] ?: 'default-avatar.png') ?>" 
                             alt="Avatar" class="author-avatar">
                        <div class="author-details">
                            <h5><?= htmlspecialchars($solution['author_name'] ?: $solution['author_username']) ?></h5>
                            <p>Développeur expert</p>
                                                    </div>
                    </div>

                    <!-- Statistiques de la solution -->
                    <div class="stats-bar">
                        <div class="stat-item">
                            <i class="fas fa-shopping-cart"></i>
                            <span><?= $solution['purchase_count'] ?> achats</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-star"></i>
                            <span><?= $solution['rating_count'] > 0 ? number_format($solution['avg_rating'], 1) . '/5' : 'Pas encore noté' ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-eye"></i>
                            <span>Solution vérifiée</span>
                        </div>
                    </div>

                    <!-- Description du problème -->
                    <div class="preview-section">
                        <h5><i class="fas fa-info-circle"></i> Description du problème</h5>
                        <p><?= nl2br(htmlspecialchars(substr($solution['problem_description'], 0, 200))) ?>
                           <?= strlen($solution['problem_description']) > 200 ? '...' : '' ?></p>
                    </div>

                    <!-- Contenu de la solution -->
                    <div class="solution-content">
                        <?php if (!$isPurchased): ?>
                            <!-- Solution floutée pour les non-acheteurs -->
                            <div class="solution-blurred">
                                <div class="solution-code">
                                    <pre><?= htmlspecialchars($solution['solution_code']) ?></pre>
                                </div>
                                
                                <?php if (!empty($solution['explanation'])): ?>
                                    <div class="solution-explanation">
                                        <h4><i class="fas fa-lightbulb"></i> Explication</h4>
                                        <p><?= nl2br(htmlspecialchars($solution['explanation'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Overlay de déverrouillage -->
                            <div class="unlock-overlay">
                                <h4><i class="fas fa-lock"></i> Solution Premium</h4>
                                <p>Cette solution de qualité professionnelle est disponible pour <strong><?= number_format($solution['price'], 2) ?> €</strong></p>
                                <p><i class="fas fa-shield-alt"></i> Garantie satisfait ou remboursé</p>
                            </div>

                        <?php else: ?>
                            <!-- Solution déverrouillée pour les acheteurs -->
                            <div class="solution-code">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <h4 style="color: #ecf0f1; margin: 0;"><i class="fas fa-code"></i> Code de la solution</h4>
                                    <button onclick="copyToClipboard('solution_<?= $solution['solution_id'] ?>')" 
                                            class="payment-btn" style="padding: 5px 10px; font-size: 12px; background: #27ae60;">
                                        <i class="fas fa-copy"></i> Copier
                                    </button>
                                </div>
                                <pre id="solution_<?= $solution['solution_id'] ?>"><?= htmlspecialchars($solution['solution_code']) ?></pre>
                            </div>
                            
                            <?php if (!empty($solution['explanation'])): ?>
                                <div class="solution-explanation">
                                    <h4><i class="fas fa-lightbulb"></i> Explication détaillée</h4>
                                    <p><?= nl2br(htmlspecialchars($solution['explanation'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Section de téléchargement -->
                            <div style="background: #d5f5e3; padding: 15px; border-radius: 8px; margin-top: 15px; text-align: center;">
                                <h5 style="color: #27ae60; margin: 0 0 10px 0;">
                                    <i class="fas fa-download"></i> Solution achetée avec succès !
                                </h5>
                                <button onclick="downloadSolution(<?= $solution['solution_id'] ?>, '<?= addslashes($solution['problem_title']) ?>')" 
                                        class="payment-btn btn-paypal">
                                    <i class="fas fa-download"></i> Télécharger la solution
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isPurchased): ?>
                        <!-- Section de paiement -->
                        <div class="payment-section">
                            <h4><i class="fas fa-credit-card"></i> Acheter cette solution</h4>
                            <p>Accédez instantanément à la solution complète avec explication détaillée</p>
                            
                            <div class="payment-methods">
                                <a href="process_payment.php?solution_id=<?= $solution['solution_id'] ?>&method=paypal" 
                                   class="payment-btn btn-paypal" onclick="showPaymentProcessing()">
                                    <i class="fab fa-paypal"></i> PayPal
                                </a>
                                
                                <a href="process_payment.php?solution_id=<?= $solution['solution_id'] ?>&method=stripe" 
                                   class="payment-btn btn-stripe" onclick="showPaymentProcessing()">
                                    <i class="fab fa-cc-stripe"></i> Carte bancaire
                                </a>
                                
                                <a href="process_payment.php?solution_id=<?= $solution['solution_id'] ?>&method=crypto" 
                                   class="payment-btn btn-crypto" onclick="showPaymentProcessing()">
                                    <i class="fab fa-bitcoin"></i> Crypto
                                </a>
                            </div>
                            
                            <div style="margin-top: 15px; font-size: 12px; color: #7f8c8d;">
                                <i class="fas fa-shield-alt"></i> Paiement sécurisé • 
                                <i class="fas fa-undo"></i> Remboursement sous 7 jours • 
                                <i class="fas fa-support"></i> Support 24/7
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php
            }
        }
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> 
                Erreur lors du chargement des solutions : ' . htmlspecialchars($e->getMessage()) . '
              </div>';
    }
    ?>
</div>

<!-- Modal de traitement du paiement -->
<div class="payment-processing" id="paymentProcessing">
    <div class="payment-modal">
        <div class="loading-spinner"></div>
        <h3 style="margin: 20px 0 10px 0;">Traitement du paiement...</h3>
        <p>Veuillez patienter, vous allez être redirigé vers la plateforme de paiement.</p>
    </div>
</div>

<?php
$additional_scripts = "
    // Fonction pour afficher le modal de traitement
    function showPaymentProcessing() {
        document.getElementById('paymentProcessing').style.display = 'flex';
    }

    // Fonction pour copier le code dans le presse-papiers
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        const text = element.textContent;
        
        navigator.clipboard.writeText(text).then(function() {
            showMessage('Code copié dans le presse-papiers!', 'success');
        }).catch(function(err) {
            console.error('Erreur lors de la copie:', err);
            showMessage('Erreur lors de la copie', 'error');
        });
    }

    // Fonction pour télécharger la solution
    function downloadSolution(solutionId, problemTitle) {
        fetch('download_solution.php?solution_id=' + solutionId)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'solution_' + problemTitle.replace(/[^a-zA-Z0-9]/g, '_') + '.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                showMessage('Solution téléchargée avec succès!', 'success');
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur lors du téléchargement', 'error');
            });
    }

    // Fonction pour afficher des messages
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.padding = '15px 20px';
        messageDiv.style.borderRadius = '8px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.maxWidth = '300px';
        messageDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        messageDiv.style.transition = 'opacity 0.3s ease';
        
        switch(type) {
            case 'success':
                messageDiv.style.backgroundColor = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.border = '1px solid #c3e6cb';
                messageDiv.innerHTML = '✅ ' + message;
                break;
            case 'error':
                messageDiv.style.backgroundColor = '#f8d7da';
                messageDiv.style.color = '#721c24';
                messageDiv.style.border = '1px solid #f5c6cb';
                messageDiv.innerHTML = '❌ ' + message;
                break;
            default:
                messageDiv.style.backgroundColor = '#d1ecf1';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = 'ℹ️ ' + message;
        }
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 4000);
    }

    // Animation d'entrée pour les cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.solution-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 150);
        });
    });

    // Effet de survol sur les cartes
    document.querySelectorAll('.solution-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (!this.classList.contains('solution-unlocked')) {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            if (!this.classList.contains('solution-unlocked')) {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
            }
        });
    });
";

include 'footer.php';
?>
