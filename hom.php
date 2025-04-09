<?php
$prenom = isset($_POST['fname']) ? $_POST['fname'] : '';
$mdp = isset($_POST['mdp']) ? $_POST['mdp'] : '';
$association = isset($_POST['association']) ? $_POST['association'] : '';
$exactday_lundi = isset($_POST['exactday_lundi']) ? $_POST['exactday_lundi'] : '';
$exactday_mardi = isset($_POST['exactday_mardi']) ? $_POST['exactday_mardi'] : '';
$exactday_jeudi = isset($_POST['exactday_jeudi']) ? $_POST['exactday_jeudi'] : '';

// Vérification des champs obligatoires
if (empty($prenom) || empty($mdp) || empty($association) || (empty($exactday_lundi) && empty($exactday_mardi) && empty($exactday_jeudi))) {
    echo "Erreur : Veuillez remplir tous les champs obligatoires.";
} else {
    // Affichage des informations
    echo "Informations du formulaire : <br>";
    echo "Prénom : $prenom <br>";
    echo "Mot de passe : $mdp <br>";
    echo "Association : $association <br>";
    echo "Jours sélectionnés : ";
    if (!empty($exactday_lundi)) {
        echo "Lundi ";
    }
    if (!empty($exactday_mardi)) {
        echo "Mardi ";
    }
    if (!empty($exactday_jeudi)) {
        echo "Jeudi ";
    }
}
