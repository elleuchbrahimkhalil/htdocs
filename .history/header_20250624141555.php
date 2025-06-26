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
            
            $notification_count = $pending_solutions + $to_pay_solutions;
            
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
    
    <!-- CSS principal uniforme pour toutes les pages -->
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
        
        /* HEADER UNIFORME POUR TOUTES LES PAGES */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            box-shadow: 0 4px 30px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255,255,255,0.3);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            height: 80px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: bold;
            font-size: 26px;
            transition: all 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .logo i {
            color: #3498db;
            font-size: 32px;
            background: linear-gradient(45deg, #3498db, #2980b9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 35px;
            list-style: none;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            padding: 12px 18px;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(52, 152, 219, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .nav-link:hover::before {
            left: 100%;
        }
        
        .nav-link:hover {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(41, 128, 185, 0.1));
            color: #3498db;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.2);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        
        .nav-link i {
            font-size: 16px;
        }
        
        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .notification-bell {
            position: relative;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid rgba(52, 152, 219, 0.2);
            font-size: 20px;
            color: #666;
            cursor: pointer;
            padding: 12px;
            border-radius: 50%;
            transition: all 0.3s ease;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notification-bell:hover {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        
        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            animation: pulse 2s infinite;
            border: 2px solid white;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .notification-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            width: 380px;
            max-height: 450px;
            overflow-y: auto;
            display: none;
            z-index: 1001;
            border: 1px solid rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .notification-dropdown.show {
            display: block;
            animation: slideDown 0.4s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .notification-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            color: #2c3e50;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 16px 16px 0 0;
        }
        
        .notification-item {
            padding: 18px 25px;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .notification-item:hover {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            transform: translateX(5px);
        }
        
        .notification-item:last-child {
            border-bottom: none;
            border-radius: 0 0 16px 16px;
        }
        
        .notification-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        
        .notification-icon.pending {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .notification-icon.payment {
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .notification-icon.purchase {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-message {
            font-size: 14px;
            margin-bottom: 4px;
            font-weight: 500;
        }
        
        .notification-time {
            font-size: 12px;
            color: #666;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
        }
        
        .user-avatar:hover {
            transform: scale(1.15);
            box-shadow: 0 8px 30px rgba(52, 152, 219, 0.4);
            border-color: #2980b9;
        }
        
        .user-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            width: 280px;
            display: none;
            z-index: 1001;
            border: 1px solid rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .user-dropdown.show {
            display: block;
            animation: slideDown 0.4s ease;
        }
        
        .user-info {
            padding: 25px;
            border-bottom: 1px solid #eee;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 16px 16px 0 0;
        }
        
        .user-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .user-email {
            font-size: 14px;
            color: #666;
        }
        
        .user-menu-item {
            display: block;
            padding: 15px 25px;
            text-decoration: none;
            color: #333;
            border-bottom: 1px solid #f8f9fa;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .user-menu-item:hover {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #3498db;
            transform: translateX(5px);
        }
        
        .user-menu-item:last-child {
            border-bottom: none;
            border-radius: 0 0 16px 16px;
        }
        
        .user-menu-item i {
            width: 25px;
            margin-right: 12px;
            color: #666;
            transition: color 0.3s ease;
        }
        
        .user-menu-item:hover i {
            color: #3498db;
        }
        
        /* CONTENU PRINCIPAL UNIFORME */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            margin-top: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        /* TITRES UNIFORMES */
        h1 {
            color: #2c3e50;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }
        
        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 2px;
        }
        
        h1 i {
            color: #3498db;
            font-size: 28px;
        }
        
        h2 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        h2 i {
            color: #3498db;
            font-size: 20px;
        }
        
        h3 {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        /* ALERTES UNIFORMES */
        .alert {
            padding: 18px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            border: 1px solid;
            backdrop-filter: blur(10px);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(212, 237, 218, 0.9), rgba(195, 230, 203, 0.9));
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(248, 215, 218, 0.9), rgba(245, 198, 203, 0.9));
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 243, 205, 0.9), rgba(255, 234, 167, 0.9));
            color: #856404;
            border-color: #ffeaa7;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(209, 236, 241, 0.9), rgba(190, 229, 235, 0.9));
            color: #0c5460;
            border-color: #bee5eb;
        }
        
        .alert i {
            font-size: 18px;
        }
        
        /* BOUTONS UNIFORMES */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #1f618d);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #229954, #1e8449);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        }
        
        .btn-outline {
            background: rgba(255, 255, 255, 0.9);
            color: #666;
            border: 2px solid #ddd;
            backdrop-filter: blur(10px);
        }
        
        .btn-outline:hover {
            background: rgba(52, 152, 219, 0.1);
            border-color: #3498db;
            color: #3498db;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.2);
        }
        
        /* FORMULAIRES UNIFORMES */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #e0e6ed;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
            background: white;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }
        
        /* CARTES UNIFORMES */
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
        }
        
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .card-header {
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .card-title {
            margin: 0;
            color: #2c3e50;
            font-size: 22px;
            font-weight: bold;
        }
        
        /* BADGES UNIFORMES */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1976d2;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
            color: #2e7d32;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            color: #f57c00;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #d32f2f;
        }
        
        /* ERREURS DE CHAMPS */
        .field-error {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        
        /* MENU MOBILE */
        .mobile-menu-toggle {
            display: none;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid rgba(52, 152, 219, 0.2);
            font-size: 20px;
            color: #2c3e50;
            cursor: pointer;
            padding: 12px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-toggle:hover {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            transform: scale(1.05);
        }
        
        /* BOUTONS D'AUTHENTIFICATION */
        .auth-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        /* RESPONSIVE DESIGN */
        @media (max-width: 768px) {
            .header-container {
                padding: 0 15px;
                height: 70px;
            }
            
            .logo {
                font-size: 22px;
            }
            
            .logo i {
                font-size: 26px;
            }
            
            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(15px);
                flex-direction: column;
                padding: 25px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                gap: 15px;
                border-radius: 0 0 20px 20px;
            }
            
            .nav-menu.show {
                display: flex;
                animation: slideDown 0.4s ease;
            }
            
            .nav-link {
                width: 100%;
                justify-content: center;
                padding: 15px;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .main-content {
                margin: 15px;
                padding: 25px 20px;
                border-radius: 20px;
            }
            
            .notification-dropdown,
            .user-dropdown {
                width: 320px;
                right: -30px;
            }
            
            .user-menu {
                gap: 15px;
            }
            
            h1 {
                font-size: 26px;
            }
            
            h2 {
                font-size: 20px;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 13px;
            }
            
            .card {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .header-container {
                padding: 0 10px;
            }
            
            .main-content {
                margin: 10px;
                padding: 20px 15px;
            }
            
            .notification-dropdown,
            .user-dropdown {
                width: 280px;
                right: -20px;
            }
            
            h1 {
                font-size: 22px;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .auth-buttons {
                flex-direction: column;
                gap: 10px;
            }
        }
        
        /* ANIMATIONS SUPPLÉMENTAIRES */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        /* SCROLLBAR PERSONNALISÉE */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #2980b9, #1f618d);
        }
        
        /* STYLES POUR LES TOOLTIPS */
        .tooltip {
            position: relative;
            cursor: help;
        }
        
        .tooltip::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        
        .tooltip::after {
            content: '';
            position: absolute;
            bottom: 115%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.9);
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
        
        /* STYLES POUR LES TABLEAUX */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            backdrop-filter: blur(10px);
        }
        
        .table th,
        .table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tr:hover {
            background: rgba(52, 152, 219, 0.05);
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        /* STYLES POUR LES LISTES */
        .list-group {
            list-style: none;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            backdrop-filter: blur(10px);
        }
        
        .list-group-item {
            padding: 18px 25px;
            border-bottom: 1px solid #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .list-group-item:hover {
            background: rgba(52, 152, 219, 0.05);
            transform: translateX(5px);
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        /* STYLES POUR LES MODALES */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideInUp 0.3s ease;
        }
        
        @keyframes slideInUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .modal-title {
            margin: 0;
            color: #2c3e50;
            font-size: 20px;
            font-weight: bold;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: #f8f9fa;
            color: #e74c3c;
        }
        
        /* STYLES POUR LES ONGLETS */
        .tabs {
            display: flex;
            border-bottom: 2px solid #f8f9fa;
            margin-bottom: 25px;
        }
        
        .tab {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        
        .tab:hover {
            color: #3498db;
            background: rgba(52, 152, 219, 0.05);
        }
        
        .tab.active {
            color: #3498db;
            border-bottom-color: #3498db;
            background: rgba(52, 152, 219, 0.05);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        /* STYLES POUR LES PROGRESS BARS */
        .progress {
            width: 100%;
            height: 8px;
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 10px;
            transition: width 0.3s ease;
            position: relative;
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* STYLES POUR LES CHIPS/TAGS */
        .chip {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 2px;
            transition: all 0.3s ease;
        }
        
        .chip:hover {
            background: rgba(52, 152, 219, 0.2);
            transform: scale(1.05);
        }
        
        .chip-removable {
            padding-right: 8px;
        }
        
        .chip-remove {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            margin-left: 8px;
            padding: 2px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .chip-remove:hover {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        /* STYLES POUR LES ACCORDÉONS */
        .accordion {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            backdrop-filter: blur(10px);
        }
        
        .accordion-item {
            border-bottom: 1px solid #f8f9fa;
        }
        
        .accordion-item:last-child {
            border-bottom: none;
        }
        
        .accordion-header {
            padding: 20px 25px;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-weight: 600;
            color: #2c3e50;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .accordion-header:hover {
            background: rgba(52, 152, 219, 0.05);
        }
        
        .accordion-content {
            padding: 0 25px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .accordion-content.active {
            padding: 20px 25px;
            max-height: 500px;
        }
        
        .accordion-icon {
            transition: transform 0.3s ease;
        }
        
        .accordion-header.active .accordion-icon {
            transform: rotate(180deg);
        }
        
        /* CSS ADDITIONNEL SPÉCIFIQUE À LA PAGE */
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
                            <i class="fas fa-bell"></i> Notifications
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
                        <div class="notification-bell" onclick="toggleNotifications()">
                            <i class="fas fa-bell"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-badge"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="notification-dropdown" id="notification-dropdown">
                            <div class="notification-header">
                                <i class="fas fa-bell"></i> Notifications (<?php echo $notification_count; ?>)
                            </div>
                            
                            <?php if (empty($notifications)): ?>
                                <div class="notification-item" style="text-align: center; color: #666;">
                                    <i class="fas fa-check-circle" style="color: #27ae60; margin-right: 10px;"></i>
                                    Aucune nouvelle notification
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="notification-item">
                                        <div class="notification-icon <?php echo htmlspecialchars($notification['type']); ?>">
                                            <i class="<?php echo htmlspecialchars($notification['icon']); ?>"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                            <div class="notification-time">Maintenant</div>
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
                            <a href="my_problems.php" class="user-menu-item">
                                <i class="fas fa-code"></i> Mes Problèmes
                            </a>
                            <a href="my_solutions.php" class="user-menu-item">
                                <i class="fas fa-lightbulb"></i> Mes Solutions
                            </a>
                            <a href="favorites.php" class="user-menu-item">
                                <i class="fas fa-heart"></i> Mes Favoris
                            </a>
                            <a href="settings.php" class="user-menu-item">
                                <i class="fas fa-cog"></i> Paramètres
                            </a>
                            <a href="logout.php" class="user-menu-item" style="color: #e74c3c;">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </a>
                        </div>
                    </div>
                    
                    <!-- Bouton menu mobile -->
                    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="exlogin.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                    <a href="register.php" class="btn btn-primary">
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
        // Fonctions JavaScript pour l'interactivité du header
        function toggleNotifications() {
            const dropdown = document.getElementById('notification-dropdown');
            const userDropdown = document.getElementById('user-dropdown');
            
            // Fermer le menu utilisateur s'il est ouvert
            userDropdown.classList.remove('show');
            
            // Toggle les notifications
            dropdown.classList.toggle('show');
        }
        
        function toggleUserMenu() {
            const dropdown = document.getElementById('user-dropdown');
            const notificationDropdown = document.getElementById('notification-dropdown');
            
            // Fermer les notifications si elles sont ouvertes
            notificationDropdown.classList.remove('show');
            
            // Toggle le menu utilisateur
            dropdown.classList.toggle('show');
        }
        
        function toggleMobileMenu() {
            const navMenu = document.getElementById('nav-menu');
            navMenu.classList.toggle('show');
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
            if (notificationDropdown && notificationContainer && !notificationContainer.contains(event.target)) {
                notificationDropdown.classList.remove('show');
            }
            
            // Fermer le menu utilisateur
            if (userDropdown && userContainer && !userContainer.contains(event.target)) {
                userDropdown.classList.remove('show');
            }
            
            // Fermer le menu mobile
            if (navMenu && mobileToggle && !navMenu.contains(event.target) && !mobileToggle.contains(event.target)) {
                navMenu.classList.remove('show');
            }
        });
        
        // Fermer les dropdowns avec la touche Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const notificationDropdown = document.getElementById('notification-dropdown');
                const userDropdown = document.getElementById('user-dropdown');
                const navMenu = document.getElementById('nav-menu');
                
                if (notificationDropdown) notificationDropdown.classList.remove('show');
                if (userDropdown) userDropdown.classList.remove('show');
                if (navMenu) navMenu.classList.remove('show');
            }
        });
        
        // Animation de chargement pour les liens
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href]');
            
            links.forEach(link => {
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
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast(successMessage, 'success');
                }).catch(err => {
                    console.error('Erreur lors de la copie:', err);
                    showToast('Erreur lors de la copie', 'danger');
                });
            } else {
                // Fallback pour les navigateurs plus anciens
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    showToast(successMessage, 'success');
                } catch (err) {
                    console.error('Erreur lors de la copie:', err);
                    showToast('Erreur lors de la copie', 'danger');
                }
                
                document.body.removeChild(textArea);
            }
        }
        
        // Fonction pour formater les nombres
        function formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(num);
        }
        
        // Fonction pour formater les dates
        function formatDate(dateString) {
            const date = new Date(dateString);
            return new Intl.DateTimeFormat('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(date);
        }
        
        // Fonction pour calculer le temps écoulé
        function timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            const intervals = [
                { label: 'an', seconds: 31536000 },
                { label: 'mois', seconds: 2592000 },
                { label: 'jour', seconds: 86400 },
                { label: 'heure', seconds: 3600 },
                { label: 'minute', seconds: 60 },
                { label: 'seconde', seconds: 1 }
            ];
            
            for (const interval of intervals) {
                const count = Math.floor(diffInSeconds / interval.seconds);
                if (count >= 1) {
                    return `Il y a ${count} ${interval.label}${count > 1 ? 's' : ''}`;
                }
            }
            
            return 'À l\'instant';
        }
        
        // Fonction pour valider les formulaires
        function validateForm(formElement) {
            const requiredFields = formElement.querySelectorAll('[required]');
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
                    field.style.borderColor = '#27ae60';
                    if (errorElement) {
                        errorElement.remove();
                    }
                }
            });
            
            return isValid;
        }
        
        // Ajouter la validation automatique aux formulaires
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form[data-validate]');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!validateForm(this)) {
                        e.preventDefault();
                        showToast('Veuillez corriger les erreurs dans le formulaire', 'danger');
                        return false;
                    }
                });
                
                // Validation en temps réel
                const fields = form.querySelectorAll('[required]');
                fields.forEach(field => {
                    field.addEventListener('blur', function() {
                        validateForm(form);
                    });
                });
            });
        });
        
        // Fonction pour gérer les modales
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
        
        // Fermer les modales en cliquant sur l'arrière-plan
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target.id);
            }
        });
        
        // Fonction pour gérer les onglets
        function switchTab(tabId, contentId) {
            // Désactiver tous les onglets
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));
            
            // Activer l'onglet et le contenu sélectionnés
            const selectedTab = document.getElementById(tabId);
            const selectedContent = document.getElementById(contentId);
            
            if (selectedTab) selectedTab.classList.add('active');
            if (selectedContent) selectedContent.classList.add('active');
        }
        
        // Fonction pour gérer les accordéons
        function toggleAccordion(headerId, contentId) {
            const header = document.getElementById(headerId);
            const content = document.getElementById(contentId);
            
            if (header && content) {
                header.classList.toggle('active');
                content.classList.toggle('active');
            }
        }
        
        // Fonction pour animer les éléments au scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('[data-animate]');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        const animation = element.dataset.animate;
                        element.style.animation = `${animation} 0.6s ease forwards`;
                        observer.unobserve(element);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            elements.forEach(element => {
                observer.observe(element);
            });
        }
        
        // Initialiser les animations au scroll
        document.addEventListener('DOMContentLoaded', animateOnScroll);
        
        // Fonction pour gérer le mode sombre (si implémenté)
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark);
        }
        
        // Charger le mode sombre depuis le localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = localStorage.getItem('darkMode') === 'true';
            if (isDark) {
                document.body.classList.add('dark-mode');
            }
        });
        
        // Fonction pour gérer les favoris (utilisée dans plusieurs pages)
        function toggleFavorite(problemId, button) {
            const isActive = button.classList.contains('active');
            const action = isActive ? 'remove' : 'add';
            
            // Désactiver le bouton pendant la requête
            button.disabled = true;
            button.classList.add('loading');
            
            const formData = new FormData();
            formData.append('problem_id', problemId);
            formData.append('favorite_action', action);
            
            fetch('manage_favorites.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (action === 'add') {
                        button.classList.add('active');
                        button.title = 'Retirer des favoris';
                        showToast('Ajouté aux favoris!', 'success');
                    } else {
                        button.classList.remove('active');
                        button.title = 'Ajouter aux favoris';
                        showToast('Retiré des favoris!', 'info');
                    }
                } else {
                    showToast(data.message || 'Erreur lors de la mise à jour', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('Erreur de connexion', 'danger');
            })
            .finally(() => {
                button.disabled = false;
                button.classList.remove('loading');
            });
        }
        
        // Fonction pour précharger les images
        function preloadImages(urls) {
            urls.forEach(url => {
                const img = new Image();
                img.src = url;
            });
        }
        
        // Fonction pour optimiser les performances
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Fonction pour throttle (limiter la fréquence d'exécution)
        function throttle(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            }
        }
        
        // Optimiser le scroll
        const optimizedScroll = throttle(() => {
            // Code à exécuter lors du scroll
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const header = document.querySelector('.header');
            
            if (scrollTop > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 4px 30px rgba(0,0,0,0.15)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = '0 4px 30px rgba(0,0,0,0.1)';
            }
        }, 16);
        
        window.addEventListener('scroll', optimizedScroll);
        
        // Fonction pour gérer les erreurs JavaScript globales
        window.addEventListener('error', function(e) {
            console.error('Erreur JavaScript:', e.error);
            // En production, vous pourriez envoyer cette erreur à un service de monitoring
        });
        
        // Fonction pour gérer les promesses rejetées
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Promise rejetée:', e.reason);
            // En production, vous pourriez envoyer cette erreur à un service de monitoring
        });
        
        // Initialisation finale
        document.addEventListener('DOMContentLoaded', function() {
            // Ajouter des classes pour les animations CSS
            document.body.classList.add('loaded');
            
            // Initialiser les tooltips
            const tooltips = document.querySelectorAll('[data-tooltip]');
            tooltips.forEach(tooltip => {
                tooltip.classList.add('tooltip');
            });
            
            // Ajouter des gestionnaires d'événements pour l'accessibilité
            const interactiveElements = document.querySelectorAll('button, a, input, select, textarea');
            interactiveElements.forEach(element => {
                element.addEventListener('focus', function() {
                    this.style.outline = '2px solid #3498db';
                    this.style.outlineOffset = '2px';
                });
                
                element.addEventListener('blur', function() {
                    this.style.outline = '';
                    this.style.outlineOffset = '';
                });
            });
            
            console.log('🚀 CodeShare initialisé avec succès!');
        });
    </script>
    
    <!-- Scripts additionnels spécifiques à la page -->
    <?php if (isset($additional_scripts)): ?>
    <script>
        <?php echo $additional_scripts; ?>
    </script>
    <?php endif; ?>
