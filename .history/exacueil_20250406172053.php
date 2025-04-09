<?php
// Toujours en haut du fichier
declare(strict_types=1);
header_remove('X-Powered-By'); // Cache le header PHP

// Configuration de sécurité
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // En HTTPS seulement
ini_set('session.use_strict_mode', 1);


// Protection contre le clickjacking
header('X-Frame-Options: DENY');
// Protection XSS
header('X-XSS-Protection: 1; mode=block');
// Pas de MIME-sniffing
header('X-Content-Type-Options: nosniff');?>
<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Configuration de la page
$page_title = "Problèmes récents";
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
        border-radius: 8px;
        padding: 15px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: relative;
        width: 100%;
    }
    .publication-header{display:flex;align-items:center;margin-bottom:10px}
    .author-avatar{width:40px;height:40px;border-radius:50%;margin-right:10px;object-fit:cover;border:2px solid #f0f4f8}
    .username{font-weight:600;color:#2c3e50;font-size:1em}
    .date{color:#7f8c8d;font-size:0.8em}
    .publication-title{font-weight:600;font-size:1.2em;margin:10px 0;color:#2c3e50}
    .publication-description{color:#4a5568;line-height:1.5;margin-bottom:15px;font-size:0.9em;max-height:60px;overflow:hidden;text-overflow:ellipsis}
    .read-more-btn{color:#4299e1;font-size:0.85em;cursor:pointer;text-decoration:underline;background:none;border:none;padding:0}
    .publication-code{background:#f8fafc;padding:10px;border-radius:6px;font-family:monospace;overflow-x:auto;border:1px solid #e2e8f0;max-height:150px;font-size:0.85em;margin-bottom:10px}
    .tags-container{display:flex;flex-wrap:wrap;gap:5px;margin:10px 0}
    .tag{padding:3px 8px;border-radius:15px;font-size:0.75em;background:#edf2f7}
    .difficulty-easy{background:#f0fff4;color:#38a169}
    .difficulty-medium{background:#fffaf0;color:#dd6b20}
    .difficulty-hard{background:#fff5f5;color:#e53e3e}
    .publication-footer{display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #f0f4f8}
    .publication-stats{display:flex;gap:10px;color:#718096;font-size:0.8em}
    .favorite-btn{position:absolute;top:10px;right:10px;background:none;border:none;font-size:1.2em;color:#cbd5e0;cursor:pointer;transition:all .2s}
    .favorite-btn:hover{transform:scale(1.1)}
    .favorite-btn.active{color:#EC4899}
    .status-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .status-indicator.not-submitted {
        background-color: red;
    }

    .status-indicator.submitted {
        background-color: green;
    }
    .status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    .unsolved {
        background-color: red;
    }
    .solved {
        background-color: green;
    }
    .action-buttons { display: flex; gap: 10px; }
    .btn { padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 0.9em; }
    .btn-primary { background: #4299e1; color: white; }
    .btn-success { background: #48bb78; color: white; }
    .btn:hover { opacity: 0.9; }
";

$conn = connect();
if ($conn === null) die("Database connection failed.");
if (!isLoggedIn()) header('Location: login.php');

// Préparer les messages pour header.php
$_SESSION['success_message'] = $_SESSION['success_message'] ?? '';
$_SESSION['error_message'] = $_SESSION['error_message'] ?? '';

// Récupérer les publications
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite,
               (SELECT COUNT(*) FROM solutions WHERE problem_id = p.problem_id AND user_id = ?) AS has_solution
        FROM problems p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error: ".$e->getMessage());
    $publications = [];
}

// Inclure l'en-tête qui contient la structure de base et la sidebar
include 'header.php';
?>

<h2>Problèmes récents</h2>

<div class="publications-grid">
    <?php foreach($publications as $pub): ?>
    <div class="publication-card">
        <button class="favorite-btn <?= $pub['is_favorite']?'active':'' ?>" 
                data-problem-id="<?= htmlspecialchars($pub['problem_id']) ?>">
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
        
        <div class="publication-description">
            <?= nl2br(htmlspecialchars($pub['description'])) ?>
        </div>
        <button class="read-more-btn" onclick="toggleDescription(this)">Lire plus</button>
        
        <?php if(!empty($pub['code'])): ?>
            <div class="publication-code">
                <pre><?= htmlspecialchars($pub['code']) ?></pre>
            </div>
        <?php endif; ?>
        
        <div class="tags-container">
            <span class="difficulty-<?= htmlspecialchars($pub['difficulty']) ?> tag">
                <?= ucfirst(htmlspecialchars($pub['difficulty'])) ?>
            </span>
            <?php if(!empty($pub['tags'])): ?>
                <?php foreach(explode(',', $pub['tags']) as $tag): ?>
                    <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="publication-footer">
            <div class="publication-stats">
                <span><i class="fas fa-comment"></i> <?= htmlspecialchars($pub['comment_count']??0) ?></span>
                <span><i class="fas fa-check"></i> <?= htmlspecialchars($pub['solution_count']??0) ?></span>
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
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
// Ajout du script spécifique pour cette page
$additional_scripts = "
    window.toggleDescription = function(button) {
        const description = button.previousElementSibling;
        if (description.style.maxHeight) {
            description.style.maxHeight = '';
            button.textContent = 'Lire plus';
        } else {
            description.style.maxHeight = 'none';
            button.textContent = 'Lire moins';
        }
    };
    
    // Gestion des favoris
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const problemId = this.dataset.problemId;
            const isActive = this.classList.contains('active');
            
            this.classList.toggle('active');
            this.querySelector('i').style.transform = 'scale(1.2)';
            
            fetch('api/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    problem_id: problemId,
                    set_favorite: isActive ? '0' : '1',
                    csrf_token: '" . ($_SESSION['csrf_token'] ?? '') . "'
                })
            }).then(response => {
                if (!response.ok) {
                    this.classList.toggle('active');
                }
            }).catch(() => {
                this.classList.toggle('active');
            });
            
            setTimeout(() => {
                this.querySelector('i').style.transform = '';
            }, 300);
        });
    });
    
    // Animation des cartes
    document.querySelectorAll('.publication-card').forEach(card => {
        card.addEventListener('mouseenter', () => 
            card.style.boxShadow = '0 3px 10px rgba(0,0,0,0.1)');
        card.addEventListener('mouseleave', () => 
            card.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)');
    });
";

// Inclure le pied de page
include 'footer.php';
?>

<?php if (isset($_SESSION['solution_submitted']) && $_SESSION['solution_submitted']): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sélectionner l'indicateur correspondant au problème
        var indicators = document.querySelectorAll('.status-indicator[data-problem-id="<?php echo $_SESSION['submitted_problem_id']; ?>"]');
        indicators.forEach(function(indicator) {
            indicator.classList.remove('not-submitted');
            indicator.classList.add('submitted');
        });
    });
</script>
<?php 
    // Nettoyer les variables de session
    unset($_SESSION['solution_submitted']);
    unset($_SESSION['submitted_problem_id']);
endif; 
?>