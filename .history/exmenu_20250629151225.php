<?php
// Vérifier si l'utilisateur est connecté (cette vérification devrait être faite dans les pages qui incluent ce menu)
// require_once 'verification.php';
// requireLogin();
?>
<!-- Navigation Bar -->
<div class="navbar">
    <div class="navbar-content">
        <div class="navbar-logo">
            <i class="fas fa-code"></i>
            <span>CodeChallenge</span>
        </div>
        <ul class="navbar-menu">
            <li><a href="exacueil.php"><i class="fas fa-home"></i> Accueil</a></li>
            <li><a href="expublier.php"><i class="fas fa-plus-circle"></i> Publier un problème</a></li>
            <li><a href="user_feedback.php"><i class="fas fa-bell"></i> Notifications
                <?php 
                // Afficher le badge de notifications si l'utilisateur est connecté
                if (function_exists('isLoggedIn') && isLoggedIn()) {
                    require_once 'db_connect.php';
                    try {
                        $conn = connect();
                        if ($conn) {
                            $user = getCurrentUser();
                            $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
                            $stmt->execute([$user['id']]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            $unread_count = $result['unread_count'] ?? 0;
                            
                            if ($unread_count > 0) {
                                echo '<span class="notification-badge">' . $unread_count . '</span>';
                            }
                        }
                    } catch (Exception $e) {
                        // Ignorer les erreurs silencieusement
                    }
                }
                ?>
            </a></li>

            <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
            <li><a href="favorites.php?favorites=1"><i class="fas fa-star"></i> Mes Favoris</a></li>
            <li><a href="my_solutions.php"><i class="fas fa-code-branch"></i> Mes Solutions</a></li>
            <?php else: ?>
            <li><a href="exlogin.php"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
            <li><a href="exregister.php"><i class="fas fa-user-plus"></i> Inscription</a></li>
            <?php endif; ?>
        </ul>
        
        <!-- Menu hamburger pour mobile -->
        <div class="navbar-toggle" id="navbar-toggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</div>

<style>
    /* Navbar styles */
    .navbar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0;
        width: 100%;
        box-sizing: border-box;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .navbar-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
        height: 70px;
    }

    .navbar-logo {
        font-size: 1.8em;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 10px;
        color: white;
        text-decoration: none;
    }

    .navbar-logo i {
        font-size: 1.2em;
        color: #ffd700;
    }

    .navbar-menu {
        display: flex;
        list-style-type: none;
        margin: 0;
        padding: 0;
        align-items: center;
    }

    .navbar-menu li {
        margin-left: 5px;
        position: relative;
    }

    .navbar-menu li a {
        color: white;
        text-decoration: none;
        padding: 12px 18px;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        position: relative;
        overflow: hidden;
    }

    .navbar-menu li a:before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transition: all 0.3s ease;
        z-index: 0;
    }

    .navbar-menu li a:hover:before {
        left: 0;
    }

    .navbar-menu li a:hover {
        background: rgba(255,255,255,0.15);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .navbar-menu li a i {
        font-size: 1.1em;
        z-index: 1;
        position: relative;
    }

    .navbar-menu li a span {
        z-index: 1;
        position: relative;
    }

    /* Badge de notification */
    .notification-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #ff4757;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 0.7em;
        font-weight: bold;
        min-width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: pulse 2s infinite;
        z-index: 2;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    /* Menu hamburger pour mobile */
    .navbar-toggle {
        display: none;
        flex-direction: column;
        cursor: pointer;
        padding: 5px;
    }

    .navbar-toggle span {
        width: 25px;
        height: 3px;
        background: white;
        margin: 3px 0;
        transition: 0.3s;
        border-radius: 2px;
    }

    /* Animation du menu hamburger */
    .navbar-toggle.active span:nth-child(1) {
        transform: rotate(-45deg) translate(-5px, 6px);
    }

    .navbar-toggle.active span:nth-child(2) {
        opacity: 0;
    }

    .navbar-toggle.active span:nth-child(3) {
        transform: rotate(45deg) translate(-5px, -6px);
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .navbar-content {
            padding: 0 15px;
            height: 60px;
        }

        .navbar-logo {
            font-size: 1.5em;
        }

        .navbar-toggle {
            display: flex;
        }

        .navbar-menu {
            position: fixed;
            top: 60px;
            left: -100%;
            width: 100%;
            height: calc(100vh - 60px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-top: 50px;
            transition: left 0.3s ease;
            z-index: 999;
        }

        .navbar-menu.active {
            left: 0;
        }

        .navbar-menu li {
            margin: 10px 0;
            width: 80%;
        }

        .navbar-menu li a {
            width: 100%;
            justify-content: center;
            padding: 15px 20px;
            font-size: 1.1em;
            border-radius: 10px;
        }

        .notification-badge {
            top: 10px;
            right: 15px;
        }
    }

    @media (max-width: 480px) {
        .navbar-content {
            padding: 0 10px;
        }

        .navbar-logo {
            font-size: 1.3em;
        }

        .navbar-logo span {
            display: none;
        }

        .navbar-menu li a {
            font-size: 1em;
            padding: 12px 15px;
        }
    }

    /* Effet de survol spécial pour les liens importants */
    .navbar-menu li a[href="expublier.php"] {
        background: rgba(255,215,0,0.1);
        border: 1px solid rgba(255,215,0,0.3);
    }

    .navbar-menu li a[href="expublier.php"]:hover {
        background: rgba(255,215,0,0.2);
        border-color: rgba(255,215,0,0.5);
        color: #ffd700;
    }

    /* Style spécial pour les notifications avec badge */
    .navbar-menu li a[href="user_feedback.php"] {
        position: relative;
    }

    /* Animation d'entrée pour la navbar */
    .navbar {
        animation: slideDown 0.5s ease-out;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Effet de focus pour l'accessibilité */
    .navbar-menu li a:focus {
        outline: 2px solid #ffd700;
        outline-offset: 2px;
    }

    /* Style pour les utilisateurs non connectés */
    .navbar-menu li a[href="exlogin.php"],
    .navbar-menu li a[href="exregister.php"] {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
    }

    .navbar-menu li a[href="exlogin.php"]:hover,
    .navbar-menu li a[href="exregister.php"]:hover {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.4);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du menu hamburger
    const navbarToggle = document.getElementById('navbar-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    
    if (navbarToggle && navbarMenu) {
        navbarToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navbarMenu.classList.toggle('active');
            
            // Empêcher le scroll du body quand le menu est ouvert
            if (navbarMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        });

        // Fermer le menu quand on clique sur un lien
        const navLinks = navbarMenu.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navbarToggle.classList.remove('active');
                navbarMenu.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
        });

        // Fermer le menu quand on clique en dehors
        document.addEventListener('click', function(event) {
            if (!navbarToggle.contains(event.target) && !navbarMenu.contains(event.target)) {
                navbarToggle.classList.remove('active');
                navbarMenu.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    }

    // Effet de survol pour les liens
    const navLinks = document.querySelectorAll('.navbar-menu li a');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Mettre en évidence le lien actuel
    const currentPage = window.location.pathname.split('/').pop();
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage) {
            link.style.background = 'rgba(255,255,255,0.2)';
            link.style.borderBottom = '2px solid #ffd700';
        }
    });

    // Animation des badges de notification
    const notificationBadges = document.querySelectorAll('.notification-badge');
    notificationBadges.forEach(badge => {
        // Animation d'apparition
        badge.style.animation = 'pulse 2s infinite, fadeIn 0.5s ease-out';
        
        // Effet de clic
        badge.addEventListener('click', function(e) {
            e.stopPropagation();
            this.style.animation = 'none';
            this.style.transform = 'scale(1.2)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
                this.style.animation = 'pulse 2s infinite';
            }, 200);
        });
    });

    console.log('✅ Menu de navigation chargé');
});

// Fonction pour mettre à jour le compteur de notifications
function updateNotificationCount(count) {
    const notificationLink = document.querySelector('a[href="user_feedback.php"]');
    if (notificationLink) {
        let badge = notificationLink.querySelector('.notification-badge');
        
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                notificationLink.appendChild(badge);
            }
            badge.textContent = count;
            badge.style.display = 'flex';
        } else if (badge) {
            badge.style.display = 'none';
        }
    }
}

// Fonction pour rafraîchir les notifications
function refreshNotifications() {
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount(data.count);
            }
        })
        .catch(error => {
            console.log('Erreur lors de la récupération des notifications:', error);
        });
}

<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    $user = getCurrentUser();
    $stmt = $conn->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$user['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'count' => (int)($result['unread_count'] ?? 0)
    ]);

} catch (Exception $e) {
    error_log("Erreur get_notification_count: " . $e->getMessage());
    echo json_encode(['success' => false, 'count' => 0]);
}
?>
