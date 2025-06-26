<?php
session_start();

echo "<h2>🧪 Test de Redirection</h2>";

// Simuler une session utilisateur
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test_user';

echo "<p>Session créée avec user_id = 1</p>";

// Test 1: Redirection immédiate
if (isset($_GET['test']) && $_GET['test'] == '1') {
    header("Location: payment.php?solution_id=123&test=1");
    exit;
}

// Test 2: Redirection JavaScript
if (isset($_GET['test']) && $_GET['test'] == '2') {
    echo "<script>
        alert('Test redirection JavaScript');
        window.location.href = 'payment.php?solution_id=123&test=1';
    </script>";
    exit;
}

echo "<h3>Tests disponibles :</h3>";
echo "<ul>";
echo "<li><a href='?test=1'>Test redirection PHP header()</a></li>";
echo "<li><a href='?test=2'>Test redirection JavaScript</a></li>";
echo "<li><a href='payment.php?solution_id=123&test=1'>Accès direct à payment.php</a></li>";
echo "<li><a href='test_session.php'>Vérifier les sessions</a></li>";
echo "</ul>";
?>
