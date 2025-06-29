<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
require_once 'verification.php';
require_once 'db_connect.php';

// Récupérer les informations de l'utilisateur si connecté
$user = null;
$notifications_count = 0;
$unread_payments = 0;

if (isLoggedIn()) {
    $user = getCurrentUser();
    
    // Compter les notifications non lues
    try {
        $conn = connect();
        if ($conn) {
            // Notifications générales
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user['id']]);
            $result = $stmt->fetch();
            $notifications_count = $result['count'] ?? 0;
            
            // Paiements non consultés
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM payments 
                WHERE payer_id = ? AND viewed = 0
            ");
            $stmt->execute([$user['id']]);
            $result = $stmt->fetch();
            $unread_payments = $result['count'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Erreur notifications header: " . $e->getMessage());
    }
}

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

$info_message = '';
if (isset($_SESSION['info_message'])) {
    $info_message = $_SESSION['info_message'];
    unset($_SESSION['info_message']);
}

// Messages de paiement spéciaux
$payment_success = '';
if (isset($_SESSION['payment_success_message'])) {
    $payment_success = $_SESSION['payment_success_message'];
    unset($_SESSION['payment_success_message']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - CodeChallenge' : 'CodeChallenge - Plateforme de Défis de Programmation'; ?></title>
    
    <!-- Meta tags pour SEO -->
    <meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Plateforme de défis de programmation avec système de paiement intégré'; ?>">
    <meta name="keywords" content="programmation, défis, code, algorithmes, paiement, solutions">
    <meta name="author" content="CodeChallenge">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 8px;
            --box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--secondary-color);
            background-color: var(--light-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Barre de navigation principale */
        .top-navbar {
            background: linear-gradient(135deg, var(--primary-color), #2980b9);
            color: white;
            padding: 0.5rem 0;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 1rem;
            align-items: center;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .nav-link.active {
            background-color: rgba(255,255,255,0.2);
        }

        /* Badge de notification */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Container principal */
        .main-container {
            display: flex;
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            gap: 2rem;
            padding: 2rem 1rem;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .sidebar h2 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar ul {
            list-style: none;
            margin-bottom: 2rem;
        }

        .sidebar ul li {
            margin: 0.5rem 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            position: relative;
        }

        .sidebar ul li a:hover {
            background-color: var(--light-color);
            transform: translateX(5px);
            color: var(--primary-color);
        }

        .sidebar ul li a.active {
            background-color: var(--primary-color);
            color: white;
        }

        /* Zone de contenu */
        .content {
            flex: 1;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            min-height: 500px;
        }

        .content h1, .content h2 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Messages d'alerte */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
            border-radius: var(--border-radius);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideInDown 0.5s ease-out;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }

        .alert-payment {
            color: #155724;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-color: #27ae60;
            border-left: 4px solid var(--success-color);
        }

        /* Boutons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #219653;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Badges de difficulté */
        .difficulty-easy {
            background-color: #d5f5e3;
            color: var(--success-color);
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .difficulty-medium {
            background-color: #fef9e7;
            color: var(--warning-color);
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .difficulty-hard {
            background-color: #fdedec;
            color: var(--danger-color);
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Menu utilisateur mobile */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Dropdown menu */
        .dropdown {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            min-width: 200px;
            padding: 0.5rem 0;
            display: none;
            z-index: 1001;
        }

        .dropdown-menu.show {
            display: block;
            animation: slideInDown 0.3s ease-out;
        }

        .dropdown-item {
            display: block;
            padding: 0.5rem 1rem;
            color: var(--secondary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background-color: var(--light-color);
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }
            
            .sidebar {
                width: 100%;
                position: relative;
                top: auto;
            }
            
            .navbar-nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, var(--primary-color), #2980b9);
                flex-direction: column;
                padding: 1rem;
                box-shadow: var(--box-shadow);
            }
            
            .navbar-nav.show {
                display: flex;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .content {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .navbar-container {
                padding: 0 0.5rem;
            }
            
            .main-container {
                padding: 0.5rem;
            }
            
            .sidebar {
                padding: 1rem;
            }
            
            .content {
                padding: 1rem;
            }
        }

        /* Animations */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Styles spécifiques à la page */
        <?php echo isset($additional_css) ? $additional_css : ''; ?>
    </style>
</head>
<body>
    <!-- Barre de navigation principale -->
    <nav class="top-navbar">
        <div class="navbar-container">
            <a href="exacueil.php" class="navbar-brand">
                <i class="fas fa-code"></i>
                CodeChallenge
            </a>
            
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            
            <ul class="navbar-nav" id="navbarNav">
                <li class="nav-item">
                    <a href="exacueil.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'exacueil.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        Accueil
                    </a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a href="expublier.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'expublier.php' ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i>
                            Publier
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="favorites.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'favorites.php' ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i>
                            Favoris
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="user_feedback.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_feedback.php' ? 'active' : ''; ?>">
                            <i class="fas fa-bell"></i>
                            Notifications
                            <?php if ($notifications_count > 0): ?>
                                <span class="notification-badge"><?php echo $notifications_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link" onclick="toggleDropdown(event)">
                            <i class="fas fa-credit-card"></i>
                            Paiements
                            <?php if ($unread_payments > 0): ?>
                                <span class="notification-badge"><?php echo $unread_payments; ?></span>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu">
                            <a href="payment.php" class="dropdown-item">
                                <i class="fas fa-wallet"></i>
                                Espace de Paiement
                            </a>
                            <a href="google_wallet_payment.php" class="dropdown-item">
                                <i class="fab fa-google-pay"></i>
                                Google Pay
                            </a>
                            <a href="payment_history.php" class="dropdown-item">
                                <i class="fas fa-history"></i>
                                Historique
                            </a>
                            <a href="payment_success.php" class="dropdown-item">
                                <i class="fas fa-check-circle"></i>
                                Mes Achats
                            </a>
                        </div>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link" onclick="toggleDropdown(event)">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user-edit"></i>
                                Mon Profil
                            </a>
                            <a href="change_name.php" class="dropdown-item">
                                <i class="fas fa-edit"></i>
                                Changer mon nom
                            </a>
                            <a href="my_solutions.php" class="dropdown-item">
                                <i class="fas fa-code-branch"></i>
                                Mes Solutions
                            </a>
                            <a href="my_problems.php" class="dropdown-item">
                                <i class="fas fa-list-alt"></i>
                                Mes Problèmes
                            </a>
                            <div style="border-top: 1px solid #eee; margin: 0.5rem 0;"></div>
                            <a href="exlogout.php" class="dropdown-item" style="color: var(--danger-color);">
                                <i class="fas fa-sign-out-alt"></i>
                                Déconnexion
                            </a>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="exlogin.php" class="nav-link">
                            <i class="fas fa-sign-in-alt"></i>
                            Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="exregister.php" class="nav-link">
                            <i class="fas fa-user-plus"></i>
                            Inscription
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="main-container">
        <div class="sidebar">
            <h2><i class="fas fa-graduation-cap"></i> Formation Générale</h2>
            <ul>
                <li><a href="https://www.hackerrank.com/" target="_blank" rel="noopener">
                    <i class="fas fa-laptop-code"></i> HackerRank
                </a></li>
                <li><a href="https://www.codewars.com/" target="_blank" rel="noopener">
                    <i class="fas fa-code"></i> Codewars
                </a></li>
                <li><a href="https://www.leetcode.com/" target="_blank" rel="noopener">
                    <i class="fas fa-file-code"></i> LeetCode
                </a></li>
                <li><a href="https://www.topcoder.com/" target="_blank" rel="noopener">
                    <i class="fas fa-trophy"></i> TopCoder
                </a></li>
            </ul>
            
            <h2><i class="fas fa-globe"></i> Compétitions Mondiales</h2>
            <ul>
                <li><a href="https://icpc.global/" target="_blank" rel="noopener">
                    <i class="fas fa-globe-americas"></i> ICPC
                </a></li>
                <li><a href="https://www.kaggle.com/" target="_blank" rel="noopener">
                    <i class="fas fa-chart-line"></i> Kaggle
                </a></li>
                <li><a href="https://codeforces.com/" target="_blank" rel="noopener">
                    <i class="fas fa-fire"></i> Codeforces
                </a></li>
            </ul>
            
            <h2><i class="fas fa-map-marker-alt"></i> Compétitions Régionales</h2>
            <ul>
                <li><a href="https://www.codechef.com/" target="_blank" rel="noopener">
                    <i class="fas fa-utensils"></i> CodeChef
                </a></li>
                <li><a href="https://www.codingame.com/" target="_blank" rel="noopener">
                    <i class="fas fa-gamepad"></i> CodinGame
                </a></li>
                <li><a href="https://atcoder.jp/" target="_blank" rel="noopener">
                    <i class="fas fa-atom"></i> AtCoder
                </a></li>
            </ul>
            
            <?php if (isLoggedIn()): ?>
            <h2><i class="fas fa-user-cog"></i> Mes Actions</h2>
            <ul>
                <li><a href="expublier.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'expublier.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i> Publier un problème
                </a></li>
                <li><a href="favorites.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'favorites.php' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i> Mes problèmes favoris
                    <?php if ($notifications_count > 0): ?>
                        <span class="notification-badge"><?php echo $notifications_count; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="user_feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user_feedback.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Notifications
                </a></li>
                <li><a href="my_solutions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_solutions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-code-branch"></i> Mes solutions
                </a></li>
            </ul>
            
            <h2><i class="fas fa-credit-card"></i> Paiements</h2>
            <ul>
                <li><a href="payment.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>">
                    <i class="fas fa-wallet"></i> Espace de paiement
                    <?php if ($unread_payments > 0): ?>
                        <span class="notification-badge"><?php echo $unread_payments; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="google_wallet_payment.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'google_wallet_payment.php' ? 'active' : ''; ?>">
                    <i class="fab fa-google-pay"></i> Google Pay
                </a></li>
                <li><a href="payment_history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'payment_history.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Historique des paiements
                </a></li>
            </ul>
            <?php endif; ?>
            
            <h2><i class="fas fa-envelope"></i> Contact</h2>
            <ul>
                <li><a href="mailto:contact@codechallenge.com">
                    <i class="fas fa-envelope"></i> contact@codechallenge.com
                </a></li>
                <li><a href="tel:+33123456789">
                    <i class="fas fa-phone"></i> +33 1 23 45 67 89
                </a></li>
                <li><a href="https://github.com/elleuchbrahimkhalil" target="_blank" rel="noopener">
                    <i class="fab fa-github"></i> GitHub
                </a></li>
            </ul>
        </div>
        
        <div class="content">
            <!-- Messages d'alerte -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success animate__animated animate__fadeInDown">
                    <i class="fas fa-check-circle"></i> 
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger animate__animated animate__fadeInDown">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($info_message)): ?>
                <div class="alert alert-info animate__animated animate__fadeInDown">
                    <i class="fas fa-info-circle"></i> 
                    <?php echo htmlspecialchars($info_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($payment_success)): ?>
                <div class="alert alert-payment animate__animated animate__fadeInDown">
                    <i class="fas fa-credit-card"></i> 
                    <?php echo htmlspecialchars($payment_success); ?>
                    <a href="payment_success.php" class="btn btn-success" style="margin-left: 1rem;">
                        <i class="fas fa-eye"></i> Voir les détails
                    </a>
                </div>
            <?php endif; ?>
            <!-- Notification de paiement en cours -->
            <?php if (isset($_SESSION['payment_pending'])): ?>
                <div class="alert alert-warning animate__animated animate__fadeInDown">
                    <i class="fas fa-clock"></i> 
                    Paiement en cours de traitement...
                    <div class="loading-spinner" style="margin-left: 1rem;"></div>
                </div>
                <?php unset($_SESSION['payment_pending']); ?>
            <?php endif; ?>

            <!-- Notification de nouvelle solution -->
            <?php if (isset($_SESSION['new_solution_notification'])): ?>
                <div class="alert alert-info animate__animated animate__fadeInDown">
                    <i class="fas fa-lightbulb"></i> 
                    <?php echo htmlspecialchars($_SESSION['new_solution_notification']); ?>
                    <a href="user_feedback.php" class="btn btn-primary" style="margin-left: 1rem;">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                </div>
                <?php unset($_SESSION['new_solution_notification']); ?>
            <?php endif; ?>

            <!-- Notification de problème publié -->
            <?php if (isset($_SESSION['problem_published'])): ?>
                <div class="alert alert-success animate__animated animate__fadeInDown">
                    <i class="fas fa-check-circle"></i> 
                    Votre problème a été publié avec succès !
                    <a href="problem.php?id=<?php echo $_SESSION['published_problem_id'] ?? ''; ?>" class="btn btn-primary" style="margin-left: 1rem;">
                        <i class="fas fa-eye"></i> Voir le problème
                    </a>
                </div>
                <?php 
                unset($_SESSION['problem_published']);
                unset($_SESSION['published_problem_id']);
                ?>
            <?php endif; ?>

            <!-- Notification de solution approuvée -->
            <?php if (isset($_SESSION['solution_approved'])): ?>
                <div class="alert alert-success animate__animated animate__fadeInDown">
                    <i class="fas fa-trophy"></i> 
                    Félicitations ! Votre solution a été approuvée et vous avez gagné <?php echo $_SESSION['points_earned'] ?? 0; ?> points !
                    <a href="user_feedback.php" class="btn btn-success" style="margin-left: 1rem;">
                        <i class="fas fa-star"></i> Voir mes récompenses
                    </a>
                </div>
                <?php 
                unset($_SESSION['solution_approved']);
                unset($_SESSION['points_earned']);
                ?>
            <?php endif; ?>

            <!-- Scripts JavaScript intégrés -->
            <script>
                // Fonction pour basculer le menu mobile
                function toggleMobileMenu() {
                    const navbarNav = document.getElementById('navbarNav');
                    navbarNav.classList.toggle('show');
                }

                // Fonction pour basculer les dropdowns
                function toggleDropdown(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    const dropdown = event.currentTarget.nextElementSibling;
                    const allDropdowns = document.querySelectorAll('.dropdown-menu');
                    
                    // Fermer tous les autres dropdowns
                    allDropdowns.forEach(menu => {
                        if (menu !== dropdown) {
                            menu.classList.remove('show');
                        }
                    });
                    
                    // Basculer le dropdown actuel
                    dropdown.classList.toggle('show');
                }

                // Fermer les dropdowns en cliquant ailleurs
                document.addEventListener('click', function(event) {
                    if (!event.target.closest('.dropdown')) {
                        document.querySelectorAll('.dropdown-menu').forEach(menu => {
                            menu.classList.remove('show');
                        });
                    }
                });

                // Fermer le menu mobile en cliquant sur un lien
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', function() {
                        document.getElementById('navbarNav').classList.remove('show');
                    });
                });

                // Auto-hide alerts après 5 secondes
                document.addEventListener('DOMContentLoaded', function() {
                    const alerts = document.querySelectorAll('.alert');
                    alerts.forEach(alert => {
                        setTimeout(() => {
                            alert.style.opacity = '0';
                            alert.style.transform = 'translateY(-20px)';
                            setTimeout(() => {
                                if (alert.parentNode) {
                                    alert.parentNode.removeChild(alert);
                                }
                            }, 300);
                        }, 5000);
                    });
                });

                // Fonction pour afficher des notifications toast
                function showToast(message, type = 'info', duration = 4000) {
                    const toast = document.createElement('div');
                    toast.className = `alert alert-${type} animate__animated animate__fadeInRight`;
                    toast.style.position = 'fixed';
                    toast.style.top = '20px';
                    toast.style.right = '20px';
                    toast.style.zIndex = '9999';
                    toast.style.maxWidth = '400px';
                    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                    
                    let icon = 'fas fa-info-circle';
                    switch(type) {
                        case 'success': icon = 'fas fa-check-circle'; break;
                        case 'danger': icon = 'fas fa-exclamation-circle'; break;
                        case 'warning': icon = 'fas fa-exclamation-triangle'; break;
                    }
                    
                    toast.innerHTML = `<i class="${icon}"></i> ${message}`;
                    document.body.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.classList.remove('animate__fadeInRight');
                        toast.classList.add('animate__fadeOutRight');
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    }, duration);
                }

                // Fonction pour mettre à jour les badges de notification
                function updateNotificationBadges() {
                    fetch('api/get_notifications_count.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Mettre à jour les badges
                                const notificationBadges = document.querySelectorAll('.notification-badge');
                                notificationBadges.forEach(badge => {
                                    const parent = badge.closest('.nav-link');
                                    if (parent && parent.href.includes('user_feedback.php')) {
                                        badge.textContent = data.notifications_count;
                                        badge.style.display = data.notifications_count > 0 ? 'flex' : 'none';
                                    } else if (parent && parent.href.includes('payment')) {
                                        badge.textContent = data.payments_count;
                                        badge.style.display = data.payments_count > 0 ? 'flex' : 'none';
                                    }
                                });
                            }
                        })
                        .catch(error => console.error('Erreur lors de la mise à jour des notifications:', error));
                }

                // Mettre à jour les notifications toutes les 30 secondes
                if (<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
                    setInterval(updateNotificationBadges, 30000);
                }

                // Fonction pour marquer les notifications comme lues
                function markNotificationsAsRead() {
                    fetch('api/mark_notifications_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateNotificationBadges();
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
                }

                // Marquer les notifications comme lues quand on visite la page des notifications
                if (window.location.pathname.includes('user_feedback.php')) {
                    setTimeout(markNotificationsAsRead, 2000);
                }

                // Fonction pour gérer les erreurs de chargement d'images
                document.addEventListener('DOMContentLoaded', function() {
                    const images = document.querySelectorAll('img');
                    images.forEach(img => {
                        img.addEventListener('error', function() {
                            if (!this.src.includes('default.png')) {
                                this.src = 'assets/images/default.png';
                            }
                        });
                    });
                });

                // Fonction pour copier du texte dans le presse-papiers
                function copyToClipboard(text, successMessage = 'Copié dans le presse-papiers!') {
                    navigator.clipboard.writeText(text).then(function() {
                        showToast(successMessage, 'success');
                    }).catch(function(err) {
                        console.error('Erreur lors de la copie:', err);
                        showToast('Impossible de copier le texte', 'danger');
                    });
                }

                // Fonction pour formater les nombres
                function formatNumber(num) {
                    if (num >= 1000000) {
                        return (num / 1000000).toFixed(1) + 'M';
                    } else if (num >= 1000) {
                        return (num / 1000).toFixed(1) + 'K';
                    }
                    return num.toString();
                }

                // Fonction pour formater les dates
                function formatDate(dateString) {
                    const date = new Date(dateString);
                    const now = new Date();
                    const diffTime = Math.abs(now - date);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    if (diffDays === 1) {
                        return 'Hier';
                    } else if (diffDays < 7) {
                        return `Il y a ${diffDays} jours`;
                    } else {
                        return date.toLocaleDateString('fr-FR');
                    }
                }

                // Fonction pour valider les formulaires
                function validateForm(formElement) {
                    const requiredFields = formElement.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    });
                    
                    return isValid;
                }

                // Ajouter des styles pour les champs invalides
                const style = document.createElement('style');
                style.textContent = `
                    .is-invalid {
                        border-color: var(--danger-color) !important;
                        box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25) !important;
                    }
                    
                    .loading {
                        opacity: 0.6;
                        pointer-events: none;
                    }
                    
                    .loading::after {
                        content: '';
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        width: 20px;
                        height: 20px;
                        margin: -10px 0 0 -10px;
                        border: 2px solid #f3f3f3;
                        border-top: 2px solid var(--primary-color);
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                    }
                `;
                document.head.appendChild(style);

                // Debug mode
                <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                console.log('🔍 Mode débogage activé');
                console.log('👤 Utilisateur connecté:', <?php echo isLoggedIn() ? 'true' : 'false'; ?>);
                <?php if (isLoggedIn()): ?>
                console.log('📊 Notifications:', <?php echo $notifications_count; ?>);
                console.log('💳 Paiements non lus:', <?php echo $unread_payments; ?>);
                <?php endif; ?>
                <?php endif; ?>

                console.log('✅ Header scripts chargés');
            </script>

            <!-- Contenu spécifique de la page -->
