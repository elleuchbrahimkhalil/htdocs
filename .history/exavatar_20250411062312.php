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

        /* Amélioration du bouton avatar */
        .pulse-button-' . $uniqueId . ' {
            background: url("' . $avatarUrl . '") no-repeat center center;
            background-size: cover;
            width: 75px;
            height: 75px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            position: fixed;
            bottom: 25px;
            right: 25px;
            animation: pulse-' . $uniqueId . ' 2s infinite;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 1001;
            transition: transform 0.3s;
            border: 4px solid #3498db;
        }
        
        .pulse-button-' . $uniqueId . ':hover {
            transform: scale(1.05) rotate(5deg);
            border-color: #2ecc71;
        }

        /* Animation pulse améliorée */
        @keyframes pulse-' . $uniqueId . ' {
            0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(52, 152, 219, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
        }

        /* Menu utilisateur amélioré */
        .user-menu-' . $uniqueId . ' {
            position: fixed;
            bottom: 110px;
            right: 25px;
            background: linear-gradient(145deg, #2c3e50, #34495e);
            padding: 25px 18px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            display: none;
            width: 280px;
            z-index: 1000;
            max-height: 85vh;
            overflow-y: auto;
            color: white;
            border: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            backdrop-filter: blur(5px);
        }

        /* Bouton d\'upload d\'avatar amélioré */
        .user-menu-' . $uniqueId . ' .upload-avatar-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(145deg, #3498db, #2980b9);
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: all 0.3s;
            border: 3px solid rgba(255,255,255,0.8);
        }

        .user-menu-' . $uniqueId . ' .upload-avatar-button:hover {
            background: linear-gradient(145deg, #2980b9, #3498db);
            transform: translateX(-50%) scale(1.05);
            box-shadow: 0 6px 12px rgba(0,0,0,0.4);
        }

        .user-menu-' . $uniqueId . ' .upload-avatar-button img {
            width: 35px;
            height: 35px;
            filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2));
        }

        /* Nom d\'utilisateur amélioré */
        .user-menu-' . $uniqueId . ' .user-name {
            text-align: center;
            font-weight: bold;
            margin-top: 35px;
            margin-bottom: 16px;
            font-size: 20px;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            border-bottom: 1px solid rgba(255,255,255,0.15);
            padding-bottom: 10px;
            letter-spacing: 0.5px;
        }

        /* Statistiques améliorées */
        .user-menu-' . $uniqueId . ' .user-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0 20px 0;
            justify-content: center;
        }

        .user-menu-' . $uniqueId . ' .stat-item {
            text-align: center;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            width: calc(50% - 15px);
            box-sizing: border-box;
            transition: transform 0.2s, background 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .user-menu-' . $uniqueId . ' .stat-item:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        }

        .user-menu-' . $uniqueId . ' .stat-value {
            font-size: 22px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 6px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .user-menu-' . $uniqueId . ' .stat-label {
            font-size: 11px;
            color: rgba(255,255,255,0.9);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        /* Boutons du menu améliorés */
        .user-menu-' . $uniqueId . ' .menu-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
            width: 100%;
            margin-top: 10px;
        }

        .user-menu-' . $uniqueId . ' .menu-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 95%;
            padding: 12px;
            border: none;
            border-radius: 8px;
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
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            letter-spacing: 0.5px;
        }
        
        .user-menu-' . $uniqueId . ' .menu-button:before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            transition: all 0.4s;
            z-index: -1;
        }
        
        .user-menu-' . $uniqueId . ' .menu-button:hover:before {
            left: 0;
        }

        .user-menu-' . $uniqueId . ' .menu-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }

        .user-menu-' . $uniqueId . ' .payment-button {
            background: linear-gradient(145deg, #3498db, #2980b9);
        }

        .user-menu-' . $uniqueId . ' .google-pay-button {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #222, #000);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        .user-menu-' . $uniqueId . ' .google-pay-button img {
            height: 22px;
            margin-right: 8px;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3));
        }

        .user-menu-' . $uniqueId . ' .logout-button {
            background: linear-gradient(145deg, #e74c3c, #c0392b);
        }

        /* Animation d\'apparition du menu */
        @keyframes slide-up-' . $uniqueId . ' {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .user-menu-' . $uniqueId . '.active {
            display: block;
            animation: slide-up-' . $uniqueId . ' 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
    </style>

    <!-- Avatar container -->
    <div id="avatar-container-' . $uniqueId . '">
        <!-- Avatar button -->
        <button class="pulse-button-' . $uniqueId . '" id="pulseButton_' . $uniqueId . '" aria-label="Ouvrir le menu utilisateur"></button>

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
                <a href="payment.php" class="menu-button payment-button">Espace de Paiement</a>
                <a href="google_wallet_payment.php" class="menu-button google-pay-button">
                    <img src="https://developers.google.com/static/pay/api/images/brand-guidelines/google-pay-mark.svg" alt="Google Pay">
                    Payer avec Google Pay
                </a>
                <a href="exlogout.php" class="menu-button logout-button">Déconnexion</a>
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
            });
            
            document.addEventListener("click", function(e) {
                if (!menu.contains(e.target) && !button.contains(e.target)) {
                    menu.classList.remove("active");
                }
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
                closeAvatarUpload_' . $uniqueId . '();
            }
        });

        // S\'assurer que le DOM est chargé
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", initAvatarMenu);
        } else {
            initAvatarMenu();
        }
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
        <title>Test Avatar</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .content {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
        </style>
    </head>
    <body>
        <div class="content">
            <h1>Test Avatar</h1>
            <p>Ceci est une page de test pour l\'avatar utilisateur.</p>';
    
    displayUserAvatar();
    
    echo '</div>
    </body>
    </html>';
}
?>