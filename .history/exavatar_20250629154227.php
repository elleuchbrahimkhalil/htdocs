<?php
/**
 * Module d'avatar utilisateur avec menu interactif
 * 
 * Ce fichier fournit une fonction pour afficher un avatar utilisateur flottant
 * avec un menu déroulant contenant les informations et options de l'utilisateur.
 */

// Inclure le script de vérification si ce n'est pas déjà fait
if (!function_exists('isLoggedIn')) {
    require_once 'verification.php';
}

require_once 'db_connect.php'; // Include the database connection

function displayUserAvatar() {
    // Vérifier si l'utilisateur est connecté
    if (!isLoggedIn()) {
        return; // Ne rien afficher si l'utilisateur n'est pas connecté
    }
    
    // Récupérer les informations de l'utilisateur
    $user = getCurrentUser();
    
    // Si l'utilisateur n'existe pas, ne rien afficher
    if (!$user) {
        return;
    }
    
    // Utiliser l'avatar personnalisé de l'utilisateur ou l'avatar par défaut
    $avatarUrl = !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';

    // Générer un ID unique pour cette instance
    $uniqueId = 'avatar_' . uniqid();
    
    // Récupérer les notifications non lues pour l'indicateur
    $unread_notifications = 0;
    $solution_notifications = 0;
    try {
        $conn = connect();
        if ($conn) {
            // Compter toutes les notifications non lues
            $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $unread_notifications = (int)($result['unread_count'] ?? 0);
            
            // Compter les notifications de solutions
            $stmt = $conn->prepare("
                SELECT COUNT(*) as solution_count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                AND (message LIKE '%solution%' OR message LIKE '%soumis%' OR message LIKE '%résolu%' OR type LIKE '%solution%')
            ");
            $stmt->execute([$user['id']]);
            $solution_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $solution_notifications = (int)($solution_result['solution_count'] ?? 0);
        }
    } catch (Exception $e) {
        // Ignorer les erreurs silencieusement
    }
    
    // Code CSS pour l'avatar et le menu utilisateur
    echo '
    <style>
        /* Styles pour la modale d\'avatar */
        .avatar-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .avatar-modal-container {
            position: relative;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .avatar-modal-header {
            padding: 15px 20px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .avatar-modal-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .avatar-modal-close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }

        .avatar-modal-close:hover {
            transform: scale(1.1);
        }

        /* Bouton Pulse avec indicateur de notifications */
        .pulse-button-' . $uniqueId . ' {
            background: url("' . $avatarUrl . '") no-repeat center center;
            background-size: cover;
            width: 70px;
            height: 70px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            position: fixed;
            bottom: 25px;
            right: 25px;
            animation: pulse-' . $uniqueId . ' 2s infinite;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 1001;
            transition: transform 0.3s;
            border: 3px solid #3498db;
        }
        
        .pulse-button-' . $uniqueId . ':hover {
            transform: scale(1.05);
        }

        @keyframes pulse-' . $uniqueId . ' {
            0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(52, 152, 219, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
        }

        /* Indicateur de notifications sur l\'avatar */
        .avatar-notification-indicator {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            border: 2px solid white;
            animation: notificationPulse 2s infinite;
        }

        .avatar-notification-indicator.solution-notification {
            background: linear-gradient(45deg, #28a745, #20c997);
            border-color: #ffd700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            animation: solutionNotificationPulse 1.5s infinite;
        }

        @keyframes notificationPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @keyframes solutionNotificationPulse {
            0% { 
                transform: scale(1); 
                box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            }
            50% { 
                transform: scale(1.2); 
                box-shadow: 0 0 20px rgba(255, 215, 0, 0.8);
            }
            100% { 
                transform: scale(1); 
                box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            }
        }

        /* Menu Utilisateur (Vertical) */
        .user-menu-' . $uniqueId . ' {
            position: fixed;
            bottom: 110px;
            right: 25px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            padding: 25px 18px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            display: none;
            width: 300px;
            z-index: 1000;
            max-height: 85vh;
            overflow-y: auto;
            color: white;
            border: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .user-menu-' . $uniqueId . '.active {
            display: block;
            animation: slide-up-' . $uniqueId . ' 0.3s ease-out;
        }
        
        @keyframes slide-up-' . $uniqueId . ' {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* En-tête du Menu avec notifications */
        .user-menu-' . $uniqueId . ' .user-name {
            text-align: center;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 16px;
            font-size: 18px;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 8px;
        }

        /* Section notifications dans le menu */
        .user-menu-notifications {
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
            border-left: 4px solid #3498db;
        }

        .user-menu-notifications.has-solution-notifications {
            border-left-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }

        .notification-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .notification-count {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .notification-count.solution-count {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: 1px solid #ffd700;
        }

        .notification-text {
            font-size: 13px;
            color: rgba(255,255,255,0.9);
        }

        /* Statistiques de l\'Utilisateur */
        .user-menu-' . $uniqueId . ' .user-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            justify-content: center;
        }

        .user-menu-' . $uniqueId . ' .stat-item {
            text-align: center;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            width: calc(50% - 12px);
            box-sizing: border-box;
            transition: transform 0.2s, background 0.2s;
        }
        
        .user-menu-' . $uniqueId . ' .stat-item:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }

        .user-menu-' . $uniqueId . ' .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 4px;
        }

        .user-menu-' . $uniqueId . ' .stat-label {
            font-size: 11px;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Boutons du Menu */
        .user-menu-' . $uniqueId . ' .menu-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            width: 100%;
        }

        .user-menu-' . $uniqueId . ' .menu-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 90%;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            z-index: 1;
            margin: 0 auto;
            color: white;
        }
        
        .user-menu-' . $uniqueId . ' .menu-button:before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s;
            z-index: -1;
        }
        
        .user-menu-' . $uniqueId . ' .menu-button:hover:before {
            left: 0;
        }

        .user-menu-' . $uniqueId . ' .payment-button {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .user-menu-' . $uniqueId . ' .payment-button:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .user-menu-' . $uniqueId . ' .profile-button {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .user-menu-' . $uniqueId . ' .profile-button:hover {
            background: linear-gradient(135deg, #8e44ad, #9b59b6);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .user-menu-' . $uniqueId . ' .notification-button {
            background: linear-gradient(135deg, #e67e22, #d35400);
            position: relative;
        }

        .user-menu-' . $uniqueId . ' .notification-button:hover {
            background: linear-gradient(135deg, #d35400, #e67e22);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

             .user-menu-' . $uniqueId . ' .notification-button.has-notifications {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: 2px solid #ffd700;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.3);
        }

        .user-menu-' . $uniqueId . ' .google-pay-button {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #222, #000);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .user-menu-' . $uniqueId . ' .google-pay-button:hover {
            background: linear-gradient(135deg, #000, #222);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .user-menu-' . $uniqueId . ' .google-pay-button img {
            height: 20px;
            margin-right: 6px;
        }

        .user-menu-' . $uniqueId . ' .logout-button {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .user-menu-' . $uniqueId . ' .logout-button:hover {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Styles pour le bouton d\'upload d\'avatar */
        .user-menu-' . $uniqueId . ' .upload-avatar-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #3498db;
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }

        .user-menu-' . $uniqueId . ' .upload-avatar-button:hover {
            background: #2980b9;
            transform: translateX(-50%) scale(1.05);
        }

        .user-menu-' . $uniqueId . ' .upload-avatar-button img {
            width: 30px;
            height: 30px;
        }

        /* Badge sur les boutons du menu */
        .menu-button-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            border: 2px solid white;
        }

        .menu-button-badge.solution-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            border-color: #ffd700;
            animation: solutionNotificationPulse 1.5s infinite;
        }

        /* Responsive pour l avatar */
        @media (max-width: 768px) {
            .pulse-button-' . $uniqueId . ' {
                width: 60px;
                height: 60px;
                bottom: 20px;
                right: 20px;
            }

            .user-menu-' . $uniqueId . ' {
                width: 280px;
                bottom: 90px;
                right: 20px;
            }

            .avatar-notification-indicator {
                width: 20px;
                height: 20px;
                font-size: 10px;
            }
        }

        @media (max-width: 480px) {
            .pulse-button-' . $uniqueId . ' {
                width: 50px;
                height: 50px;
                bottom: 15px;
                right: 15px;
            }

            .user-menu-' . $uniqueId . ' {
                width: 260px;
                bottom: 75px;
                right: 15px;
                padding: 20px 15px;
            }

            .user-menu-' . $uniqueId . ' .stat-item {
                width: 100%;
                margin-bottom: 8px;
            }
        }
    </style>

    <!-- Avatar container -->
    <div id="avatar-container-' . $uniqueId . '">
        <!-- Avatar button avec indicateur de notifications -->
        <button class="pulse-button-' . $uniqueId . '" id="pulseButton_' . $uniqueId . '" aria-label="Ouvrir le menu utilisateur">
            ' . ($unread_notifications > 0 ? 
                '<span class="avatar-notification-indicator' . ($solution_notifications > 0 ? ' solution-notification' : '') . '">' . 
                $unread_notifications . '</span>' : '') . '
        </button>

        <!-- User menu -->
        <div class="user-menu-' . $uniqueId . '" id="userMenu_' . $uniqueId . '">
            <div class="upload-avatar-button" onclick="openAvatarUpload_' . $uniqueId . '()">
                <img src="https://cdn-icons-png.flaticon.com/512/3159/3159331.png" alt="Upload Avatar">
            </div>

            <div id="avatarModalOverlay_' . $uniqueId . '" class="avatar-modal-overlay">
                <div class="avatar-modal-container">
                    <div class="avatar-modal-header">
                        <h3>Télécharger un avatar</h3>
                        <span class="avatar-modal-close" onclick="closeAvatarUpload_' . $uniqueId . '()">×</span>
                    </div>
                    <iframe id="uploadFrame_' . $uniqueId . '" src="upload_avatar.php" style="width: 100%; height: 400px; border: none;"></iframe>
                </div>
            </div>

            <div class="user-name">' . htmlspecialchars($user['name']) . '</div>

            <!-- Section notifications -->
            ' . ($unread_notifications > 0 ? '
            <div class="user-menu-notifications' . ($solution_notifications > 0 ? ' has-solution-notifications' : '') . '">
                <div class="notification-summary">
                    <span class="notification-text">
                        <i class="fas fa-bell"></i> Notifications
                    </span>
                    <span class="notification-count' . ($solution_notifications > 0 ? ' solution-count' : '') . '">
                        ' . $unread_notifications . '
                    </span>
                </div>
                ' . ($solution_notifications > 0 ? '
                <div class="notification-text" style="font-size: 11px; color: #ffd700;">
                    <i class="fas fa-code"></i> ' . $solution_notifications . ' solution(s) soumise(s)
                </div>
                ' : '') . '
            </div>
            ' : '') . '

            <div class="user-stats">
                <div class="stat-item">
                    <div class="stat-value">' . htmlspecialchars($user['score']) . '</div>
                    <div class="stat-label">Score Total</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">' . htmlspecialchars($user['problems_solved']) . '</div>
                    <div class="stat-label">Problèmes Résolus</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">' . htmlspecialchars($user['problems_posted']) . '</div>
                    <div class="stat-label">Problèmes Posés</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">' . htmlspecialchars($user['earnings']) . '€</div>
                    <div class="stat-label">Gains</div>
                </div>
            </div>
            
            <div class="menu-buttons">
                <a href="user_feedback.php" class="menu-button notification-button' . ($unread_notifications > 0 ? ' has-notifications' : '') . '">
                    <i class="fas fa-bell"></i> Notifications
                    ' . ($unread_notifications > 0 ? 
                        '<span class="menu-button-badge' . ($solution_notifications > 0 ? ' solution-badge' : '') . '">' . 
                        $unread_notifications . '</span>' : '') . '
                </a>
                
                <a href="payment.php" class="menu-button payment-button">
                    <i class="fas fa-credit-card"></i> Espace de Paiement
                </a>
                
                <a href="google_wallet_payment.php" class="menu-button google-pay-button">
                    <img src="https://developers.google.com/static/pay/api/images/brand-guidelines/google-pay-mark.svg" alt="Google Pay">
                    Payer avec Google Pay
                </a>
                
                <a href="favorites.php" class="menu-button profile-button">
                    <i class="fas fa-star"></i> Mes Favoris
                </a>
                
                <a href="my_solutions.php" class="menu-button profile-button">
                    <i class="fas fa-code-branch"></i> Mes Solutions
                </a>
                
                <a href="change_name.php" class="menu-button profile-button">
                    <i class="fas fa-user-edit"></i> Changer mon nom
                </a>
                
                <a href="exlogout.php" class="menu-button logout-button">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var uniqueId = "' . $uniqueId . '";
        var buttonId = "pulseButton_" + uniqueId;
        var menuId = "userMenu_" + uniqueId;
        
        function initAvatarMenu() {
            var button = document.getElementById(buttonId);
            var menu = document.getElementById(menuId);
            
            if (!button || !menu) {
                console.error("Avatar elements not found");
                return;
            }
            
            button.addEventListener("click", function(e) {
                e.stopPropagation();
                menu.classList.toggle("active");
                
                // Marquer les notifications comme vues quand le menu s\'ouvre
                if (menu.classList.contains("active")) {
                    markNotificationsAsSeen();
                }
            });
            
            document.addEventListener("click", function(e) {
                if (!menu.contains(e.target) && !button.contains(e.target)) {
                    menu.classList.remove("active");
                }
            });
        }
        
        // Fonction pour marquer les notifications comme vues
        function markNotificationsAsSeen() {
            fetch("mark_notifications_seen.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({action: "mark_seen"})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l\'interface utilisateur
                    updateNotificationDisplay(0, 0);
                }
            })
            .catch(error => {
                console.log("Erreur lors du marquage des notifications:", error);
            });
        }
        
        // Fonction pour mettre à jour l\'affichage des notifications
        function updateNotificationDisplay(totalCount, solutionCount) {
            // Mettre à jour l\'indicateur sur l\'avatar
            var indicator = document.querySelector(".avatar-notification-indicator");
            if (totalCount > 0) {
                if (!indicator) {
                    indicator = document.createElement("span");
                    indicator.className = "avatar-notification-indicator";
                    document.getElementById(buttonId).appendChild(indicator);
                }
                indicator.textContent = totalCount;
                indicator.className = "avatar-notification-indicator" + (solutionCount > 0 ? " solution-notification" : "");
                indicator.style.display = "flex";
            } else if (indicator) {
                indicator.style.display = "none";
            }
            
            // Mettre à jour la section notifications dans le menu
            var notificationSection = document.querySelector(".user-menu-notifications");
            if (totalCount > 0) {
                if (!notificationSection) {
                    // Créer la section si elle n\'existe pas
                    var userStats = document.querySelector(".user-stats");
                    if (userStats) {
                        notificationSection = document.createElement("div");
                        notificationSection.className = "user-menu-notifications";
                        userStats.parentNode.insertBefore(notificationSection, userStats);
                    }
                }
                if (notificationSection) {
                    notificationSection.className = "user-menu-notifications" + (solutionCount > 0 ? " has-solution-notifications" : "");
                    notificationSection.innerHTML = `
                        <div class="notification-summary">
                            <span class="notification-text">
                                <i class="fas fa-bell"></i> Notifications
                            </span>
                            <span class="notification-count${solutionCount > 0 ? " solution-count" : ""}">
                                ${totalCount}
                            </span>
                        </div>
                        ${solutionCount > 0 ? `
                        <div class="notification-text" style="font-size: 11px; color: #ffd700;">
                            <i class="fas fa-code"></i> ${solutionCount} solution(s) soumise(s)
                        </div>
                        ` : ""}
                    `;
                }
            } else if (notificationSection) {
                notificationSection.style.display = "none";
            }
            
            // Mettre à jour le badge sur le bouton notifications
            var notificationButton = document.querySelector(".notification-button");
            var badge = notificationButton ? notificationButton.querySelector(".menu-button-badge") : null;
            
            if (totalCount > 0) {
                if (!badge) {
                    badge = document.createElement("span");
                    badge.className = "menu-button-badge";
                    notificationButton.appendChild(badge);
                }
                badge.textContent = totalCount;
                badge.className = "menu-button-badge" + (solutionCount > 0 ? " solution-badge" : "");
                notificationButton.className = notificationButton.className.replace(" has-notifications", "") + " has-notifications";
            } else if (badge) {
                badge.style.display = "none";
                notificationButton.className = notificationButton.className.replace(" has-notifications", "");
            }
        }
        
        // Fonction pour rafraîchir les notifications
        function refreshNotifications() {
            fetch("get_notification_count.php")
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateNotificationDisplay(data.count, data.solution_count || 0);
                    }
                })
                .catch(error => {
                    console.log("Erreur lors de la récupération des notifications:", error);
                });
        }
        
        // Fonctions pour gérer la fenêtre modale d\'upload d\'avatar
        window["openAvatarUpload_" + uniqueId] = function() {
            document.getElementById("avatarModalOverlay_" + uniqueId).style.display = "block";
            document.body.style.overflow = "hidden";
        };

        window["closeAvatarUpload_" + uniqueId] = function() {
            document.getElementById("avatarModalOverlay_" + uniqueId).style.display = "none";
            document.body.style.overflow = "auto";
        };

        // Fermer la fenêtre modale si l\'utilisateur clique en dehors
        document.addEventListener("click", function(event) {
            var overlay = document.getElementById("avatarModalOverlay_" + uniqueId);
            if (overlay && overlay.style.display === "block" && event.target === overlay) {
                window["closeAvatarUpload_" + uniqueId]();
            }
        });

        // Gestion des touches clavier pour l\'accessibilité
        document.addEventListener("keydown", function(event) {
            var menu = document.getElementById(menuId);
            var overlay = document.getElementById("avatarModalOverlay_" + uniqueId);
            
            // Fermer le menu avec Escape
            if (event.key === "Escape") {
                if (overlay && overlay.style.display === "block") {
                    window["closeAvatarUpload_" + uniqueId]();
                } else if (menu && menu.classList.contains("active")) {
                    menu.classList.remove("active");
                }
            }
        });

        // Animation d\'entrée pour les éléments du menu
        function animateMenuItems() {
            var menuItems = document.querySelectorAll("#" + menuId + " .menu-button, #" + menuId + " .stat-item");
            menuItems.forEach(function(item, index) {
                item.style.opacity = "0";
                item.style.transform = "translateY(10px)";
                setTimeout(function() {
                    item.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                    item.style.opacity = "1";
                    item.style.transform = "translateY(0)";
                }, index * 50);
            });
        }

        // Observer pour détecter quand le menu s\'ouvre
        var menuObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === "attributes" && mutation.attributeName === "class") {
                    var menu = mutation.target;
                    if (menu.classList.contains("active")) {
                        setTimeout(animateMenuItems, 100);
                    }
                }
            });
        });

        // S\'assurer que le DOM est chargé
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", function() {
                initAvatarMenu();
                var menu = document.getElementById(menuId);
                if (menu) {
                    menuObserver.observe(menu, { attributes: true });
                }
                
                // Rafraîchir les notifications au chargement
                refreshNotifications();
                
                // Rafraîchir les notifications toutes les 30 secondes
                setInterval(refreshNotifications, 30000);
            });
        } else {
            initAvatarMenu();
            var menu = document.getElementById(menuId);
            if (menu) {
                menuObserver.observe(menu, { attributes: true });
            }
            
            // Rafraîchir les notifications au chargement
            refreshNotifications();
            
            // Rafraîchir les notifications toutes les 30 secondes
            setInterval(refreshNotifications, 30000);
        }

        // Fonction pour gérer le clic sur les notifications
        function handleNotificationClick() {
            // Marquer toutes les notifications comme lues
            fetch("mark_all_notifications_read.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({action: "mark_all_read"})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationDisplay(0, 0);
                }
            })
            .catch(error => {
                console.log("Erreur lors du marquage des notifications:", error);
            });
        }

        // Ajouter l\'événement de clic sur le bouton notifications
        document.addEventListener("DOMContentLoaded", function() {
            var notificationButton = document.querySelector(".notification-button");
            if (notificationButton) {
                notificationButton.addEventListener("click", function(e) {
                    // Ne pas marquer comme lu immédiatement, laisser l\'utilisateur voir les notifications
                    setTimeout(function() {
                        // Marquer comme vues après un délai
                        markNotificationsAsSeen();
                    }, 2000);
                });
            }
        });

        // Fonction pour afficher une notification toast
        function showToast(message, type = "info") {
            var toast = document.createElement("div");
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: bold;
                z-index: 10000;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                max-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            `;
            
            switch(type) {
                case "success":
                    toast.style.background = "linear-gradient(135deg, #28a745, #20c997)";
                    break;
                case "error":
                    toast.style.background = "linear-gradient(135deg, #dc3545, #c82333)";
                    break;
                case "warning":
                    toast.style.background = "linear-gradient(135deg, #ffc107, #e0a800)";
                    break;
                default:
                    toast.style.background = "linear-gradient(135deg, #17a2b8, #138496)";
            }
            
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-${type === "success" ? "check-circle" : type === "error" ? "exclamation-circle" : type === "warning" ? "exclamation-triangle" : "info-circle"}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Animation d\'entrée
            setTimeout(function() {
                toast.style.opacity = "1";
                toast.style.transform = "translateX(0)";
            }, 100);
            
            // Animation de sortie
            setTimeout(function() {
                toast.style.opacity = "0";
                toast.style.transform = "translateX(100%)";
                setTimeout(function() {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 4000);
        }

        // Écouter les événements personnalisés pour les notifications
        document.addEventListener("newNotification", function(event) {
            var data = event.detail;
            showToast(data.message, data.type || "info");
            refreshNotifications();
        });

        // Fonction pour simuler une nouvelle notification (pour les tests)
        window["simulateNotification_" + uniqueId] = function(message, type) {
            document.dispatchEvent(new CustomEvent("newNotification", {
                detail: { message: message, type: type }
            }));
        };

        console.log("✅ Avatar utilisateur chargé avec notifications");
    })();
    </script>
    ';
}

// Si ce fichier est exécuté directement, afficher l'avatar
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Test Avatar avec Notifications</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background-color: #f5f5f5;
                min-height: 100vh;
            }
            .content {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .test-buttons {
                margin-top: 20px;
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .test-btn {
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-weight: bold;
                transition: all 0.3s;
            }
            .test-btn.primary {
                background: #3498db;
                color: white;
            }
            .test-btn.success {
                background: #28a745;
                color: white;
            }
            .test-btn.warning {
                background: #ffc107;
                color: black;
            }
            .test-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
        </style>
    </head>
    <body>
        <div class="content">
            <h1>Test Avatar avec Notifications</h1>
            <p>Ceci est une page de test pour l\'avatar utilisateur avec système de notifications.</p>
            
            <div class="test-buttons">
                <button class="test-btn primary" onclick="testNotification(\'Nouvelle notification de test!\', \'info\')">
                    Test Notification Info
                </button>
                <button class="test-btn success" onclick="testNotification(\'Solution approuvée!\', \'success\')">
                    Test Notification Succès
                </button>
                <button class="test-btn warning" onclick="testNotification(\'Attention: Nouvelle solution soumise!\', \'warning\')">
                    Test Notification Solution
                </button>
            </div>
            
            <script>
                function testNotification(message, type) {
                    // Simuler une notification
                    if (typeof window["simulateNotification_' . (isset($uniqueId) ? $uniqueId : 'test') . '"] === "function") {
                        window["simulateNotification_' . (isset($uniqueId) ? $uniqueId : 'test') . '"](message, type);
                    } else {
                        alert("Avatar non chargé ou fonction non disponible");
                    }
                }
            </script>
        </div>';
    
    displayUserAvatar();
    
    echo '</body>
    </html>';
}
?>
