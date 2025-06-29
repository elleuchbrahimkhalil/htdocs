<?php
// Vérifier si l'utilisateur est connecté (cette vérification devrait être faite dans les pages qui incluent ce menu)
// require_once 'verification.php';
// requireLogin();
?>
<!-- Navigation Bar -->
<div class="navbar">
    <div class="navbar-content">
        <div class="navbar-logo">CodeChallenge</div>
        <ul class="navbar-menu">
            <li><a href="exacueil.php">Accueil</a></li>
            <li><a href="expublier.php">Publier un problème</a></li>
            <li><a href="user_feedback.php">Notifications des Problèmes</a></li>

            <?php if (isLoggedIn()): ?>
            <li><a href="favorites.php?favorites=1">Mes Favoris</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<style>
    /* Navbar styles */
    .navbar {
        background-color: #333;
        color: white;
        padding: 15px 20px;
        width: 100%;
        box-sizing: border-box;
    }

    .navbar-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
    }

    .navbar-logo {
        font-size: 1.5em;
        font-weight: bold;
    }

    .navbar-menu {
        display: flex;
        list-style-type: none;
        margin: 0;
        padding: 0;
    }

    .navbar-menu li {
        margin-left: 20px;
    }

    .navbar-menu li a {
        color: white;
        text-decoration: none;
        padding: 5px 10px;
    }

    .navbar-menu li a:hover {
        background-color: #555;
    }
</style>
