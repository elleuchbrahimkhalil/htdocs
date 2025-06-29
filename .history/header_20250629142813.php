<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
require_once 'verification.php';

// Récupérer les messages de session
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Récupérer les notifications si l'utilisateur est connecté
$notifications = [];
$unread_count = 0;
if (isLoggedIn()) {
    require_once 'db_connect.php';
    try {
        $conn = connect();
        if ($conn) {
            $user = getCurrentUser();
            $stmt = $conn->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$user['id']]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Compter les notifications non lues
            $stmt = $conn->prepare("
                SELECT COUNT(*) as unread_count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $unread_count = $result['unread_count'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Erreur récupération notifications: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'CodeChallenge'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: #f8f9fa;
            line-height: 1.6;
        }

        .main-container {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 280px;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            color: white;
        }

        .content {
            flex: 1;
            padding: 30px;
            background-color: #f8f9fa;
        }

        .sidebar h2 {
            font-size: 1.4em;
            margin: 0 0 20px 0;
            color: white;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            padding-bottom: 12px;
            font-weight: 600;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0 0 30px 0;
        }

        .sidebar ul li {
            margin: 8px 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .sidebar ul li a:before {
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

        .sidebar ul li a:hover:before {
            left: 0;
        }

        .sidebar ul li a:hover {
            color: white;
            transform: translateX(5px);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .sidebar ul li a i {
            font-size: 1.1em;
            width: 20px;
            text-align: center;
            z-index: 1;
            position: relative;
        }

        .sidebar ul li a span {
            z-index: 1;
            position: relative;
        }

        /* Notifications dans la sidebar */
        .notifications-section {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .notifications-title {
            font-size: 1.1em;
            font-weight: 600;
            color: white;
            margin: 0;
        }

        .notification-badge {
            background: #ff4757;
            color: white;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 0.8em;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
        }

        .notification-item {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            border-left: 3px solid #ffd700;
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(3px);
        }

        .notification-item.unread {
            border-left-color: #ff4757;
            background: rgba(255,71,87,0.1);
        }

        .notification-text {
            font-size: 0.9em;
            color: rgba(255,255,255,0.9);
            margin-bottom: 5px;
        }

        .notification-date {
            font-size: 0.75em;
            color: rgba(255,255,255,0.6);
        }

        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95em;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #56ab2f, #a8e6cf);
            color: white;
            box-shadow: 0 4px 15px rgba(86, 171, 47, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(86, 171, 47, 0.4);
        }

        .alert {
            padding: 16px 20px;
            margin-bottom: 25px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .alert-success {
            color: #155724;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            color: #721c24;
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border-left: 4px solid #dc3545;
        }

        .difficulty-easy {
            color: #28a745;
            font-weight: 600;
        }

        .difficulty-medium {
            color: #ffc107;
            font-weight: 600;
        }

        .difficulty-hard {
            color: #dc3545;
            font-weight: 600;
        }

        /* Styles pour les liens externes */
        .external-link {
            position: relative;
        }

        .external-link:after {
            content: '↗';
            position: absolute;
            right: 10px;
            opacity: 0.7;
            font-size: 0.8em;
        }

        /* Section spéciale pour les actions utilisateur */
        .user-actions {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 20px;
            }

            .content {
                padding: 20px;
            }

            .notifications-section {
                padding: 15px;
            }

            .sidebar h2 {
                font-size: 1.2em;
            }
        }

        /* Animation pour les notifications */
        @keyframes notificationPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .notification-item.new {
            animation: notificationPulse 2s ease-in-out;
        }

        /* Scrollbar personnalisée pour la sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        /* CSS spécifique à la page */
        <?php echo isset($additional_css) ? $additional_css : ''; ?>
    </style>
</head>
<body>
    <?php include 'exmenu.php'; ?>

    <div class="main-container">
        <div class="sidebar">
            <!-- Section Notifications -->
            <?php if (isLoggedIn() && (!empty($notifications) || $unread_count > 0)): ?>
            <div class="notifications-section">
                <div class="notifications-header">
                    <h3 class="notifications-title">
                        <i class="fas fa-bell"></i> Notifications
                    </h3>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?= $unread_count ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($notifications)): ?>
                    <div class="notification-item">
                        <div class="notification-text">Aucune notification</div>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                        <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
                            <div class="notification-text">
                                <?= htmlspecialchars($notification['message']) ?>
                            </div>
                            <div class="notification-date">
                                <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($notifications) > 3): ?>
                        <div style="text-align: center; margin-top: 10px;">
                            <a href="user_feedback.php" style="color: rgba(255,255,255,0.8); font-size: 0.9em;">
                                Voir toutes les notifications (<?= count($notifications) ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Formation Générale -->
            <h2><i class="fas fa-graduation-cap"></i> Formation Générale</h2>
            <ul>
                <li><a href="https://www.hackerrank.com/" target="_blank" class="external-link">
                    <i class="fas fa-laptop-code"></i> <span>HackerRank</span>
                </a></li>
                <li><a href="https://www.codewars.com/" target="_blank" class="external-link">
                    <i class="fas fa-code"></i> <span>Codewars</span>
                </a></li>
                <li><a href="https://www.leetcode.com/" target="_blank" class="external-link">
                    <i class="fas fa-file-code"></i> <span>LeetCode</span>
                </a></li>
                <li><a href="https://www.topcoder.com/" target="_blank" class="external-link">
                    <i class="fas fa-trophy"></i> <span>TopCoder</span>
                </a></li>
            </ul>
            
            <!-- Compétitions Mondiales -->
            <h2><i class="fas fa-globe-americas"></i> Compétitions Mondiales</h2>
            <ul>
                <li><a href="https://icpc.global/" target="_blank" class="external-link">
                    <i class="fas fa-globe"></i> <span>ICPC</span>
                </a></li>
                <li><a href="https://www.kaggle.com/" target="_blank" class="external-link">
                    <i class="fas fa-chart-line"></i> <span>Kaggle</span>
                </a></li>
            </ul>
            
            <!-- Compétitions Régionales -->
            <h2><i class="fas fa-map-marker-alt"></i> Compétitions Régionales</h2>
            <ul>
                <li><a href="https://www.codechef.com/" target="_blank" class="external-link">
                    <i class="fas fa-utensils"></i> <span>CodeChef</span>
                </a></li>
                <li><a href="https://www.codingame.com/" target="_blank" class="external-link">
                    <i class="fas fa-gamepad"></i> <span>CodinGame</span>
                </a></li>
            </ul>
            
            <!-- Actions Utilisateur -->
            <?php if (isLoggedIn()): ?>
            <div class="user-actions">
                <h2><i class="fas fa-user-cog"></i> Mes Actions</h2>
                <ul>
                    <li><a href="expublier.php">
                        <i class="fas fa-plus-circle"></i> <span>Publier un problème</span>
                    </a></li>
                    <li><a href="favorites.php">
                        <i class="fas fa-star"></i> <span>Mes problèmes favoris</span>
                    </a></li>
                    <li><a href="user_feedback.php">
                        <i class="fas fa-bell"></i> <span>Toutes mes notifications</span>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge" style="margin-left: auto;"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="my_solutions.php">
                        <i class="fas fa-code-branch"></i> <span>Mes solutions</span>
                    </a></li>
                    <li><a href="my_problems.php">
                        <i class="fas fa-list-alt"></i> <span>Mes problèmes publiés</span>
                    </a></li>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Ressources et Aide -->
            <h2><i class="fas fa-question-circle"></i> Ressources</h2>
            <ul>
                <li><a href="documentation.php">
                    <i class="fas fa-book"></i> <span>Documentation</span>
                </a></li>
                <li><a href="faq.php">
                    <i class="fas fa-question"></i> <span>FAQ</span>
                </a></li>
                <li><a href="tutorials.php">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Tutoriels</span>
                </a></li>
            </ul>
            
            <!-- Contact -->
            <h2><i class="fas fa-envelope"></i> Contact</h2>
            <ul>
                <li><a href="mailto:contact@codechallenge.com">
                    <i class="fas fa-envelope"></i> <span>contact@codechallenge.com</span>
                </a></li>
                <li><a href="support.php">
                    <i class="fas fa-life-ring"></i> <span>Support technique</span>
                </a></li>
                <li><a href="feedback.php">
                    <i class="fas fa-comment-dots"></i> <span>Donner un avis</span>
                </a></li>
            </ul>

            <!-- Statistiques rapides pour les utilisateurs connectés -->
            <?php if (isLoggedIn()): ?>
            <div class="user-actions" style="margin-top: 20px;">
                <h2><i class="fas fa-chart-bar"></i> Mes Statistiques</h2>
                <?php 
                $user = getCurrentUser();
                if ($user): 
                ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                    <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #ffd700;">
                            <?= htmlspecialchars($user['score']) ?>
                        </div>
                        <div style="font-size: 0.8em; opacity: 0.8;">Points</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #28a745;">
                            <?= htmlspecialchars($user['problems_solved']) ?>
                        </div>
                        <div style="font-size: 0.8em; opacity: 0.8;">Résolus</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #17a2b8;">
                            <?= htmlspecialchars($user['problems_posted']) ?>
                        </div>
                        <div style="font-size: 0.8em; opacity: 0.8;">Publiés</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #28a745;">
                            <?= htmlspecialchars($user['earnings']) ?>€
                        </div>
                        <div style="font-size: 0.8em; opacity: 0.8;">Gains</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <!-- Messages de succès et d'erreur -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Barre de navigation rapide -->
            <?php if (isLoggedIn()): ?>
            <div style="background: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span style="color: #666; font-weight: 500;">Navigation rapide:</span>
                    <a href="exacueil.php" class="btn" style="background: #f8f9fa; color: #495057; padding: 8px 15px; font-size: 0.9em;">
                        <i class="fas fa-home"></i> Accueil
                    </a>
                    <a href="expublier.php" class="btn btn-success" style="padding: 8px 15px; font-size: 0.9em;">
                        <i class="fas fa-plus"></i> Nouveau problème
                    </a>
                </div>
                
                <?php if ($unread_count > 0): ?>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="color: #ff4757; font-weight: 500;">
                        <i class="fas fa-bell"></i> <?= $unread_count ?> nouvelle(s) notification(s)
                    </span>
                    <a href="user_feedback.php" class="btn" style="background: #ff4757; color: white; padding: 6px 12px; font-size: 0.85em;">
                        Voir
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

    <!-- Script pour gérer les notifications -->
    <script>
        // Marquer les notifications comme lues après un certain temps
        document.addEventListener('DOMContentLoaded', function() {
            // Animation pour les nouvelles notifications
            const unreadNotifications = document.querySelectorAll('.notification-item.unread');
            unreadNotifications.forEach((notification, index) => {
                setTimeout(() => {
                    notification.classList.add('new');
                }, index * 200);
            });

            // Auto-masquer les alertes après 5 secondes
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    alert.style.transition = 'all 0.5s ease';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }, 5000);
            });

            // Effet de survol pour les liens de la sidebar
            const sidebarLinks = document.querySelectorAll('.sidebar a');
            sidebarLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });
                
                link.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });

            // Marquer automatiquement les notifications comme lues après 10 secondes
            <?php if (isLoggedIn() && $unread_count > 0): ?>
            setTimeout(() => {
                fetch('mark_notifications_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'mark_viewed'
                    })
                }).catch(error => {
                    console.log('Erreur lors du marquage des notifications:', error);
                });
            }, 10000);
            <?php endif; ?>
        });

        // Fonction utilitaire pour afficher des messages
        function showMessage(message, type = 'info') {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'alert alert-' + (type === 'error' ? 'danger' : type);
            messageDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            const content = document.querySelector('.content');
            content.insertBefore(messageDiv, content.firstChild);
            
            // Auto-supprimer après 5 secondes
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                messageDiv.style.transform = 'translateY(-20px)';
                messageDiv.style.transition = 'all 0.5s ease';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 500);
            }, 5000);
        }

        console.log('✅ Header chargé avec notifications');
    </script>
