<?php
/**
 * Module d'avatar utilisateur avec menu interactif simplifié
 * 
 * Ce fichier fournit une fonction pour afficher un avatar utilisateur flottant
 * avec un menu déroulant contenant les informations et options essentielles de l'utilisateur.
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
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .avatar-modal-container {
            position: relative;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            animation: modalFadeIn 0.4s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .avatar-modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .avatar-modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .avatar-modal-close {
            color: white;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
        }

        .avatar-modal-close:hover {
            transform: scale(1.1);
            background: rgba(255,255,255,0.2);
        }

        /* Bouton Avatar Principal */
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
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            z-index: 1001;
            transition: all 0.3s ease;
            border: 4px solid #667eea;
        }
        
        .pulse-button-' . $uniqueId . ':hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(0,0,0,0.4);
        }

        @keyframes pulse-' . $uniqueId . ' {
            0% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(102, 126, 234, 0); }
            100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
        }

        /* Menu Utilisateur Simplifié */
        .user-menu-' . $uniqueId . ' {
            position: fixed;
            bottom: 115px;
            right: 25px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            padding: 30px 20px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            display: none;
            width: 320px;
            z-index: 1000;
            max-height: 80vh;
            overflow-y: auto;
            color: white;
            border: 2px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .user-menu-' . $uniqueId . '.active {
            display: block;
            animation: slide-up-' . $uniqueId . ' 0.4s ease-out;
        }
        
        @keyframes slide-up-' . $uniqueId . ' {
            from { 
                opacity: 0; 
                transform: translateY(30px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        /* Bouton de Changement d\'Avatar - PLUS VISIBLE */
        .user-menu-' . $uniqueId . ' .upload-avatar-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            border: 4px solid white;
            animation: photoButtonPulse 3s infinite;
        }

        .user-menu-' . $uniqueId . ' .upload-avatar-button:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateX(-50%) scale(1.1);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.6);
        }

        @keyframes photoButtonPulse {
            0%, 100% { 
                box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
                transform: translateX(-50%) scale(1);
            }
            50% { 
                box-shadow: 0 12px 30px rgba(102, 126, 234, 0.7);
                transform: translateX(-50%) scale(1.05);
            }
        }

        .user-menu-' . $uniqueId . ' .upload-avatar-button .camera-icon {
            font-size: 28px;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .user-menu-' . $uniqueId . ' .upload-avatar-button::after {
            content: "Changer Photo";
            position: absolute;
            bottom: -35px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }

        .user-menu-' . $uniqueId . ' .upload-avatar-button:hover::after {
            opacity: 1;
        }

        /* En-tête du Menu */
        .user-menu-' . $uniqueId . ' .user-name {
            text-align: center;
            font-weight: bold;
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 20px;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            border-bottom: 2px solid rgba(255,255,255,0.2);
            padding-bottom: 12px;
        }

        /* Statistiques de l\'Utilisateur */
        .user-menu-' . $uniqueId . ' .user-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 25px;
        }

        .user-menu-' . $uniqueId . ' .stat-item {
            text-align: center;
            padding: 15px 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-menu-' . $uniqueId . ' .stat-item:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .user-menu-' . $uniqueId . ' .stat-value {
            font-size: 22px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        .user-menu-' . $uniqueId . ' .stat-label {
            font-size: 11px;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        /* Boutons du Menu - SEULEMENT LES ESSENTIELS */
        .user-menu-' . $uniqueId . ' .menu-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
            width: 100%;
        }

        .user-menu-' . $uniqueId . ' .menu-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 95%;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            color: white;
            gap: 10px;
        }
        
        .user-menu-' . $uniqueId . ' .menu-button:before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            z-index: -1;
        }
        
        .user-menu-' . $uniqueId . ' .menu-button:hover:before {
            left: 0;
        }

        .user-menu-' . $uniqueId . ' .menu-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .user-menu-' . $uniqueId . ' .profile-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .user-menu-' . $uniqueId . ' .profile-button:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
        }

        .user-menu-' . $uniqueId . ' .logout-button {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            margin-top: 10px;
        }

        .user-menu-' . $uniqueId . ' .logout-button:hover {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
        }

        /* Responsive pour l\'avatar */
        @media (max-width: 768px) {
            .pulse-button-' . $uniqueId . ' {
                width: 65px;
                height: 65px;
                bottom: 20px;
                right: 20px;
            }

            .user-menu-' . $uniqueId . ' {
                width: 300px;
                bottom: 95px;
                right: 20px;
                padding: 25px 18px;
            }

            .user-menu-' . $uniqueId . ' .upload-avatar-button {
                width: 70px;
                height: 70px;
                top: -20px;
            }

            .user-menu-' . $uniqueId . ' .upload-avatar-button .camera-icon {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .pulse-button-' . $uniqueId . ' {
                width: 55px;
                height: 55px;
                bottom: 15px;
                right: 15px;
            }

            .user-menu-' . $uniqueId . ' {
                width: 280px;
                bottom: 80px;
                right: 15px;
                padding: 20px 15px;
            }

                       .user-menu-' . $uniqueId . ' .user-stats {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .user-menu-' . $uniqueId . ' .upload-avatar-button {
                width: 60px;
                height: 60px;
                top: -15px;
            }

            .user-menu-' . $uniqueId . ' .upload-avatar-button .camera-icon {
                font-size: 20px;
            }
        }

        /* Animation d\'entrée pour les éléments */
        .user-menu-' . $uniqueId . '.active .stat-item,
        .user-menu-' . $uniqueId . '.active .menu-button {
            animation: slideInUp 0.5s ease forwards;
            opacity: 0;
        }

        .user-menu-' . $uniqueId . '.active .stat-item:nth-child(1) { animation-delay: 0.1s; }
        .user-menu-' . $uniqueId . '.active .stat-item:nth-child(2) { animation-delay: 0.15s; }
        .user-menu-' . $uniqueId . '.active .stat-item:nth-child(3) { animation-delay: 0.2s; }
        .user-menu-' . $uniqueId . '.active .stat-item:nth-child(4) { animation-delay: 0.25s; }
        .user-menu-' . $uniqueId . '.active .menu-button:nth-child(1) { animation-delay: 0.3s; }
        .user-menu-' . $uniqueId . '.active .menu-button:nth-child(2) { animation-delay: 0.35s; }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <!-- Avatar container -->
    <div id="avatar-container-' . $uniqueId . '">
        <!-- Avatar button -->
        <button class="pulse-button-' . $uniqueId . '" id="pulseButton_' . $uniqueId . '" aria-label="Ouvrir le menu utilisateur"></button>

        <!-- User menu simplifié -->
        <div class="user-menu-' . $uniqueId . '" id="userMenu_' . $uniqueId . '">
            <!-- Bouton de changement d\'avatar plus visible -->
            <div class="upload-avatar-button" onclick="openAvatarUpload_' . $uniqueId . '()">
                <i class="fas fa-camera camera-icon"></i>
            </div>

            <!-- Modale d\'upload d\'avatar -->
            <div id="avatarModalOverlay_' . $uniqueId . '" class="avatar-modal-overlay">
                <div class="avatar-modal-container">
                    <div class="avatar-modal-header">
                        <h3><i class="fas fa-camera"></i> Changer votre photo</h3>
                        <span class="avatar-modal-close" onclick="closeAvatarUpload_' . $uniqueId . '()">×</span>
                    </div>
                    <iframe id="uploadFrame_' . $uniqueId . '" src="upload_avatar.php" style="width: 100%; height: 400px; border: none;"></iframe>
                </div>
            </div>

            <!-- Nom de l\'utilisateur -->
            <div class="user-name">
                <i class="fas fa-user"></i> ' . htmlspecialchars($user['name']) . '
            </div>

            <!-- Statistiques simplifiées -->
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
            
            <!-- Boutons essentiels seulement -->
            <div class="menu-buttons">
                <a href="change_name.php" class="menu-button profile-button">
                    <i class="fas fa-user-edit"></i> 
                    <span>Changer mon nom</span>
                </a>
                
                <a href="exlogout.php" class="menu-button logout-button">
                    <i class="fas fa-sign-out-alt"></i> 
                    <span>Déconnexion</span>
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
            
            // Gestion du clic sur l\'avatar
            button.addEventListener("click", function(e) {
                e.stopPropagation();
                menu.classList.toggle("active");
                
                // Animation des éléments lors de l\'ouverture
                if (menu.classList.contains("active")) {
                    animateMenuElements();
                }
            });
            
            // Fermer le menu en cliquant ailleurs
            document.addEventListener("click", function(e) {
                if (!menu.contains(e.target) && !button.contains(e.target)) {
                    menu.classList.remove("active");
                }
            });
        }
        
        // Animation des éléments du menu
        function animateMenuElements() {
            var elements = document.querySelectorAll("#" + menuId + " .stat-item, #" + menuId + " .menu-button");
            elements.forEach(function(element, index) {
                element.style.opacity = "0";
                element.style.transform = "translateY(15px)";
                
                setTimeout(function() {
                    element.style.transition = "opacity 0.4s ease, transform 0.4s ease";
                    element.style.opacity = "1";
                    element.style.transform = "translateY(0)";
                }, index * 80);
            });
        }
        
        // Fonctions pour gérer la modale d\'upload d\'avatar
        window["openAvatarUpload_" + uniqueId] = function() {
            var overlay = document.getElementById("avatarModalOverlay_" + uniqueId);
            overlay.style.display = "block";
            document.body.style.overflow = "hidden";
            
            // Animation d\'ouverture
            setTimeout(function() {
                overlay.style.opacity = "1";
            }, 10);
        };

        window["closeAvatarUpload_" + uniqueId] = function() {
            var overlay = document.getElementById("avatarModalOverlay_" + uniqueId);
            overlay.style.opacity = "0";
            
            setTimeout(function() {
                overlay.style.display = "none";
                document.body.style.overflow = "auto";
            }, 300);
        };

        // Fermer la modale en cliquant sur l\'overlay
        document.addEventListener("click", function(event) {
            var overlay = document.getElementById("avatarModalOverlay_" + uniqueId);
            if (overlay && overlay.style.display === "block" && event.target === overlay) {
                window["closeAvatarUpload_" + uniqueId]();
            }
        });

        // Gestion des touches clavier
        document.addEventListener("keydown", function(event) {
            var menu = document.getElementById(menuId);
            var overlay = document.getElementById("avatarModalOverlay_" + uniqueId);
            
            if (event.key === "Escape") {
                if (overlay && overlay.style.display === "block") {
                    window["closeAvatarUpload_" + uniqueId]();
                } else if (menu && menu.classList.contains("active")) {
                    menu.classList.remove("active");
                }
            }
        });

        // Effet de survol sur l\'avatar principal
        function addHoverEffects() {
            var button = document.getElementById(buttonId);
            if (button) {
                button.addEventListener("mouseenter", function() {
                    this.style.transform = "scale(1.1)";
                    this.style.boxShadow = "0 15px 40px rgba(0,0,0,0.4)";
                });
                
                button.addEventListener("mouseleave", function() {
                    this.style.transform = "scale(1)";
                    this.style.boxShadow = "0 8px 25px rgba(0,0,0,0.3)";
                });
            }
        }

        // Fonction pour afficher des messages toast
        function showToast(message, type = "info") {
            var toast = document.createElement("div");
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.4s ease;
                max-width: 320px;
                box-shadow: 0 8px 25px rgba(0,0,0,0.3);
                backdrop-filter: blur(10px);
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
                    toast.style.background = "linear-gradient(135deg, #667eea, #764ba2)";
            }
            
            var icon = type === "success" ? "check-circle" : 
                      type === "error" ? "exclamation-circle" : 
                      type === "warning" ? "exclamation-triangle" : "info-circle";
            
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-${icon}" style="font-size: 18px;"></i>
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
                }, 400);
            }, 4000);
        }

        // Écouter les messages de l\'iframe d\'upload
        window.addEventListener("message", function(event) {
            if (event.data && event.data.type === "avatar_uploaded") {
                showToast("Photo de profil mise à jour avec succès!", "success");
                window["closeAvatarUpload_" + uniqueId]();
                
                // Recharger la page après un délai pour voir la nouvelle photo
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else if (event.data && event.data.type === "avatar_error") {
                showToast("Erreur lors du téléchargement de la photo", "error");
            }
        });

        // Initialisation
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", function() {
                initAvatarMenu();
                addHoverEffects();
            });
        } else {
            initAvatarMenu();
            addHoverEffects();
        }

        // Message de bienvenue (optionnel)
        setTimeout(function() {
            if (Math.random() < 0.3) { // 30% de chance d\'afficher le message
                showToast("Cliquez sur votre avatar pour accéder aux options!", "info");
            }
        }, 2000);

        console.log("✅ Avatar utilisateur simplifié chargé");
    })();
    </script>
    ';
}

// Si ce fichier est exécuté directement, afficher l avatar
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Test Avatar Simplifié</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                margin: 0;
                padding: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                color: white;
            }
            .content {
                max-width: 800px;
                margin: 0 auto;
                background: rgba(255,255,255,0.1);
                padding: 30px;
                border-radius: 15px;
                box-shadow: 0 8px 25px rgba(0,0,0,0.2);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,255,255,0.2);
            }
            .feature-list {
                margin-top: 20px;
                padding: 0;
                list-style: none;
            }
            .feature-list li {
                padding: 10px 0;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                display: flex;
                align-items: center;
                gap: 10px;
            }
                      .feature-list li i {
                color: #ffd700;
                width: 20px;
            }
            h1 {
                text-align: center;
                margin-bottom: 20px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
            .highlight {
                background: rgba(255,215,0,0.2);
                padding: 15px;
                border-radius: 10px;
                border-left: 4px solid #ffd700;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="content">
            <h1><i class="fas fa-user-circle"></i> Avatar Utilisateur Simplifié</h1>
            
            <div class="highlight">
                <strong><i class="fas fa-info-circle"></i> Version Simplifiée</strong><br>
                Cette version ne contient que les fonctionnalités essentielles pour une meilleure expérience utilisateur.
            </div>
            
            <h3>Fonctionnalités disponibles :</h3>
            <ul class="feature-list">
                <li><i class="fas fa-camera"></i> Changement de photo de profil (bouton bien visible)</li>
                <li><i class="fas fa-chart-bar"></i> Affichage des statistiques utilisateur</li>
                <li><i class="fas fa-user-edit"></i> Modification du nom d\'utilisateur</li>
                <li><i class="fas fa-sign-out-alt"></i> Déconnexion</li>
                <li><i class="fas fa-mobile-alt"></i> Interface responsive</li>
                <li><i class="fas fa-palette"></i> Design moderne avec animations</li>
            </ul>
            
            <h3>Fonctionnalités supprimées :</h3>
            <ul class="feature-list">
                <li><i class="fas fa-times text-muted"></i> <span style="opacity: 0.7;">Mes favoris</span></li>
                <li><i class="fas fa-times text-muted"></i> <span style="opacity: 0.7;">Paiement Google Pay</span></li>
                <li><i class="fas fa-times text-muted"></i> <span style="opacity: 0.7;">Notifications</span></li>
                <li><i class="fas fa-times text-muted"></i> <span style="opacity: 0.7;">Mes solutions</span></li>
                <li><i class="fas fa-times text-muted"></i> <span style="opacity: 0.7;">Espace de paiement</span></li>
            </ul>
            
            <div class="highlight">
                <strong><i class="fas fa-camera"></i> Changement de Photo</strong><br>
                Le bouton de changement de photo est maintenant plus visible avec :
                <ul style="margin-top: 10px;">
                    <li>• Taille augmentée (80px)</li>
                    <li>• Animation pulsante</li>
                    <li>• Icône caméra claire</li>
                    <li>• Tooltip explicatif</li>
                    <li>• Bordure et ombres accentuées</li>
                </ul>
            </div>
        </div>';
    
    displayUserAvatar();
    
    echo '</body>
    </html>';
}
?>
