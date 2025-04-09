<?php
// Set page title
$page_title = "Notifications des Problèmes";

// Additional CSS specific to this page
$additional_css = "
    .solution-card {
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 20px;
        background-color: white;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        transition: transform 0.3s, box-shadow 0.3s;
        margin-bottom: 20px;
    }

    .solution-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.12);
    }

    .solution-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 15px;
    }

    .solution-title {
        font-weight: bold;
        font-size: 1.2em;
        color: #2c3e50;
        margin: 0;
    }

    .solution-price {
        font-weight: bold;
        color: #4CAF50;
        font-size: 1.2em;
    }

    .solution-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
        font-size: 0.9em;
        color: #7f8c8d;
    }

    .solution-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .solution-content {
        margin-bottom: 15px;
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        overflow-x: auto;
        border: 1px solid #e0e0e0;
    }

    .solution-explanation {
        margin-bottom: 15px;
        padding: 15px;
        background-color: #f0f7fb;
        border-radius: 8px;
        border-left: 4px solid #3498db;
    }

    .solution-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f0f0f0;
        padding-top: 15px;
    }

    .solution-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 10px;
        border-radius: 15px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
    }

    .solution-status.pending {
        background: #fef9e7;
        color: #f39c12;
    }

    .solution-status.accepted {
        background: #d5f5e3;
        color: #27ae60;
    }

    .solution-status.rejected {
        background: #fdedec;
        color: #e74c3c;
    }

    .solution-actions {
        display: flex;
        gap: 10px;
    }

    .btn-outline {
        background-color: transparent;
        color: #e74c3c;
        border: 1px solid #e74c3c;
    }

    .btn-outline:hover {
        background-color: #e74c3c;
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }

    .empty-state i {
        font-size: 3em;
        color: #ddd;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #7f8c8d;
        margin-bottom: 20px;
    }
";

// Include header
include 'header.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Initialiser les variables
$received_solutions = [];

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Récupérer les solutions soumises pour les problèmes créés par l'utilisateur
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, u.username, u.name as solver_name,
               COALESCE(s.price, 0.00) as price
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        WHERE p.user_id = ? AND s.user_id != ?
        ORDER BY s.created_at DESC
    ");
    
    $stmt->execute([$user_id, $user_id]);
    $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des solutions: " . $e->getMessage());
    $error_message = "Une erreur est survenue lors de la récupération des données.";
}

// Traiter l'acceptation d'une solution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_solution'])) {
    $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
    
    if ($solution_id > 0) {
        try {
            // Vérifier que la solution existe et appartient à un problème de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT s.*, p.user_id as problem_owner
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                WHERE s.id = ? AND p.user_id = ?
            ");
            
            $stmt->execute([$solution_id, $user_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($solution) {
                // Mettre à jour le statut de la solution
                $stmt = $pdo->prepare("
                    UPDATE solutions
                    SET status = 'accepted', evaluated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$solution_id]);
                
                if ($result) {
                    $success_message = "La solution a été acceptée avec succès. Vous serez redirigé vers la page de paiement.";
                    
                    // Rediriger vers la page de paiement après 2 secondes
                    header("refresh:2;url=payment.php?solution_id=$solution_id");
                    
                    // Rafraîchir la liste des solutions
                    $stmt = $pdo->prepare("
                        SELECT s.*, p.title as problem_title, u.username, u.name as solver_name
                        FROM solutions s
                        JOIN problems p ON s.problem_id = p.problem_id
                        JOIN users u ON s.user_id = u.id
                        WHERE p.user_id = ? AND s.user_id != ?
                        ORDER BY s.created_at DESC
                    ");
                    
                    $stmt->execute([$user_id, $user_id]);
                    $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Une erreur est survenue lors de l'acceptation de la solution.";
                }
            } else {
                $error_message = "Solution introuvable ou vous n'êtes pas autorisé à l'accepter.";
            }
        } catch (Exception $e) {
            error_log("Erreur lors de l'acceptation de la solution: " . $e->getMessage());
            $error_message = "Une erreur est survenue lors de l'acceptation de la solution.";
        }
    } else {
        $error_message = "ID de solution invalide.";
    }
}
?>

<h1><i class="fas fa-bell"></i> Notifications des Solutions</h1>

<h2><i class="fas fa-inbox"></i> Solutions reçues pour vos problèmes</h2>

<?php if (empty($received_solutions)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Aucune solution reçue</h3>
        <p>Vous n'avez pas encore reçu de solutions pour vos problèmes publiés.</p>
    </div>
<?php else: ?>
    <?php foreach ($received_solutions as $solution): ?>
        <div class="solution-card">
            <div class="solution-header">
                <h3 class="solution-title">
                    <i class="fas fa-file-code"></i> 
                    <?php echo htmlspecialchars($solution['problem_title']); ?>
                </h3>
                <span class="solution-price">
                    <?php echo number_format($solution['price'] ?? 0, 2); ?> €
                </span>
            </div>
            
            <div class="solution-meta">
                <span>
                    <i class="fas fa-user"></i> 
                    <?php echo htmlspecialchars($solution['solver_name'] ?? $solution['username']); ?>
                </span>
                <span>
                    <i class="fas fa-calendar-alt"></i> 
                    <?php 
                    $date = new DateTime($solution['created_at'] ?? date('Y-m-d H:i:s'));
                    echo $date->format('d/m/Y à H:i'); 
                    ?>
                </span>
                <span class="solution-status <?php echo htmlspecialchars($solution['status']); ?>">
                    <?php 
                    $status_labels = [
                        'pending' => 'En attente',
                        'accepted' => 'Acceptée',
                        'rejected' => 'Rejetée'
                    ];
                    echo isset($status_labels[$solution['status']]) ? 
                        $status_labels[$solution['status']] : 
                        htmlspecialchars($solution['status']);
                    ?>
                </span>
            </div>
            
            <div class="solution-content">
                <pre><?php echo htmlspecialchars($solution['solution_code']); ?></pre>
            </div>
            
            <?php if (!empty($solution['explanation'])): ?>
                <div class="solution-explanation">
                    <h4><i class="fas fa-comment-alt"></i> Explication du développeur</h4>
                    <?php echo nl2br(htmlspecialchars($solution['explanation'])); ?>
                </div>
            <?php endif; ?>
            
            <div class="solution-footer">
                <a href="problem.php?id=<?php echo $solution['problem_id']; ?>" class="btn btn-outline">
                    <i class="fas fa-eye"></i> Voir le problème
                </a>
                
                <?php if ($solution['status'] === 'pending'): ?>
                    <div class="solution-actions">
                        <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir accepter cette solution? Vous serez redirigé vers la page de paiement.');">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <button type="submit" name="accept_solution" class="btn btn-primary">
                                <i class="fas fa-check"></i> Accepter (<?php echo number_format($solution['price'] ?? 0, 2); ?> €)
                            </button>
                        </form>
                        
                        <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette solution?');">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <input type="hidden" name="reject_solution" value="1">
                            <button type="submit" class="btn btn-outline">
                                <i class="fas fa-times"></i> Rejeter
                            </button>
                        </form>
                    </div>
                <?php elseif ($solution['status'] === 'accepted'): ?>
                    <a href="payment.php?solution_id=<?php echo $solution['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i> Procéder au paiement
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
// Additional scripts
$additional_scripts = "
    // Animation pour les boutons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'translateY(-2px)';
            button.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.transform = '';
            button.style.boxShadow = '';
        });
    });
    
    // Animation pour les cartes
    document.querySelectorAll('.solution-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
            card.style.boxShadow = '0 8px 15px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(-3px)';
            card.style.boxShadow = '0 5px 15px rgba(0,0,0,0.08)';
        });
    });
";

// Include footer
include 'footer.php';
?>
