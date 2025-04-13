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

        /* Bouton Pulse */
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

        /* Menu Utilisateur (Vertical) */
        .user-menu-' . $uniqueId . ' {
              position: fixed;
    bottom: 110px;
    right: 25px;
    background: linear-gradient(135deg, #2c3e50, #34495e);
    padding: 25px 18px; /* Augmenter le padding vertical */
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    display: none;
    width: 280px; /* Légèrement plus large */
    z-index: 1000;
    max-height: 85vh; /* Augmenter la hauteur maximale */
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

        /* En-tête du Menu */
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

        /* Statistiques de l Utilisateur */
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
                <a href="change_name.php" class="menu-button profile-button">
                    <i class="fas fa-user-edit"></i> Changer mon nom
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