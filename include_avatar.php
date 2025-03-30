<?php
/**
 * Fichier d'inclusion pour l'avatar utilisateur
 * 
 * Incluez ce fichier à la fin de vos pages pour afficher l'avatar utilisateur
 * Exemple d'utilisation: <?php require_once 'include_avatar.php'; ?>
 */

// Vérifier si l'avatar a déjà été affiché sur cette page
if (!isset($GLOBALS['avatar_already_displayed'])) {
    // Inclure le module d'avatar s'il n'est pas déjà inclus
    if (!function_exists('displayUserAvatar')) {
        require_once 'exavatar.php';
    }
    
    // Afficher l'avatar utilisateur
    displayUserAvatar();
    
    // Marquer l'avatar comme affiché pour éviter les doublons
    $GLOBALS['avatar_already_displayed'] = true;
}
?>
