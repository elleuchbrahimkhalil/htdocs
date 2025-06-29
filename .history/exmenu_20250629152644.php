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
                            
                            // Compter les notifications non lues
                            $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
                            $stmt->execute([$user['id']]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            $unread_count = $result['unread_count'] ?? 0;
                            
                            // Compter les notifications de solutions soumises non lues
                            $stmt = $conn->prepare("
                                SELECT COUNT(*) as solution_notifications 
                                FROM notifications 
                                WHERE user_id = ? AND is_read = 0 
                                AND (message LIKE '%solution%' OR message LIKE '%soumis%' OR message LIKE '%résolu%')
                            ");
                            $stmt->execute([$user['id']]);
                            $solution_result = $stmt->fetch(PDO::FETCH_ASSOC);
                            $solution_notifications = $solution_result['solution_notifications'] ?? 0;
                            
                            if ($unread_count > 0) {
                                $badge_class = $solution_notifications > 0 ? 'notification-badge solution-badge' : 'notification-badge';
                                echo '<span class="' . $badge_class . '" data-solution-count="' . $solution_notifications . '">' . $unread_count . '</span>';
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
    /* Navbar styles - Couleurs sombres conservées */
    .navbar {
        background-color: #333;
        color: white;
        padding: 0;
        width: 100%;
        box-sizing: border-box;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
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
        border-radius: 6px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        position: relative;
    }

    .navbar-menu li a:hover {
        background-color: #555;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .navbar-menu li a i {
        font-size: 1.1em;
    }

    /* Badge de notification standard */
    .notification-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #dc3545;
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
        border: 2px solid #333;
    }

    /* Badge spécial pour les notifications de solutions */
    .notification-badge.solution-badge {
        background: linear-gradient(45deg, #28a745, #20c997);
        border: 2px solid #ffd700;
        box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        animation: solutionPulse 1.5s infinite;
    }

    .notification-badge.solution-badge::before {
        content: '';
        position: absolute;
        top: -3px;
        left: -3px;
        right: -3px;
        bottom: -3px;
        background: linear-gradient(45deg, #ffd700, #ffed4e);
        border-radius: 50%;
        z-index: -1;
        animation: glow 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    @keyframes solutionPulse {
        0% { 
            transform: scale(1); 
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        50% { 
            transform: scale(1.15); 
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.8);
        }
        100% { 
            transform: scale(1); 
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
    }

    @keyframes glow {
        0% { opacity: 0.5; }
        50% { opacity: 1; }
        100% { opacity: 0.5; }
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
            background-color: #333;
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
            border-radius: 8px;
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
    }

    /* Style spécial pour les notifications avec badge */
    .navbar-menu li a[href="user_feedback.php"] {
        position: relative;
    }

    /* Cadre coloré pour les notifications de solutions */
    .navbar-menu li a[href="user_feedback.php"]:has(.solution-badge) {
        border: 2px solid #28a745;
        background: rgba(40, 167, 69, 0.1);
        box-shadow: 0 0 15px rgba(40, 167, 69, 0.3);
    }

    /* Fallback pour les navigateurs qui ne supportent pas :has() */
    .navbar-menu li a[href="user_feedback.php"].has-solution-notification {
        border: 2px solid #28a745;
        background: rgba(40, 167, 69, 0.1);
        box-shadow: 0 0 15px rgba(40, 167, 69, 0.3);
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

    // Ajouter la classe pour le cadre coloré des notifications de solutions
    const solutionBadge = document.querySelector('.solution-badge');
    if (solutionBadge) {
        const notificationLink = solutionBadge.closest('a');
        if (notificationLink) {
            notificationLink.classList.add('has-solution-notification');
        }
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
            link.style.background = '#555';
            link.style.borderBottom = '2px solid #ffd700';
        }
    });

    // Animation des badges de notification
    const notificationBadges = document.querySelectorAll('.notification-badge');
    notificationBadges.forEach(badge => {
        // Animation d'apparition
        badge.style.animation = badge.classList.contains('solution-badge') ? 
            'solutionPulse 1.5s infinite, fadeIn 0.5s ease-out' : 
            'pulse 2s infinite, fadeIn 0.5s ease-out';
        
        // Effet de clic
        badge.addEventListener('click', function(e) {
            e.stopPropagation();
            this.style.animation = 'none';
            this.style.transform = 'scale(1.2)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
                this.style.animation = this.classList.contains('solution-badge') ? 
                    'solutionPulse 1.5s infinite' : 
                    'pulse 2s infinite';
            }, 200);
        });
    });

    console.log('✅ Menu de navigation chargé');
});

// Fonction pour mettre à jour le compteur de notifications
function updateNotificationCount(count, solutionCount = 0) {
    const notificationLink = document.querySelector('a[href="user_feedback.php"]');
    if (notificationLink) {
        let badge = notificationLink.querySelector('.notification-badge');
        
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                notificationLink.appendChild(badge);
            }
            
            // Appliquer le style spécial si il y a des notifications de solutions
            if (solutionCount > 0) {
                badge.classList.add('solution-badge');
                badge.setAttribute('data-solution-count', solutionCount);
                notificationLink.classList.add('has-solution-notification');
                
                // Ajouter un titre informatif
                badge.title = `${count} notification(s) dont ${solutionCount} solution(s) soumise(s)`;
            } else {
                badge.classList.remove('solution-badge');
                badge.removeAttribute('data-solution-count');
                notificationLink.classList.remove('has-solution-notification');
                badge.title = `${count} notification(s)`;
            }
            
            badge.textContent = count;
            badge.style.display = 'flex';
        } else if (badge) {
            badge.style.display = 'none';
            notificationLink.classList.remove('has-solution-notification');
        }
    }
}

// Fonction pour rafraîchir les notifications
function refreshNotifications() {
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount(data.count, data.solution_count || 0);
            }
        })
        .catch(error => {
            console.log('Erreur lors de la récupération des notifications:', error);
        });
}

// Rafraîchir les notifications toutes les 30 secondes
setInterval(refreshNotifications, 30000);

// Animation CSS supplémentaire
const additionalStyles = `
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.5); }
        to { opacity: 1; transform: scale(1); }
    }
    
    /* Effet de brillance sur le logo */
    .navbar-logo:hover {
        text-shadow: 0 0 10px rgba(255,215,0,0.5);
        transform: scale(1.05);
        transition: all 0.3s ease;
    }
    
    /* Effet de vague sur les liens */
    .navbar-menu li a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: #ffd700;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    
    .navbar-menu li a:hover::after {
        width: 80%;
    }
    
    /* Style pour les liens actifs */
    .navbar-menu li a.active {
        background: #555;
        border-bottom: 2px solid #ffd700;
    }
    
    /* Animation de chargement pour les badges */
    .notification-badge.loading {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Effet spécial pour les notifications de solutions */
    .solution-badge::after {
        content: '🎯';
        position: absolute;
        top: -8px;
        right: -8px;
        font-size: 10px;
        background: #ffd700;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: bounce 2s infinite;
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-5px); }
        60% { transform: translateY(-3px); }
    }
`;

// Ajouter les styles supplémentaires
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);
</script>
