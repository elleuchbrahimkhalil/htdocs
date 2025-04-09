<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'ID du problème est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: exacueil.php');
    exit;
}

$problem_id = $_GET['id'];

// Récupérer les détails du problème
try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.name as author_name,
        (SELECT COUNT(*) FROM solutions WHERE problem_id = p.problem_id) as solution_count,
        (SELECT COUNT(*) FROM solutions WHERE problem_id = p.problem_id AND status = 'accepted') as accepted_solutions
        FROM problems p
        JOIN users u ON p.user_id = u.id
        WHERE p.problem_id = ?
    ");
    
    $stmt->execute([$problem_id]);
    $problem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$problem) {
        // Problème non trouvé
        $_SESSION['error_message'] = "Le problème demandé n'existe pas.";
        header('Location: exacueil.php');
        exit;
    }
    
    // Si l'utilisateur est connecté, vérifier s'il a déjà soumis une solution
    $user_solution = null;
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("
            SELECT s.*, uc.price
            FROM solutions s
            LEFT JOIN user_corrections uc ON s.id = uc.solution_id
            WHERE s.problem_id = ? AND s.user_id = ?
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$problem_id, $_SESSION['user_id']]);
        $user_solution = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du problème: " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la récupération du problème.";
    header('Location: exacueil.php');
    exit;
}

// Définir le titre de la page
$page_title = htmlspecialchars($problem['title']);

// CSS spécifique à cette page
$additional_css = "
    .problem-container {
        max-width: 900px;
        margin: 0 auto 30px auto;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .problem-header {
        padding: 25px;
        border-bottom: 1px solid #f0f0f0;
        position: relative;
    }

    .problem-title {
        margin: 0 0 15px 0;
        color: #2c3e50;
        font-size: 28px;
    }

    .problem-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
        font-size: 14px;
        color: #7f8c8d;
    }

    .difficulty {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 15px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
    }

    .difficulty-easy { background: #d5f5e3; color: #27ae60; }
    .difficulty-medium { background: #fef9e7; color: #f39c12; }
    .difficulty-hard { background: #fdedec; color: #e74c3c; }

    .problem-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 15px;
    }

    .tag {
        background: #ecf0f1;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 12px;
        color: #2c3e50;
    }

    .section {
        padding: 25px;
        border-bottom: 1px solid #f0f0f0;
    }

    .section-title {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 20px;
        font-weight: 600;
    }

    .code-block {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        overflow-x: auto;
        font-family: 'Courier New', Courier, monospace;
        line-height: 1.5;
        border: 1px solid #e0e0e0;
    }

    .actions {
        padding: 25px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .action-group {
        display: flex;
        gap: 10px;
    }

    .solution-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
    }

    .solution-status.pending {
        background: #fef9e7;
        color: #f39c12;
    }

    .solution-status.accepted {
        background: #d5f5e3;
        color: #27ae60;
    }

    .solution-status.rejected {
        background: #fdedec;
        color: #e74c3c;
    }

    .solutions-count {
        margin-top: 15px;
        font-size: 14px;
        color: #7f8c8d;
    }

    .favorite-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        background: none;
        border: none;
        font-size: 24px;
        color: #ddd;
        cursor: pointer;
        transition: all 0.3s;
    }

    .favorite-btn:hover {
        transform: scale(1.1);
    }

    .favorite-btn.active {
        color: #f1c40f;
    }
";

// Inclure l'en-tête
include 'header.php';
?>

<div class="problem-container">
    <div class="problem-header">
        <h1 class="problem-title"><?php echo htmlspecialchars($problem['title']); ?></h1>
        
        <?php if (isLoggedIn()): ?>
            <?php
            // Vérifier si le problème est déjà en favori
            $stmt_fav = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND problem_id = ?");
            $stmt_fav->execute([$_SESSION['user_id'], $problem_id]);
            $is_favorite = $stmt_fav->fetch();
            ?>
            <button class="favorite-btn <?php echo $is_favorite ? 'active' : ''; ?>" 
                    data-problem-id="<?php echo $problem_id; ?>">
                <i class="fas fa-star"></i>
            </button>
        <?php endif; ?>
        
        <div class="problem-meta">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($problem['author_name'] ?? $problem['username']); ?></span>
            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($problem['created_at'])); ?></span>
            <span><i class="fas fa-code"></i> <?php echo htmlspecialchars(ucfirst($problem['language'])); ?></span>
            <span class="difficulty difficulty-<?php echo htmlspecialchars($problem['difficulty']); ?>">
                <?php 
                $difficulty_text = '';
                switch($problem['difficulty']) {
                    case 'easy': $difficulty_text = 'Facile'; break;
                    case 'medium': $difficulty_text = 'Moyen'; break;
                    case 'hard': $difficulty_text = 'Difficile'; break;
                    default: $difficulty_text = $problem['difficulty'];
                }
                echo htmlspecialchars($difficulty_text);
                ?>
            </span>
            <span><i class="fas fa-award"></i> <?php echo htmlspecialchars($problem['points']); ?> points</span>
        </div>
        
        <?php if (!empty($problem['tags'])): ?>
            <div class="problem-tags">
                <?php 
                $tags = explode(',', $problem['tags']);
                foreach ($tags as $tag): 
                    if (!empty(trim($tag))):
                ?>
                    <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2 class="section-title">Description</h2>
        <div><?php echo nl2br(htmlspecialchars($problem['description'])); ?></div>
    </div>
    
    <?php if (!empty($problem['code'])): ?>
    <div class="section">
        <h2 class="section-title">Code du problème</h2>
        <pre class="code-block"><?php echo htmlspecialchars($problem['code']); ?></pre>
    </div>
    <?php endif; ?>
    
    <div class="section">
        <h2 class="section-title">Solution attendue</h2>
        <div><?php echo nl2br(htmlspecialchars($problem['solution'])); ?></div>
        
        <div class="solutions-count">
            <p>
                <i class="fas fa-users"></i> <?php echo $problem['solution_count'] ?? 0; ?> solution(s) soumise(s)
                <i class="fas fa-check-circle"></i> <?php echo $problem['accepted_solutions'] ?? 0; ?> solution(s) acceptée(s)
            </p>
        </div>
    </div>
    
    <div class="actions">
        <div class="action-group">
            <a href="exacueil.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Retour aux problèmes
            </a>
        </div>
        
        <div class="action-group">
            <?php if (isLoggedIn()): ?>
                <?php if ($problem['user_id'] == $_SESSION['user_id']): ?>
                    <!-- L'utilisateur est l'auteur du problème -->
                    <a href="user_feedback.php" class="btn btn-primary">
                        <i class="fas fa-check-double"></i> Voir les solutions soumises
                    </a>
                <?php else: ?>
                    <!-- L'utilisateur n'est pas l'auteur -->
                    <?php if ($user_solution): ?>
                        <!-- L'utilisateur a déjà soumis une solution -->
                        <?php if ($user_solution['status'] === 'pending'): ?>
                            <div class="solution-status pending">
                                <i class="fas fa-clock"></i> Solution en attente
                            </div>
                        <?php elseif ($user_solution['status'] === 'accepted'): ?>
                            <div class="solution-status accepted">
                                <i class="fas fa-check-circle"></i> Solution acceptée
                            </div>
                        <?php elseif ($user_solution['status'] === 'rejected'): ?>
                            <div class="solution-status rejected">
                                <i class="fas fa-times-circle"></i> Solution rejetée
                            </div>
                            <a href="submit_solution.php?problem_id=<?php echo $problem_id; ?>" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Soumettre à nouveau
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- L'utilisateur n'a pas encore soumis de solution -->
                        <a href="submit_solution.php?problem_id=<?php echo $problem_id; ?>" class="btn btn-primary">
                            <i class="fas fa-code"></i> Proposer une solution
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <!-- L'utilisateur n'est pas connecté -->
                <a href="exlogin.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Connectez-vous pour proposer une solution
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Scripts additionnels
$additional_scripts = "
    // Gestion des favoris
    document.querySelector('.favorite-btn')?.addEventListener('click', function() {
        const problemId = this.dataset.problemId;
        const isActive = this.classList.contains('active');
        
        this.classList.toggle('active');
        this.querySelector('i').style.transform = 'scale(1.2)';
        
        fetch('api/toggle_favorite.php', {
            method: 'POST',
            body: new URLSearchParams({
                problem_id: problemId,
                set_favorite: isActive ? '0' : '1',
                csrf_token: '" . ($_SESSION['csrf_token'] ?? '') . "'
            })
        }).catch(() => this.classList.toggle('active'));
        
        setTimeout(() => {
            this.querySelector('i').style.transform = '';
        }, 300);
    });
";

// Inclure le pied de page
include 'footer.php';
?>
