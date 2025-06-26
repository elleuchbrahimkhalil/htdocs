<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
require_once 'verification.php';

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

// Récupérer le nombre de notifications non lues
$notification_count = 0;
if (isLoggedIn()) {
    try {
        $user = getCurrentUser();
        $pdo = connect();
        
        if ($pdo && $user) {
            // Compter les solutions en attente pour les problèmes de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                WHERE p.user_id = ? AND s.user_id != ? AND s.status = 'pending'
            ");
            $stmt->execute([$user['id'], $user['id']]);
            $pending_solutions = $stmt->fetchColumn();
            
            // Compter les solutions de l'utilisateur avec des mises à jour
            $stmt = $pdo->prepare("
                SELECT TOP 1 COUNT(*) 
                FROM solutions s
                WHERE s.user_id = ? AND s.status IN ('accepted', 'rejected', 'paid')
                AND s.updated_at > s.created_at
                AND s.updated_at > COALESCE(
                    (SELECT last_seen FROM user_notifications WHERE user_id = ?),
                    '1970-01-01'
                )
            ");
            $stmt->execute([$user['id'], $user['id']]);
            $updated_solutions = $stmt->fetchColumn();
            
            $notification_count = $pending_solutions + $updated_solutions;
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des notifications: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'CodeChallenge'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: #f9f9f9;
        }

        .main-container {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 250px;
            padding: 20px;
            background-color: #f4f4f4;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .content {
            flex: 1;
            padding: 20px;
        }

        .sidebar h2, .content h2 {
            font-size: 1.5em;
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #eaeaea;
            padding-bottom: 10px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0 0 20px 0;
        }

        .sidebar ul li {
            margin: 10px 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #333;
            display: block;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar ul li a:hover {
            background-color: #e0e0e0;
            transform: translateX(5px);
        }

        .btn {
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }

        .btn-primary:hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 500;
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

        .difficulty-easy {
            color: #28a745;
        }

        .difficulty-medium {
            color: #ffc107;
        }

        .difficulty-hard {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
        }

        /* CSS spécifique à la page */
        <?php echo isset($additional_css) ? $additional_css : ''; ?>
    </style>
</head>
<body>
    <?php include 'exmenu.php'; ?>

    <div class="main-container">
        <div class="sidebar">
            <h2>Formation Générale</h2>
            <ul>
                <li><a href="https://www.hackerrank.com/" target="_blank"><i class="fas fa-laptop-code"></i> HackerRank</a></li>
                <li><a href="https://www.codewars.com/" target="_blank"><i class="fas fa-code"></i> Codewars</a></li>
                <li><a href="https://www.leetcode.com/" target="_blank"><i class="fas fa-file-code"></i> LeetCode</a></li>
                <li><a href="https://www.topcoder.com/" target="_blank"><i class="fas fa-trophy"></i> TopCoder</a></li>
            </ul>
            
            <h2>Compétitions Mondiales</h2>
            <ul>
                <li><a href="https://icpc.global/" target="_blank"><i class="fas fa-globe"></i> ICPC</a></li>
                <li><a href="https://www.kaggle.com/" target="_blank"><i class="fas fa-chart-line"></i> Kaggle</a></li>
            </ul>
            
            <h2>Compétitions Régionales</h2>
            <ul>
                <li><a href="https://www.codechef.com/" target="_blank"><i class="fas fa-utensils"></i> CodeChef</a></li>
                <li><a href="https://www.codingame.com/" target="_blank"><i class="fas fa-gamepad"></i> CodinGame</a></li>
            </ul>
            
            <h2>Mes Actions</h2>
            <ul>
                <li><a href="expublier.php"><i class="fas fa-plus-circle"></i> Publier un problème</a></li>
                <li><a href="favorites.php"><i class="fas fa-star"></i> Mes problèmes favoris</a></li>
                <li><a href="user_feedback.php"><i class="fas fa-bell"></i> Notifications</a></li>
            </ul>
            
            <h2>Contact</h2>
            <ul>
                <li><a href="mailto:contact@example.com"><i class="fas fa-envelope"></i> contact@example.com</a></li>
            </ul>
        </div>
        
        <div class="content">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
