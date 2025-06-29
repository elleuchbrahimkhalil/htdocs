<?php
/**
 * Gestionnaire d'effet de flou pour les solutions non payées
 * 
 * Ce fichier gère l'affichage des solutions avec effet de flou
 * jusqu'à ce que l'utilisateur effectue le paiement
 */

if (!function_exists('isLoggedIn')) {
    require_once 'verification.php';
}

require_once 'db_connect.php';

class SolutionBlurHandler {
    private $conn;
    private $user_id;
    
    public function __construct() {
        $this->conn = connect();
        $this->user_id = isLoggedIn() ? $_SESSION['user_id'] : 0;
    }
    
    /**
     * Vérifier si l'utilisateur a payé pour une solution
     */
    public function hasPaidForSolution($solution_id) {
        if (!$this->conn || !$this->user_id) {
            return false;
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) 
                FROM payments 
                WHERE solution_id = ? AND payer_id = ? AND status = 'completed'
            ");
            $stmt->execute([$solution_id, $this->user_id]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Erreur hasPaidForSolution: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si l'utilisateur est le propriétaire de la solution
     */
    public function isOwnerOfSolution($solution_id) {
        if (!$this->conn || !$this->user_id) {
            return false;
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) 
                FROM solutions 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$solution_id, $this->user_id]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Erreur isOwnerOfSolution: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir le prix d'une solution
     */
    public function getSolutionPrice($solution_id) {
        if (!$this->conn) {
            return 0;
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT price 
                FROM solutions 
                WHERE id = ?
            ");
            $stmt->execute([$solution_id]);
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            error_log("Erreur getSolutionPrice: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Générer le HTML pour une solution avec effet de flou si nécessaire
     */
    public function renderSolution($solution) {
        $solution_id = $solution['id'];
        $is_paid = $this->hasPaidForSolution($solution_id);
        $is_owner = $this->isOwnerOfSolution($solution_id);
        $price = $this->getSolutionPrice($solution_id);
        
        // Si l'utilisateur a payé ou est le propriétaire, afficher normalement
        if ($is_paid || $is_owner) {
            return $this->renderFullSolution($solution);
        } else {
            return $this->renderBlurredSolution($solution, $price);
        }
    }
    
    /**
     * Afficher la solution complète (payée ou propriétaire)
     */
    private function renderFullSolution($solution) {
        $html = '<div class="solution-content-full">';
        
        // Code de la solution
        if (!empty($solution['solution_code'])) {
            $html .= '<div class="solution-code-container">';
            $html .= '<div class="solution-code-header">';
            $html .= '<i class="fas fa-code"></i> Code de la solution';
            $html .= '<button class="copy-code-btn" onclick="copySolutionCode(this)" title="Copier le code">';
            $html .= '<i class="fas fa-copy"></i>';
            $html .= '</button>';
            $html .= '</div>';
            $html .= '<pre class="solution-code"><code>' . htmlspecialchars($solution['solution_code']) . '</code></pre>';
            $html .= '</div>';
        }
        
        // Explication de la solution
        if (!empty($solution['explanation'])) {
            $html .= '<div class="solution-explanation-container">';
            $html .= '<div class="solution-explanation-header">';
            $html .= '<i class="fas fa-lightbulb"></i> Explication détaillée';
            $html .= '</div>';
            $html .= '<div class="solution-explanation">';
            $html .= nl2br(htmlspecialchars($solution['explanation']));
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Boutons d'action
        $html .= '<div class="solution-actions">';
        $html .= '<button class="btn btn-success" onclick="downloadSolution(' . $solution['id'] . ')">';
        $html .= '<i class="fas fa-download"></i> Télécharger';
        $html .= '</button>';
        $html .= '<button class="btn btn-outline" onclick="rateSolution(' . $solution['id'] . ')">';
        $html .= '<i class="fas fa-star"></i> Noter';
        $html .= '</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Afficher la solution avec effet de flou (non payée)
     */
    private function renderBlurredSolution($solution, $price) {
        $solution_id = $solution['id'];
        
        $html = '<div class="solution-content-blurred" data-solution-id="' . $solution_id . '">';
        
        // Overlay de paiement
        $html .= '<div class="payment-overlay">';
        $html .= '<div class="payment-overlay-content">';
        $html .= '<div class="payment-icon">';
        $html .= '<i class="fas fa-lock"></i>';
        $html .= '</div>';
        $html .= '<h3>Solution Premium</h3>';
        $html .= '<p>Cette solution est payante. Débloquez-la pour voir le code complet et l\'explication détaillée.</p>';
        $html .= '<div class="price-display">';
        $html .= '<span class="price-amount">' . number_format($price, 2) . ' €</span>';
        $html .= '</div>';
        $html .= '<div class="payment-buttons">';
        $html .= '<button class="btn btn-primary payment-btn" onclick="initiateSolutionPayment(' . $solution_id . ', ' . $price . ')">';
        $html .= '<i class="fas fa-credit-card"></i> Acheter maintenant';
        $html .= '</button>';
        $html .= '<button class="btn btn-outline preview-btn" onclick="showSolutionPreview(' . $solution_id . ')">';
        $html .= '<i class="fas fa-eye"></i> Aperçu gratuit';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Contenu flouté
        $html .= '<div class="blurred-content">';
        
        // Code flouté
        if (!empty($solution['solution_code'])) {
            $html .= '<div class="solution-code-container blurred">';
            $html .= '<div class="solution-code-header">';
            $html .= '<i class="fas fa-code"></i> Code de la solution';
            $html .= '<span class="locked-badge"><i class="fas fa-lock"></i> Verrouillé</span>';
            $html .= '</div>';
            
            // Afficher seulement les premières lignes
            $code_lines = explode("\n", $solution['solution_code']);
            $preview_lines = array_slice($code_lines, 0, 3);
            $preview_code = implode("\n", $preview_lines);
            if (count($code_lines) > 3) {
                $preview_code .= "\n// ... " . (count($code_lines) - 3) . " lignes supplémentaires";
            }
            
            $html .= '<pre class="solution-code blurred-text"><code>' . htmlspecialchars($preview_code) . '</code></pre>';
            $html .= '</div>';
        }
        
        // Explication floutée
        if (!empty($solution['explanation'])) {
            $html .= '<div class="solution-explanation-container blurred">';
            $html .= '<div class="solution-explanation-header">';
            $html .= '<i class="fas fa-lightbulb"></i> Explication détaillée';
            $html .= '<span class="locked-badge"><i class="fas fa-lock"></i> Verrouillé</span>';
            $html .= '</div>';
            
            // Afficher seulement le début de l'explication
            $explanation_preview = substr($solution['explanation'], 0, 100);
            if (strlen($solution['explanation']) > 100) {
                $explanation_preview .= "...";
            }
            
            $html .= '<div class="solution-explanation blurred-text">';
            $html .= nl2br(htmlspecialchars($explanation_preview));
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // fin blurred-content
        $html .= '</div>'; // fin solution-content-blurred
        
        return $html;
    }
    
    /**
     * Générer le CSS pour les effets de flou
     */
    public static function getBlurCSS() {
        return '
        <style>
            /* Styles pour les solutions floutées */
            .solution-content-blurred {
                position: relative;
                margin: 20px 0;
                border-radius: 12px;
                overflow: hidden;
                background: white;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            
            .payment-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(2px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
                padding: 20px;
            }
            
            .payment-overlay-content {
                text-align: center;
                max-width: 400px;
                background: white;
                padding: 30px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                border: 2px solid #f0f0f0;
            }
            
            .payment-icon {
                font-size: 48px;
                color: #3498db;
                margin-bottom: 20px;
                animation: lockPulse 2s infinite;
            }
            
            @keyframes lockPulse {
                0%, 100% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.1); opacity: 0.8; }
            }
            
            .payment-overlay-content h3 {
                color: #2c3e50;
                margin-bottom: 15px;
                font-size: 24px;
                font-weight: 600;
            }
            
            .payment-overlay-content p {
                color: #7f8c8d;
                margin-bottom: 20px;
                line-height: 1.6;
            }
            
            .price-display {
                margin: 20px 0;
                padding: 15px;
                background: linear-gradient(135deg, #3498db, #2980b9);
                border-radius: 10px;
                color: white;
            }
            
            .price-amount {
                font-size: 28px;
                font-weight: bold;
                text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .payment-buttons {
                display: flex;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .payment-btn {
                background: linear-gradient(135deg, #27ae60, #2ecc71);
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .payment-btn:hover {
                background: linear-gradient(135deg, #2ecc71, #27ae60);
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
            }
            
            .preview-btn {
                background: transparent;
                color: #3498db;
                border: 2px solid #3498db;
                padding: 10px 22px;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .preview-btn:hover {
                background: #3498db;
                color: white;
                transform: translateY(-2px);
            }
            
            /* Contenu flouté */
            .blurred-content {
                filter: blur(8px);
                opacity: 0.6;
                pointer-events: none;
                user-select: none;
                padding: 20px;
            }
            
            .solution-code-container.blurred,
            .solution-explanation-container.blurred {
                position: relative;
                margin: 15px 0;
            }
            
            .solution-code-header,
            .solution-explanation-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 15px;
                background: #f8f9fa;
                border-radius: 8px 8px 0 0;
                font-weight: 600;
                color: #2c3e50;
            }
            
            .locked-badge {
                background: #e74c3c;
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500;
            }
            
                      .solution-code {
                background: #2c3e50;
                color: #ecf0f1;
                padding: 20px;
                margin: 0;
                border-radius: 0 0 8px 8px;
                font-family: "Courier New", Courier, monospace;
                line-height: 1.6;
                overflow-x: auto;
            }
            
            .solution-explanation {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 0 0 8px 8px;
                line-height: 1.6;
                color: #2c3e50;
            }
            
            .blurred-text {
                text-shadow: 0 0 8px rgba(0,0,0,0.5);
                color: transparent !important;
                background: linear-gradient(45deg, #bdc3c7, #ecf0f1);
                background-clip: text;
                -webkit-background-clip: text;
            }
            
            /* Solution complète (payée) */
            .solution-content-full {
                margin: 20px 0;
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                border: 2px solid #27ae60;
            }
            
            .solution-content-full .solution-code-container,
            .solution-content-full .solution-explanation-container {
                margin: 0;
            }
            
            .copy-code-btn {
                background: #3498db;
                color: white;
                border: none;
                padding: 6px 12px;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.3s;
                font-size: 12px;
            }
            
            .copy-code-btn:hover {
                background: #2980b9;
                transform: scale(1.05);
            }
            
            .solution-actions {
                padding: 20px;
                display: flex;
                gap: 10px;
                justify-content: center;
                background: #f8f9fa;
                border-top: 1px solid #e9ecef;
            }
            
            .btn {
                padding: 10px 20px;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                display: flex;
                align-items: center;
                gap: 8px;
                text-decoration: none;
                border: none;
            }
            
            .btn-success {
                background: #27ae60;
                color: white;
            }
            
            .btn-success:hover {
                background: #2ecc71;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
            }
            
            .btn-outline {
                background: transparent;
                color: #3498db;
                border: 2px solid #3498db;
            }
            
            .btn-outline:hover {
                background: #3498db;
                color: white;
            }
            
            /* Aperçu modal */
            .preview-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                z-index: 1000;
                backdrop-filter: blur(5px);
            }
            
            .preview-modal-content {
                position: relative;
                width: 90%;
                max-width: 800px;
                margin: 50px auto;
                background: white;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                animation: modalSlideIn 0.3s ease-out;
            }
            
            @keyframes modalSlideIn {
                from {
                    opacity: 0;
                    transform: translateY(-50px) scale(0.9);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }
            
            .preview-modal-header {
                padding: 20px 25px;
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .preview-modal-header h3 {
                margin: 0;
                font-size: 20px;
            }
            
            .preview-modal-close {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                padding: 5px;
                border-radius: 50%;
                transition: all 0.3s;
            }
            
            .preview-modal-close:hover {
                background: rgba(255,255,255,0.2);
                transform: scale(1.1);
            }
            
            .preview-modal-body {
                padding: 25px;
                max-height: 60vh;
                overflow-y: auto;
            }
            
            .preview-info {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 20px;
                color: #856404;
            }
            
            .preview-info i {
                color: #f39c12;
                margin-right: 8px;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .payment-overlay-content {
                    padding: 20px;
                    margin: 10px;
                }
                
                .payment-buttons {
                    flex-direction: column;
                }
                
                .payment-btn,
                .preview-btn {
                    width: 100%;
                    justify-content: center;
                }
                
                .solution-actions {
                    flex-direction: column;
                }
                
                .btn {
                    width: 100%;
                    justify-content: center;
                }
                
                .preview-modal-content {
                    width: 95%;
                    margin: 20px auto;
                }
                
                .preview-modal-body {
                    padding: 15px;
                    max-height: 70vh;
                }
            }
            
            /* Animation de chargement */
            .loading-spinner {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid rgba(255,255,255,0.3);
                border-radius: 50%;
                border-top-color: white;
                animation: spin 1s ease-in-out infinite;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            
            /* États des boutons */
            .btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none !important;
            }
            
            .btn.loading {
                pointer-events: none;
            }
            
            /* Notification de succès */
            .payment-success-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #27ae60, #2ecc71);
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
                z-index: 1001;
                animation: slideInRight 0.3s ease-out;
            }
            
            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
        </style>';
    }
    
    /**
     * Générer le JavaScript pour les interactions
     */
    public static function getBlurJS() {
        return '
        <script>
            // Fonctions pour gérer les solutions floutées
            
            // Initier le paiement d\'une solution
            function initiateSolutionPayment(solutionId, price) {
                const btn = event.target;
                const originalText = btn.innerHTML;
                
                // Animation de chargement
                btn.disabled = true;
                btn.classList.add("loading");
                btn.innerHTML = \'<span class="loading-spinner"></span> Traitement...\';
                
                // Rediriger vers la page de paiement
                setTimeout(() => {
                    window.location.href = `payment.php?solution_id=${solutionId}&amount=${price}&type=solution`;
                }, 1000);
            }
            
            // Afficher l\'aperçu gratuit d\'une solution
            function showSolutionPreview(solutionId) {
                fetch(`get_solution_preview.php?solution_id=${solutionId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showPreviewModal(data.preview);
                        } else {
                            showNotification("Erreur lors du chargement de l\'aperçu", "error");
                        }
                    })
                    .catch(error => {
                        console.error("Erreur:", error);
                        showNotification("Erreur de connexion", "error");
                    });
            }
            
            // Afficher la modal d\'aperçu
            function showPreviewModal(previewData) {
                const modal = document.createElement("div");
                modal.className = "preview-modal";
                modal.innerHTML = `
                    <div class="preview-modal-content">
                        <div class="preview-modal-header">
                            <h3><i class="fas fa-eye"></i> Aperçu de la solution</h3>
                            <button class="preview-modal-close" onclick="closePreviewModal()">&times;</button>
                        </div>
                        <div class="preview-modal-body">
                            <div class="preview-info">
                                <i class="fas fa-info-circle"></i>
                                Ceci est un aperçu limité. Achetez la solution complète pour voir tout le code et l\'explication détaillée.
                            </div>
                            
                            ${previewData.code_preview ? `
                                <div class="solution-code-container">
                                    <div class="solution-code-header">
                                        <i class="fas fa-code"></i> Aperçu du code (${previewData.total_lines} lignes au total)
                                    </div>
                                    <pre class="solution-code"><code>${previewData.code_preview}</code></pre>
                                </div>
                            ` : ""}
                            
                            ${previewData.explanation_preview ? `
                                <div class="solution-explanation-container">
                                    <div class="solution-explanation-header">
                                        <i class="fas fa-lightbulb"></i> Début de l\'explication
                                    </div>
                                    <div class="solution-explanation">${previewData.explanation_preview}</div>
                                </div>
                            ` : ""}
                            
                            <div class="solution-actions">
                                <button class="btn payment-btn" onclick="initiateSolutionPayment(${previewData.solution_id}, ${previewData.price})">
                                    <i class="fas fa-credit-card"></i> Acheter maintenant (${previewData.price}€)
                                </button>
                                <button class="btn btn-outline" onclick="closePreviewModal()">
                                    <i class="fas fa-times"></i> Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                modal.style.display = "block";
                document.body.style.overflow = "hidden";
                
                // Fermer en cliquant sur l\'overlay
                modal.addEventListener("click", function(e) {
                    if (e.target === modal) {
                        closePreviewModal();
                    }
                });
            }
            
            // Fermer la modal d\'aperçu
            function closePreviewModal() {
                const modal = document.querySelector(".preview-modal");
                if (modal) {
                    modal.style.opacity = "0";
                    setTimeout(() => {
                        modal.remove();
                        document.body.style.overflow = "auto";
                    }, 300);
                }
            }
            
            // Copier le code d\'une solution
            function copySolutionCode(button) {
                const codeElement = button.closest(".solution-code-container").querySelector("code");
                const code = codeElement.textContent;
                
                navigator.clipboard.writeText(code).then(() => {
                    const originalIcon = button.innerHTML;
                    button.innerHTML = \'<i class="fas fa-check"></i>\';
                    button.style.background = "#27ae60";
                    
                    setTimeout(() => {
                        button.innerHTML = originalIcon;
                        button.style.background = "#3498db";
                    }, 2000);
                    
                    showNotification("Code copié dans le presse-papiers!", "success");
                }).catch(err => {
                    console.error("Erreur lors de la copie:", err);
                    showNotification("Erreur lors de la copie", "error");
                });
            }
            
            // Télécharger une solution
            function downloadSolution(solutionId) {
                const btn = event.target;
                const originalText = btn.innerHTML;
                
                btn.disabled = true;
                btn.innerHTML = \'<span class="loading-spinner"></span> Téléchargement...\';
                
                // Créer un lien de téléchargement
                const link = document.createElement("a");
                link.href = `download_solution.php?solution_id=${solutionId}`;
                link.download = `solution_${solutionId}.txt`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    showNotification("Solution téléchargée!", "success");
                }, 1500);
            }
            
            // Noter une solution
            function rateSolution(solutionId) {
                // Implémenter le système de notation
                showNotification("Système de notation à venir!", "info");
            }
            
            // Afficher une notification
            function showNotification(message, type = "info") {
                const notification = document.createElement("div");
                notification.className = `payment-success-notification ${type}`;
                
                const icons = {
                    success: "fas fa-check-circle",
                    error: "fas fa-exclamation-circle",
                    info: "fas fa-info-circle",
                    warning: "fas fa-exclamation-triangle"
                };
                
                const colors = {
                    success: "linear-gradient(135deg, #27ae60, #2ecc71)",
                    error: "linear-gradient(135deg, #e74c3c, #c0392b)",
                    info: "linear-gradient(135deg, #3498db, #2980b9)",
                    warning: "linear-gradient(135deg, #f39c12, #e67e22)"
                };
                
                notification.style.background = colors[type] || colors.info;
                notification.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="${icons[type] || icons.info}"></i>
                        <span>${message}</span>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Animation de sortie
                setTimeout(() => {
                    notification.style.opacity = "0";
                    notification.style.transform = "translateX(100%)";
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 4000);
            }
            
            // Gestion des touches clavier
            document.addEventListener("keydown", function(e) {
                if (e.key === "Escape") {
                    closePreviewModal();
                }
            });
            
            // Vérifier le statut de paiement après retour de la page de paiement
            function checkPaymentStatus() {
                const urlParams = new URLSearchParams(window.location.search);
                const paymentSuccess = urlParams.get("payment_success");
                const solutionId = urlParams.get("solution_id");
                
                if (paymentSuccess === "true" && solutionId) {
                    showNotification("Paiement réussi! La solution est maintenant débloquée.", "success");
                    
                    // Recharger la page après 2 secondes pour afficher la solution débloquée
                    setTimeout(() => {
                        window.location.href = window.location.pathname;
                    }, 2000);
                }
            }
            
            // Initialisation
            document.addEventListener("DOMContentLoaded", function() {
                checkPaymentStatus();
                
                // Animation d\'entrée pour les solutions floutées
                const blurredSolutions = document.querySelectorAll(".solution-content-blurred");
                blurredSolutions.forEach((solution, index) => {
                    solution.style.opacity = "0";
                    solution.style.transform = "translateY(20px)";
                    
                    setTimeout(() => {
                        solution.style.transition = "opacity 0.5s ease, transform 0.5s ease";
                        solution.style.opacity = "1";
                        solution.style.transform = "translateY(0)";
                    }, index * 200);
                });
            });
            
            // Fonction pour actualiser une solution après paiement
            function refreshSolution(solutionId) {
                fetch(`get_solution_content.php?solution_id=${solutionId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.is_paid) {
                            const solutionElement = document.querySelector(`[data-solution-id="${solutionId}"]`);
                            if (solutionElement) {
                                solutionElement.outerHTML = data.html;
                                showNotification("Solution débloquée avec succès!", "success");
                            }
                        }
                    })
                    .catch(error => {
                        console.error("Erreur lors de l\'actualisation:", error);
                    });
            }
            
            // Effet de survol sur les solutions payantes
            document.addEventListener("mouseover", function(e) {
                if (e.target.closest(".payment-overlay-content")) {
                    const overlay = e.target.closest(".payment-overlay");
                    overlay.style.background = "rgba(255, 255, 255, 0.98)";
                }
            });
            
            document.addEventListener("mouseout", function(e) {
                if (e.target.closest(".payment-overlay-content")) {
                    const overlay = e.target.closest(".payment-overlay");
                    overlay.style.background = "rgba(255, 255, 255, 0.95)";
                }
            });
        </script>';
    }
}

// Fonction helper pour utiliser facilement dans user_feedback.php
function renderSolutionWithBlur($solution) {
    $handler = new SolutionBlurHandler();
    return $handler->renderSolution($solution);
}

// Fonction pour inclure les styles et scripts
function includeSolutionBlurAssets() {
    echo SolutionBlurHandler::getBlurCSS();
    echo SolutionBlurHandler::getBlurJS();
}
?>
