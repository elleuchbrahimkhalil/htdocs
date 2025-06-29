<?php
header("Access-Control-Allow-Origin: https://elleuchbrahimkhalil.github.io");
header("X-Frame-Options: ALLOW-FROM https://elleuchbrahimkhalil.github.io");

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
    .favorite-btn.loading{color:#ffc107;transform:scale(1.1)}
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

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();

// Préparer les messages pour header.php
$_SESSION['success_message'] = $_SESSION['success_message'] ?? '';
$_SESSION['error_message'] = $_SESSION['error_message'] ?? '';

// Récupérer les publications EXACTEMENT comme dans manage_favorites.php
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id) AS favorite_count,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite,
               (SELECT COUNT(*) FROM solutions WHERE problem_id = p.problem_id AND user_id = ?) AS has_solution
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

<h2>Problèmes récents</h2>

<div class="publications-grid">
    <?php foreach($publications as $pub): ?>
    <div class="publication-card">
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
        
        <div class="publication-description">
            <?= nl2br(htmlspecialchars(substr($pub['description'], 0, 100).(strlen($pub['description']) > 100 ? '...' : ''))) ?>
        </div>
        
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
                <span><i class="fas fa-code"></i> <?= htmlspecialchars(ucfirst($pub['language'])) ?></span>
                <span><i class="fas fa-check-circle"></i> <?= htmlspecialchars($pub['solution_count']??0) ?> résolutions</span>
                <span><i class="fas fa-heart"></i> <?= htmlspecialchars($pub['favorite_count']) ?> favoris</span>
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
    // Gestion des favoris - VERSION CORRIGÉE
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
                        const statsSpan = this.closest('.publication-card').querySelector('.publication-stats span:last-child');
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
                        const statsSpan = this.closest('.publication-card').querySelector('.publication-stats span:last-child');
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
    
    // Fonction pour afficher des messages
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.padding = '15px 20px';
        messageDiv.style.borderRadius = '5px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.maxWidth = '300px';
        messageDiv.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
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
            case 'info':
                messageDiv.style.backgroundColor = '#d1ecf1';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = 'ℹ️ ' + message;
                break;
        }
        
        document.body.appendChild(messageDiv);
        
        // Supprimer le message après 3 secondes
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                             if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 3000);
    }
    
    // Animation des cartes
    document.querySelectorAll('.publication-card').forEach(card => {
        card.addEventListener('mouseenter', () => 
            card.style.boxShadow = '0 3px 10px rgba(0,0,0,0.1)');
        card.addEventListener('mouseleave', () => 
            card.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)');
    });
    
    // Animation d'apparition des cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.publication-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
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
    
    // Fonction pour filtrer les problèmes par difficulté
    function filterByDifficulty(difficulty) {
        const cards = document.querySelectorAll('.publication-card');
        cards.forEach(card => {
            const difficultyTag = card.querySelector('.difficulty-' + difficulty);
            if (difficulty === 'all' || difficultyTag) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.5s ease';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Fonction pour filtrer par langage
    function filterByLanguage(language) {
        const cards = document.querySelectorAll('.publication-card');
        cards.forEach(card => {
            const languageSpan = card.querySelector('.publication-stats span:first-child');
            if (language === 'all' || (languageSpan && languageSpan.textContent.toLowerCase().includes(language.toLowerCase()))) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.5s ease';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Fonction de recherche
    function searchProblems(query) {
        const cards = document.querySelectorAll('.publication-card');
        const searchTerm = query.toLowerCase();
        
        cards.forEach(card => {
            const title = card.querySelector('.publication-title').textContent.toLowerCase();
            const description = card.querySelector('.publication-description').textContent.toLowerCase();
            const tags = Array.from(card.querySelectorAll('.tag')).map(tag => tag.textContent.toLowerCase()).join(' ');
            
            if (title.includes(searchTerm) || description.includes(searchTerm) || tags.includes(searchTerm)) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.5s ease';
                
                // Surligner les termes trouvés
                highlightSearchTerm(card, searchTerm);
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Fonction pour surligner les termes de recherche
    function highlightSearchTerm(card, term) {
        if (!term) return;
        
        const title = card.querySelector('.publication-title');
        const description = card.querySelector('.publication-description');
        
        [title, description].forEach(element => {
            if (element) {
                const originalText = element.textContent;
                const regex = new RegExp(`(${term})`, 'gi');
                const highlightedText = originalText.replace(regex, '<mark style=\"background-color: #fff3cd; padding: 2px 4px; border-radius: 3px;\">$1</mark>');
                
                if (highlightedText !== originalText) {
                    element.innerHTML = highlightedText;
                }
            }
        });
    }
    
    // Fonction pour réinitialiser les filtres
    function resetFilters() {
        const cards = document.querySelectorAll('.publication-card');
        cards.forEach(card => {
            card.style.display = 'block';
            
            // Supprimer le surlignage
            const title = card.querySelector('.publication-title');
            const description = card.querySelector('.publication-description');
            
            [title, description].forEach(element => {
                if (element && element.innerHTML.includes('<mark')) {
                    element.innerHTML = element.textContent;
                }
            });
        });
    }
    
    // Ajouter une barre de recherche et des filtres (optionnel)
    function addSearchAndFilters() {
        const content = document.querySelector('.content');
        const searchContainer = document.createElement('div');
        searchContainer.style.marginBottom = '20px';
        searchContainer.style.padding = '15px';
        searchContainer.style.background = 'white';
        searchContainer.style.borderRadius = '8px';
        searchContainer.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)';
        
        searchContainer.innerHTML = `
            <div style=\"display: flex; gap: 15px; flex-wrap: wrap; align-items: center;\">
                <div style=\"flex: 1; min-width: 200px;\">
                    <input type=\"text\" id=\"searchInput\" placeholder=\"Rechercher des problèmes...\" 
                           style=\"width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;\">
                </div>
                <div>
                    <select id=\"difficultyFilter\" style=\"padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;\">
                        <option value=\"all\">Toutes difficultés</option>
                        <option value=\"easy\">Facile</option>
                        <option value=\"medium\">Moyen</option>
                        <option value=\"hard\">Difficile</option>
                    </select>
                </div>
                <div>
                    <select id=\"languageFilter\" style=\"padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;\">
                        <option value=\"all\">Tous langages</option>
                        <option value=\"python\">Python</option>
                        <option value=\"java\">Java</option>
                        <option value=\"javascript\">JavaScript</option>
                        <option value=\"c\">C</option>
                        <option value=\"cpp\">C++</option>
                        <option value=\"php\">PHP</option>
                    </select>
                </div>
                <div>
                    <button onclick=\"resetFilters()\" style=\"padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;\">
                        <i class=\"fas fa-undo\"></i> Réinitialiser
                    </button>
                </div>
            </div>
        `;
        
        const h2 = content.querySelector('h2');
        h2.parentNode.insertBefore(searchContainer, h2.nextSibling);
        
        // Ajouter les événements
        document.getElementById('searchInput').addEventListener('input', function() {
            searchProblems(this.value);
        });
        
        document.getElementById('difficultyFilter').addEventListener('change', function() {
            filterByDifficulty(this.value);
        });
        
        document.getElementById('languageFilter').addEventListener('change', function() {
            filterByLanguage(this.value);
        });
    }
    
    // Fonction pour afficher les statistiques
    function showStats() {
        const cards = document.querySelectorAll('.publication-card');
        const totalProblems = cards.length;
        const solvedProblems = document.querySelectorAll('.status-dot.solved').length;
        const unsolvedProblems = totalProblems - solvedProblems;
        
        // Compter par difficulté
        const difficulties = {
            easy: document.querySelectorAll('.difficulty-easy').length,
            medium: document.querySelectorAll('.difficulty-medium').length,
            hard: document.querySelectorAll('.difficulty-hard').length
        };
        
        console.log('📊 Statistiques des problèmes:');
        console.log(`Total: ${totalProblems}`);
        console.log(`Résolus: ${solvedProblems}`);
        console.log(`Non résolus: ${unsolvedProblems}`);
        console.log(`Faciles: ${difficulties.easy}`);
        console.log(`Moyens: ${difficulties.medium}`);
        console.log(`Difficiles: ${difficulties.hard}`);
        
        return {
            total: totalProblems,
            solved: solvedProblems,
            unsolved: unsolvedProblems,
            difficulties: difficulties
        };
    }
    
    // Fonction pour trier les problèmes
    function sortProblems(criteria) {
        const grid = document.querySelector('.publications-grid');
        const cards = Array.from(document.querySelectorAll('.publication-card'));
        
        cards.sort((a, b) => {
            switch(criteria) {
                case 'date':
                    const dateA = new Date(a.querySelector('.date').textContent);
                    const dateB = new Date(b.querySelector('.date').textContent);
                    return dateB - dateA;
                    
                case 'difficulty':
                    const difficultyOrder = { easy: 1, medium: 2, hard: 3 };
                    const diffA = a.querySelector('[class*=\"difficulty-\"]').className.match(/difficulty-(\w+)/)[1];
                    const diffB = b.querySelector('[class*=\"difficulty-\"]').className.match(/difficulty-(\w+)/)[1];
                    return difficultyOrder[diffA] - difficultyOrder[diffB];
                    
                case 'popularity':
                    const favA = parseInt(a.querySelector('.publication-stats span:last-child').textContent.match(/\d+/)[0]);
                    const favB = parseInt(b.querySelector('.publication-stats span:last-child').textContent.match(/\d+/)[0]);
                    return favB - favA;
                    
                case 'title':
                    const titleA = a.querySelector('.publication-title').textContent.toLowerCase();
                    const titleB = b.querySelector('.publication-title').textContent.toLowerCase();
                    return titleA.localeCompare(titleB);
                    
                default:
                    return 0;
            }
        });
        
        // Réorganiser les cartes
        cards.forEach(card => grid.appendChild(card));
        
        // Animation de réorganisation
        cards.forEach((card, index) => {
            card.style.animation = `fadeIn 0.3s ease ${index * 0.05}s both`;
        });
    }
    
    // Ajouter un menu de tri
    function addSortMenu() {
        const h2 = document.querySelector('h2');
        const sortContainer = document.createElement('div');
        sortContainer.style.marginBottom = '15px';
        sortContainer.style.textAlign = 'right';
        
        sortContainer.innerHTML = `
            <div style=\"display: inline-flex; align-items: center; gap: 10px;\">
                <span style=\"font-size: 14px; color: #666;\">Trier par:</span>
                <select id=\"sortSelect\" style=\"padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;\">
                    <option value=\"date\">Date (récent)</option>
                    <option value=\"difficulty\">Difficulté</option>
                    <option value=\"popularity\">Popularité</option>
                    <option value=\"title\">Titre (A-Z)</option>
                </select>
            </div>
        `;
        
        h2.parentNode.insertBefore(sortContainer, h2.nextSibling);
        
        document.getElementById('sortSelect').addEventListener('change', function() {
            sortProblems(this.value);
        });
    }
    
    // Initialisation complète
    document.addEventListener('DOMContentLoaded', function() {
        // Ajouter les fonctionnalités optionnelles
        addSearchAndFilters();
        addSortMenu();
        
        // Afficher les statistiques dans la console
        setTimeout(() => {
            showStats();
        }, 1000);
        
        // Ajouter des raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Ctrl+F pour focus sur la recherche
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
            
            // Échap pour réinitialiser les filtres
            if (e.key === 'Escape') {
                resetFilters();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.value = '';
                }
                document.getElementById('difficultyFilter').value = 'all';
                document.getElementById('languageFilter').value = 'all';
            }
        });
        
        // Ajouter des tooltips informatifs
        document.querySelectorAll('.status-dot').forEach(dot => {
            dot.title = dot.classList.contains('solved') ? 'Vous avez résolu ce problème' : 'Problème non résolu';
        });
        
        console.log('🚀 Page d\\'accueil initialisée avec toutes les fonctionnalités!');
    });
    
    // Fonction pour exporter les données (pour debug)
    window.exportProblemsData = function() {
        const problems = [];
        document.querySelectorAll('.publication-card').forEach(card => {
            const problem = {
                title: card.querySelector('.publication-title').textContent,
                author: card.querySelector('.username').textContent,
                date: card.querySelector('.date').textContent,
                difficulty: card.querySelector('[class*=\"difficulty-\"]').textContent,
                language: card.querySelector('.publication-stats span:first-child').textContent,
                solved: card.querySelector('.status-dot').classList.contains('solved'),
                favorites: parseInt(card.querySelector('.publication-stats span:last-child').textContent.match(/\d+/)[0])
            };
            problems.push(problem);
        });
        
        console.log('📋 Données des problèmes exportées:', problems);
        return problems;
    };
    
    // Fonction pour détecter les problèmes inactifs (sans solutions récentes)
    function detectInactiveProblems() {
        const cards = document.querySelectorAll('.publication-card');
        const inactiveProblems = [];
        
        cards.forEach(card => {
            const dateText = card.querySelector('.date').textContent;
            const solutionsCount = parseInt(card.querySelector('.publication-stats span:nth-child(2)').textContent.match(/\d+/)[0]);
            
            // Convertir la date
            const problemDate = new Date(dateText.split(' ')[2].split('/').reverse().join('-'));
            const daysSincePosted = Math.floor((new Date() - problemDate) / (1000 * 60 * 60 * 24));
            
            // Problème inactif si plus de 7 jours et moins de 2 solutions
            if (daysSincePosted > 7 && solutionsCount < 2) {
                inactiveProblems.push({
                    title: card.querySelector('.publication-title').textContent,
                    daysSincePosted: daysSincePosted,
                    solutionsCount: solutionsCount
                });
                
                // Ajouter un indicateur visuel
                const indicator = document.createElement('div');
                indicator.style.position = 'absolute';
                indicator.style.top = '10px';
                indicator.style.left = '10px';
                indicator.style.background = '#ffc107';
                indicator.style.color = 'white';
                indicator.style.padding = '4px 8px';
                indicator.style.borderRadius = '12px';
                indicator.style.fontSize = '10px';
                indicator.style.fontWeight = 'bold';
                indicator.textContent = 'INACTIF';
                indicator.title = `Posté il y a ${daysSincePosted} jours, ${solutionsCount} solution(s)`;
                
                card.style.position = 'relative';
                card.appendChild(indicator);
            }
        });
        
        if (inactiveProblems.length > 0) {
            console.log('⚠️ Problèmes inactifs détectés:', inactiveProblems);
        }
        
        return inactiveProblems;
    }
    
    // Fonction pour suggérer des problèmes similaires
    function suggestSimilarProblems(currentCard) {
        const currentTags = Array.from(currentCard.querySelectorAll('.tag')).map(tag => tag.textContent.toLowerCase());
        const currentDifficulty = currentCard.querySelector('[class*=\"difficulty-\"]').className.match(/difficulty-(\w+)/)[1];
        const currentLanguage = currentCard.querySelector('.publication-stats span:first-child').textContent.toLowerCase();
        
        const suggestions = [];
        
        document.querySelectorAll('.publication-card').forEach(card => {
            if (card === currentCard) return;
            
            const cardTags = Array.from(card.querySelectorAll('.tag')).map(tag => tag.textContent.toLowerCase());
            const cardDifficulty = card.querySelector('[class*=\"difficulty-\"]').className.match(/difficulty-(\w+)/)[1];
            const cardLanguage = card.querySelector('.publication-stats span:first-child').textContent.toLowerCase();
            
            let similarity = 0;
            
            // Points pour tags similaires
            const commonTags = currentTags.filter(tag => cardTags.includes(tag));
            similarity += commonTags.length * 3;
            
            // Points pour même difficulté
            if (currentDifficulty === cardDifficulty) similarity += 2;
            
            // Points pour même langage
            if (currentLanguage === cardLanguage) similarity += 1;
            
            if (similarity > 0) {
                suggestions.push({
                    card: card,
                    similarity: similarity,
                    title: card.querySelector('.publication-title').textContent
                });
            }
        });
        
        return suggestions.sort((a, b) => b.similarity - a.similarity).slice(0, 3);
    }
    
    // Ajouter des suggestions au survol
    document.addEventListener('DOMContentLoaded', function() {
        let suggestionTimeout;
        
        document.querySelectorAll('.publication-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                suggestionTimeout = setTimeout(() => {
                    const suggestions = suggestSimilarProblems(this);
                    if (suggestions.length > 0) {
                        console.log(`💡 Suggestions pour "${this.querySelector('.publication-title').textContent}":`, 
                                  suggestions.map(s => s.title));
                    }
                }, 1000);
            });
            
            card.addEventListener('mouseleave', function() {
                clearTimeout(suggestionTimeout);
            });
        });
        
        // Détecter les problèmes inactifs après chargement
        setTimeout(() => {
            detectInactiveProblems();
        }, 2000);
    });
    
    // Fonction pour optimiser les performances
    function optimizePerformance() {
        // Lazy loading des images
        const avatars = document.querySelectorAll('.author-avatar');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        });
        
        avatars.forEach(img => {
            if (img.src && img.src !== 'default.png') {
                img.dataset.src = img.src;
                img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjRjBGNEY4Ii8+CjxwYXRoIGQ9Ik0yMCAyMEM3LjMgMjAgNyAyMCA3IDIwUzIwIDcuMyAyMCAyMFoiIGZpbGw9IiNEREQiLz4KPC9zdmc+';
                imageObserver.observe(img);
            }
        });
        
        // Virtualisation pour de grandes listes (si plus de 50 éléments)
        const cards = document.querySelectorAll('.publication-card');
        if (cards.length > 50) {
            console.log('📦 Optimisation activée pour', cards.length, 'problèmes');
            
            // Masquer les cartes non visibles
            const cardObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.visibility = 'visible';
                    } else {
                        entry.target.style.visibility = 'hidden';
                    }
                });
            }, {
                rootMargin: '100px'
            });
            
            cards.forEach(card => cardObserver.observe(card));
        }
    }
    
    // Fonction pour sauvegarder les préférences utilisateur
    function saveUserPreferences() {
        const preferences = {
            lastSearch: document.getElementById('searchInput')?.value || '',
            lastDifficultyFilter: document.getElementById('difficultyFilter')?.value || 'all',
            lastLanguageFilter: document.getElementById('languageFilter')?.value || 'all',
            lastSort: document.getElementById('sortSelect')?.value || 'date',
            timestamp: Date.now()
        };
        
        localStorage.setItem('exacueil_preferences', JSON.stringify(preferences));
    }
    
    function loadUserPreferences() {
        const saved = localStorage.getItem('exacueil_preferences');
        if (saved) {
            try {
                const preferences = JSON.parse(saved);
                
                // Charger seulement si récent (moins de 24h)
                if (Date.now() - preferences.timestamp < 24 * 60 * 60 * 1000) {
                    setTimeout(() => {
                        const searchInput = document.getElementById('searchInput');
                        const difficultyFilter = document.getElementById('difficultyFilter');
                        const languageFilter = document.getElementById('languageFilter');
                        const sortSelect = document.getElementById('sortSelect');
                        
                        if (searchInput && preferences.lastSearch) {
                            searchInput.value = preferences.lastSearch;
                            searchProblems(preferences.lastSearch);
                        }
                        
                        if (difficultyFilter && preferences.lastDifficultyFilter !== 'all') {
                            difficultyFilter.value = preferences.lastDifficultyFilter;
                            filterByDifficulty(preferences.lastDifficultyFilter);
                        }
                        
                        if (languageFilter && preferences.lastLanguageFilter !== 'all') {
                            languageFilter.value = preferences.lastLanguageFilter;
                            filterByLanguage(preferences.lastLanguageFilter);
                        }
                        
                        if (sortSelect && preferences.lastSort !== 'date') {
                            sortSelect.value = preferences.lastSort;
                            sortProblems(preferences.lastSort);
                        }
                    }, 500);
                }
            } catch (e) {
                console.error('Erreur lors du chargement des préférences:', e);
                localStorage.removeItem('exacueil_preferences');
            }
        }
    }
    
    // Sauvegarder les préférences avant de quitter
    window.addEventListener('beforeunload', saveUserPreferences);
    
    // Sauvegarder périodiquement
    setInterval(saveUserPreferences, 30000);
    
    // Initialisation finale
    document.addEventListener('DOMContentLoaded', function() {
        // Optimiser les performances
        optimizePerformance();
        
        // Charger les préférences utilisateur
        loadUserPreferences();
        
        // Ajouter des métadonnées pour le SEO et l'accessibilité
        document.querySelectorAll('.publication-card').forEach((card, index) => {
            card.setAttribute('role', 'article');
            card.setAttribute('aria-label', `Problème: ${card.querySelector('.publication-title').textContent}`);
            card.setAttribute('tabindex', '0');
            
            // Navigation au clavier
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const viewLink = this.querySelector('.btn-primary');
                    if (viewLink) {
                        viewLink.click();
                    }
                }
            });
        });
        
        // Ajouter un indicateur de chargement
        const loadingIndicator = document.createElement('div');
        loadingIndicator.id = 'loadingIndicator';
        loadingIndicator.style.display = 'none';
        loadingIndicator.style.position = 'fixed';
        loadingIndicator.style.top = '50%';
        loadingIndicator.style.left = '50%';
        loadingIndicator.style.transform = 'translate(-50%, -50%)';
        loadingIndicator.style.background = 'rgba(0,0,0,0.8)';
        loadingIndicator.style.color = 'white';
        loadingIndicator.style.padding = '20px';
        loadingIndicator.style.borderRadius = '8px';
        loadingIndicator.style.zIndex = '9999';
        loadingIndicator.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Chargement...';
        document.body.appendChild(loadingIndicator);
        
        console.log('✅ Initialisation complète de la page d\\'accueil terminée!');
        console.log('🎯 Fonctionnalités disponibles: recherche, filtres, tri, suggestions, optimisations');
        console.log('⌨️ Raccourcis: Ctrl+F (recherche), Échap (réinitialiser)');
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
        
        // Afficher un message de succès
        showMessage('Solution soumise avec succès!', 'success');
    });
</script>
<?php 
    // Nettoyer les variables de session
    unset($_SESSION['solution_submitted']);
    unset($_SESSION['submitted_problem_id']);
endif; 
?>
