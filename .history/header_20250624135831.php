<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
$is_logged_in = isLoggedIn();
$current_user = null;
$notifications = [];
$notification_count = 0;

if ($is_logged_in) {
    $current_user = getCurrentUser();
    
    // Récupérer les notifications pour l'utilisateur connecté
    try {
        $pdo = connect();
        if ($pdo && $current_user) {
            // Notifications pour les solutions reçues (en attente)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                WHERE p.user_id = ? AND s.user_id != ? AND s.status = 'pending'
            ");
            $stmt->execute([$current_user['id'], $current_user['id']]);
            $pending_solutions = $stmt->fetchColumn();
            
            // Notifications pour les solutions acceptées (à payer)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                LEFT JOIN payments pay ON s.id = pay.solution_id
                WHERE p.user_id = ? AND s.user_id != ? AND s.status = 'accepted' AND pay.id IS NULL
            ");
            $stmt->execute([$current_user['id'], $current_user['id']]);
            $to_pay_solutions = $stmt->fetchColumn();
            
            // Notifications pour les nouvelles solutions achetées
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM payments pay
                WHERE pay.payer_id = ? AND pay.status = 'completed' 
                AND pay.payment_date > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$current_user['id']]);
            $recent_purchases = $stmt->fetchColumn();
            
            $notification_count = $pending_solutions + $to_pay_solutions + $recent_purchases;
            
            if ($pending_solutions > 0) {
                $notifications[] = [
                    'type' => 'pending',
                    'count' => $pending_solutions,
                    'message' => $pending_solutions . ' solution(s) en attente de validation',
                    'link' => 'user_feedback.php#received',
                    'icon' => 'fas fa-clock'
                ];
            }
            
            if ($to_pay_solutions > 0) {
                $notifications[] = [
                    'type' => 'payment',
                    'count' => $to_pay_solutions,
                    'message' => $to_pay_solutions . ' solution(s) acceptée(s) à payer',
                    'link' => 'user_feedback.php#received',
                    'icon' => 'fas fa-credit-card'
                ];
            }
            
            if ($recent_purchases > 0) {
                $notifications[] = [
                    'type' => 'purchase',
                    'count' => $recent_purchases,
                    'message' => $recent_purchases . ' nouvelle(s) solution(s) achetée(s)',
                    'link' => 'user_feedback.php#purchased',
                    'icon' => 'fas fa-shopping-cart'
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des notifications: " . $e->getMessage());
    }
}

// Définir le titre de la page si non défini
if (!isset($page_title)) {
    $page_title = "CodeShare - Plateforme de partage de code";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- CSS externes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    
    <!-- CSS principal -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            height: 70px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: bold;
            font-size: 24px;
        }
        
        .logo i {
            color: #3498db;
            font-size: 28px;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 30px;
            list-style: none;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 500;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-link:hover {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: #3498db;
            color: white;
        }
        
        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .notification-bell {
            position: relative;
            background: none;
            border: none;
            font-size: 20px;
            color: #666;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .notification-bell:hover {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }
        
        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1001;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .notification-dropdown.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #333;
            transition: background 0.3s ease;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
        }
        
        .notification-icon.pending {
            background: #f39c12;
        }
        
        .notification-icon.payment {
            background: #27ae60;
        }
        
        .notification-icon.purchase {
            background: #3498db;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-message {
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .notification-time {
            font-size: 12px;
            color: #666;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #3498db;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 250px;
            display: none;
            z-index: 1001;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .user-dropdown.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        .user-info {
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        
        .user-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .user-email {
            font-size: 14px;
            color: #666;
        }
        
        .user-menu-item {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: #333;
            border-bottom: 1px solid #f8f9fa;
            transition: background 0.3s ease;
        }
        
        .user-menu-item:hover {
            background: #f8f9fa;
        }
        
        .user-menu-item:last-child {
            border-bottom: none;
            border-radius: 0 0 12px 12px;
        }
        
        .user-menu-item i {
            width: 20px;
            margin-right: 10px;
            color: #666;
        }
        
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #219653;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .btn-outline:hover {
            background: #f8f9fa;
            border-color: #3498db;
            color: #3498db;
        }
        
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #2c3e50;
            cursor: pointer;
        }
        
        .field-error {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                padding: 0 15px;
            }
            
            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                gap: 10px;
            }
            
            .nav-menu.show {
                display: flex;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .main-content {
                margin: 10px;
                padding: 20px 15px;
                border-radius: 15px;
            }
            
            .notification-dropdown,
            .user-dropdown {
                width: 280px;
                right: -50px;
            }
            
            .user-menu {
                gap: 10px;
            }
            
            .logo {
                font-size: 20px;
            }
            
            .logo i {
                font-size: 24px;
            }
        }
        
        /* Animations supplémentaires */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .notification-badge {
            animation: pulse 2s infinite;
        }
        
        /* Styles pour les formulaires */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        /* Styles pour les tableaux */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        /* Styles pour les cartes */
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .card-title {
            margin: 0;
            color: #2c3e50;
            font-size: 20px;
            font-weight: bold;
        }
        
        /* Styles pour les badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-primary {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-success {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .badge-warning {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .badge-danger {
            background: #ffebee;
            color: #d32f2f;
        }
        
        /* Styles pour les tooltips */
        .tooltip {
            position: relative;
            cursor: help;
        }
        
        .tooltip::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .tooltip::after {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #333;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .tooltip:hover::before,
        .tooltip:hover::after {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(-5px);
        }
        
        /* CSS additionnel spécifique à la page */
        <?php echo isset($additional_css) ? $additional_css : ''; ?>
    </style>
    
    <!-- Scripts externes -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="exacueil.php" class="logo">
                <i class="fas fa-code"></i>
                CodeShare
            </a>
            
            <?php if ($is_logged_in): ?>
                <nav class="nav-menu" id="nav-menu">
                    <li class="nav-item">
                        <a href="exacueil.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'exacueil.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="expublier.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'expublier.php' ? 'active' : ''; ?>">
                            <i class="fas fa-plus"></i> Publier
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="favorites.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'favorites.php' ? 'active' : ''; ?>">
                            <i class="fas fa-heart"></i> Favoris
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="user_feedback.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_feedback.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i> Profil
                        </a>
                    </li>
                </nav>
                
                <div class="user-menu">
                    <!-- Notifications -->
                    <div class="notification-container">
                        <button class="notification-bell" onclick="toggleNotifications()">
                            <i class="fas fa-bell"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-badge"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </button>
                        
                        <div class="notification-dropdown" id="notification-dropdown">
                            <div class="notification-header">
                                <i class="fas fa-bell"></i> Notifications
                            </div>
                            
                            <?php if (empty($notifications)): ?>
                                <div class="notification-item" style="text-align: center; color: #666;">
                                    <i class="fas fa-check-circle" style="color: #27ae60; margin-right: 10px;"></i>
                                    Aucune nouvelle notification
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <a href="<?php echo $notification['link']; ?>" class="notification-item">
                                        <div class="notification-icon <?php echo $notification['type']; ?>">
                                            <i class="<?php echo $notification['icon']; ?>"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-message">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </div>
                                            <div class="notification-time">
                                                Maintenant
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Menu utilisateur -->
                    <div class="user-container">
                        <img src="<?php echo htmlspecialchars($current_user['avatar_url'] ?? 'default-avatar.png'); ?>" 
                             alt="Avatar" class="user-avatar" onclick="toggleUserMenu()">
                        
                        <div class="user-dropdown" id="user-dropdown">
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($current_user['name'] ?? $current_user['username']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($current_user['email']); ?></div>
                            </div>
                            
                            <a href="profile.php" class="user-menu-item">
                                <i class="fas fa-user"></i> Mon Profil
                            </a>
                            <a href="user_feedback.php" class="user-menu-item">
                                <i class="fas fa-chart-line"></i> Tableau de bord
                            </a>
                            <a href="favorites.php" class="user-menu-item">
                                <i class="fas fa-heart"></i> Mes Favoris
                            </a>
                            <a href="settings.php" class="user-menu-item">
                                <i class="fas fa-cog"></i> Paramètres
                            </a>
                            <a href="logout.php" class="user-menu-item" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter?')">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
                
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="exlogin.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                    <a href="exregister.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Inscription
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>
    
    <main class="main-content">
        <?php
        // Afficher les messages de session
        if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])):
        ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php
        if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])):
        ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <?php
        if (isset($_SESSION['warning_message']) && !empty($_SESSION['warning_message'])):
        ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_SESSION['warning_message']); ?>
            </div>
            <?php unset($_SESSION['warning_message']); ?>
        <?php endif; ?>
        
        <?php
        if (isset($_SESSION['info_message']) && !empty($_SESSION['info_message'])):
        ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <?php echo htmlspecialchars($_SESSION['info_message']); ?>
            </div>
            <?php unset($_SESSION['info_message']); ?>
        <?php endif; ?>

    <script>
        // Gestion du menu mobile
        function toggleMobileMenu() {
            const navMenu = document.getElementById('nav-menu');
            navMenu.classList.toggle('show');
        }
        
        // Gestion des notifications
        function toggleNotifications() {
            const dropdown = document.getElementById('notification-dropdown');
            const userDropdown = document.getElementById('user-dropdown');
            
            // Fermer le menu utilisateur s'il est ouvert
            if (userDropdown.classList.contains('show')) {
                userDropdown.classList.remove('show');
            }
            
            dropdown.classList.toggle('show');
        }
        
        // Gestion du menu utilisateur
        function toggleUserMenu() {
            const dropdown = document.getElementById('user-dropdown');
            const notificationDropdown = document.getElementById('notification-dropdown');
            
            // Fermer les notifications si elles sont ouvertes
            if (notificationDropdown.classList.contains('show')) {
                notificationDropdown.classList.remove('show');
            }
            
            dropdown.classList.toggle('show');
        }
        
        // Fermer les dropdowns en cliquant à l'extérieur
        document.addEventListener('click', function(event) {
            const notificationContainer = document.querySelector('.notification-container');
            const userContainer = document.querySelector('.user-container');
            const notificationDropdown = document.getElementById('notification-dropdown');
            const userDropdown = document.getElementById('user-dropdown');
            const navMenu = document.getElementById('nav-menu');
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            
            // Fermer les notifications
            if (notificationDropdown && !notificationContainer.contains(event.target)) {
                notificationDropdown.classList.remove('show');
            }
            
            // Fermer le menu utilisateur
            if (userDropdown && !userContainer.contains(event.target)) {
                userDropdown.classList.remove('show');
            }
            
            // Fermer le menu mobile
            if (navMenu && !navMenu.contains(event.target) && !mobileToggle.contains(event.target)) {
                navMenu.classList.remove('show');
            }
        });
        
        // Fermer les dropdowns avec la touche Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.getElementById('notification-dropdown').classList.remove('show');
                document.getElementById('user-dropdown').classList.remove('show');
                document.getElementById('nav-menu').classList.remove('show');
            }
        });
        
        // Animation de chargement pour les liens
        document.querySelectorAll('a[href]').forEach(link => {
            link.addEventListener('click', function(e) {
                // Ne pas animer les liens externes ou les ancres
                if (this.href.startsWith('http') && !this.href.includes(window.location.hostname)) {
                    return;
                }
                if (this.href.includes('#')) {
                    return;
                }
                
                // Ajouter une animation de chargement
                const icon = this.querySelector('i');
                if (icon && !icon.classList.contains('fa-spin')) {
                    const originalClass = icon.className;
                    icon.className = 'fas fa-spinner fa-spin';
                    
                    // Restaurer l'icône après un délai
                    setTimeout(() => {
                        icon.className = originalClass;
                    }, 2000);
                }
            });
        });
        
        // Mise à jour automatique des notifications
        <?php if ($is_logged_in): ?>
        function updateNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.querySelector('.notification-badge');
                        const bell = document.querySelector('.notification-bell');
                        
                        if (data.count > 0) {
                            if (!badge) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'notification-badge';
                                newBadge.textContent = data.count;
                                bell.appendChild(newBadge);
                            } else {
                                badge.textContent = data.count;
                            }
                        } else if (badge) {
                            badge.remove();
                        }
                        
                        // Mettre à jour le contenu des notifications
                        updateNotificationContent(data.notifications);
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la mise à jour des notifications:', error);
                });
        }
        
        function updateNotificationContent(notifications) {
            const dropdown = document.getElementById('notification-dropdown');
            const header = dropdown.querySelector('.notification-header');
            
            // Supprimer les anciennes notifications
            const oldItems = dropdown.querySelectorAll('.notification-item');
            oldItems.forEach(item => item.remove());
            
            if (notifications.length === 0) {
                const emptyItem = document.createElement('div');
                emptyItem.className = 'notification-item';
                emptyItem.style.textAlign = 'center';
                emptyItem.style.color = '#666';
                emptyItem.innerHTML = '<i class="fas fa-check-circle" style="color: #27ae60; margin-right: 10px;"></i>Aucune nouvelle notification';
                dropdown.appendChild(emptyItem);
            } else {
                notifications.forEach(notification => {
                    const item = document.createElement('a');
                    item.href = notification.link;
                    item.className = 'notification-item';
                    item.innerHTML = `
                        <div class="notification-icon ${notification.type}">
                            <i class="${notification.icon}"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-message">${notification.message}</div>
                            <div class="notification-time">Maintenant</div>
                        </div>
                    `;
                    dropdown.appendChild(item);
                });
            }
        }
        
        // Mettre à jour les notifications toutes les 30 secondes
        setInterval(updateNotifications, 30000);
        <?php endif; ?>
        
        // Fonction utilitaire pour afficher des messages toast
        function showToast(message, type = 'info', duration = 3000) {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type}`;
            toast.style.position = 'fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.style.minWidth = '300px';
            toast.style.animation = 'slideInRight 0.3s ease';
            
            const icons = {
                success: 'fas fa-check-circle',
                danger: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            
            toast.innerHTML = `<i class="${icons[type] || icons.info}"></i> ${message}`;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, duration);
        }
        
        // Animations CSS pour les toasts
        const toastStyles = document.createElement('style');
        toastStyles.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(toastStyles);
        
        // Gestion des formulaires avec validation côté client
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form[data-validate="true"]');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        const errorElement = field.parentNode.querySelector('.field-error');
                        
                        if (!field.value.trim()) {
                            isValid = false;
                            field.style.borderColor = '#e74c3c';
                            
                            if (!errorElement) {
                                const error = document.createElement('div');
                                error.className = 'field-error';
                                error.innerHTML = '<i class="fas fa-exclamation-circle"></i> Ce champ est obligatoire';
                                field.parentNode.appendChild(error);
                            }
                        } else {
                            field.style.borderColor = '#ddd';
                            if (errorElement) {
                                errorElement.remove();
                            }
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        showToast('Veuillez remplir tous les champs obligatoires', 'danger');
                    }
                });
            });
        });
        
        // Fonction pour confirmer les actions dangereuses
        function confirmAction(message = 'Êtes-vous sûr de vouloir effectuer cette action?') {
            return confirm(message);
        }
        
        // Ajouter la confirmation automatique aux liens/boutons dangereux
        document.addEventListener('DOMContentLoaded', function() {
            const dangerousElements = document.querySelectorAll('[data-confirm]');
            
            dangerousElements.forEach(element => {
                element.addEventListener('click', function(e) {
                    const message = this.dataset.confirm || 'Êtes-vous sûr?';
                    if (!confirm(message)) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        });
        
        // Fonction pour copier du texte dans le presse-papiers
        function copyToClipboard(text, successMessage = 'Copié dans le presse-papiers!') {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast(successMessage, 'success');
                }).catch(() => {
                    fallbackCopyTextToClipboard(text, successMessage);
                });
            } else {
                fallbackCopyTextToClipboard(text, successMessage);
            }
        }
        
        function fallbackCopyTextToClipboard(text, successMessage) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.top = '0';
            textArea.style.left = '0';
            textArea.style.position = 'fixed';
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showToast(successMessage, 'success');
            } catch (err) {
                showToast('Impossible de copier le texte', 'danger');
            }
            
            document.body.removeChild(textArea);
        }
        
        // Fonction pour formater les nombres
        function formatNumber(num, decimals = 0) {
            return new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(num);
        }
        
        // Fonction pour formater les dates
        function formatDate(dateString, options = {}) {
            const defaultOptions = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            
            const finalOptions = { ...defaultOptions, ...options };
            return new Intl.DateTimeFormat('fr-FR', finalOptions).format(new Date(dateString));
        }
        
        // Fonction pour débouncer les événements (utile pour la recherche)
        function debounce(func, wait, immediate) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    timeout = null;
                    if (!immediate) func(...args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func(...args);
            };
        }
        
        // Fonction pour animer les compteurs
        function animateCounter(element, start, end, duration = 2000) {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= end) {
                    current = end;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 16);
        }
        
        // Initialisation des tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipElements = document.querySelectorAll('[data-tooltip]');
            
            tooltipElements.forEach(element => {
                element.classList.add('tooltip');
            });
        });
        
        // Gestion du mode sombre (si implémenté)
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark);
        }
        
        // Restaurer le mode sombre au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = localStorage.getItem('darkMode') === 'true';
            if (isDark) {
                document.body.classList.add('dark-mode');
            }
        });
        
        // Fonction pour faire défiler vers un élément
        function scrollToElement(elementId, offset = 0) {
            const element = document.getElementById(elementId);
            if (element) {
                const elementPosition = element.offsetTop - offset;
                window.scrollTo({
                    top: elementPosition,
                    behavior: 'smooth'
                });
            }
        }
        
        // Gestion des images avec lazy loading
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img[data-src]');
            
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
        });
    </script>
