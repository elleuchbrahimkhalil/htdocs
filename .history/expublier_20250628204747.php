<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Vous devez être connecté pour publier un problème";
    header('Location: login.php');
    exit;
}

// Configuration de la page
$page_title = "Publier un Problème";

// Récupérer et normaliser les erreurs et données du formulaire
$form_errors = isset($_SESSION['form_errors']) ? (array)$_SESSION['form_errors'] : [];
$form_data = isset($_SESSION['form_data']) ? (array)$_SESSION['form_data'] : [];

// Nettoyer les variables de session
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);

// Normaliser la structure des erreurs
foreach ($form_errors as $key => &$error) {
    if (!is_array($error)) {
        $error = [$error];
    }
}

// Liste des langages disponibles
$languages = [
    'python' => 'Python',
    'java' => 'Java',
    'javascript' => 'JavaScript',
    'c' => 'C',
    'cpp' => 'C++',
    'csharp' => 'C#',
    'php' => 'PHP',
    'ruby' => 'Ruby',
    'swift' => 'Swift',
    'go' => 'Go',
    'rust' => 'Rust',
    'kotlin' => 'Kotlin',
    'typescript' => 'TypeScript',
    'sql' => 'SQL',
    'html' => 'HTML/CSS',
    'other' => 'Autre'
];

// CSS spécifique à cette page (sans les styles de difficulté)
$additional_css = "
    .publish-container {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .publish-header {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        padding: 30px;
        text-align: center;
    }

    .publish-header h1 {
        margin: 0;
        font-size: 28px;
        font-weight: 600;
    }

    .publish-header p {
        margin: 10px 0 0 0;
        opacity: 0.9;
        font-size: 16px;
    }

    .ai-notice {
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
        color: white;
        padding: 20px;
        margin: 20px 0;
        border-radius: 8px;
        text-align: center;
        border-left: 4px solid #fff;
    }

    .ai-notice i {
        font-size: 24px;
        margin-bottom: 10px;
        display: block;
    }

    .publish-form {
        padding: 40px;
    }

    .form-section {
        margin-bottom: 35px;
        padding-bottom: 25px;
        border-bottom: 1px solid #f0f0f0;
    }

    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .section-title {
        color: #2c3e50;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        color: #3498db;
        font-size: 18px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
    }

    .form-input,
    .form-textarea,
    .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s;
        box-sizing: border-box;
        font-family: inherit;
    }

    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .form-textarea {
        min-height: 120px;
        resize: vertical;
        line-height: 1.6;
    }

    .code-editor {
        font-family: 'Courier New', Courier, monospace;
        min-height: 200px;
        background: #f8f9fa;
        border: 2px solid #e0e6ed;
    }

    .code-editor:focus {
        background: white;
    }

    .tags-container {
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        padding: 12px;
        min-height: 50px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: flex-start;
        transition: border-color 0.3s;
    }

    .tags-container:focus-within {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .tag {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        font-weight: 500;
        animation: tagAppear 0.3s ease-out;
    }

    @keyframes tagAppear {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .tag .remove-tag {
        cursor: pointer;
        font-weight: bold;
        padding: 2px 4px;
        border-radius: 50%;
        transition: background 0.2s;
    }

    .tag .remove-tag:hover {
        background: rgba(255,255,255,0.2);
    }

    .tag-input {
        border: none;
        outline: none;
        padding: 6px;
        font-size: 14px;
        flex: 1;
        min-width: 120px;
    }

    .points-input {
        max-width: 200px;
    }

    .submit-section {
        background: #f8f9fa;
        padding: 30px;
        text-align: center;
        border-top: 1px solid #e0e6ed;
    }

    .submit-btn {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
        padding: 15px 40px;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
    }

    .submit-btn:hover {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
    }

    .submit-btn:active {
        transform: translateY(0);
    }

    .error-alert {
        background: #fff5f5;
        border: 1px solid #fed7d7;
        color: #c53030;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 25px;
    }

    .error-alert ul {
        margin: 0;
        padding-left: 20px;
    }

    .field-error {
        color: #e74c3c;
        font-size: 14px;
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .help-text {
        font-size: 14px;
        color: #7f8c8d;
        margin-top: 6px;
        font-style: italic;
    }

    .form-row {
        display: flex;
        gap: 20px;
        align-items: flex-start;
    }

    .form-col {
        flex: 1;
    }

    .preview-section {
        background: #f8f9fa;
        border: 2px dashed #bdc3c7;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        text-align: center;
        color: #7f8c8d;
    }

    @media (max-width: 768px) {
        .publish-form {
            padding: 20px;
        }
        
        .form-row {
            flex-direction: column;
            gap: 0;
        }
        
        .submit-btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* Animation d'entrée */
    .publish-container {
        animation: slideInUp 0.6s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
";

// Inclure l'en-tête
include 'header.php';
?>

<div class="publish-container">
    <div class="publish-header">
        <h1><i class="fas fa-plus-circle"></i> Publier un Nouveau Problème</h1>
        <p>Partagez un défi de programmation avec la communauté</p>
    </div>

    <div class="ai-notice">
        <i class="fas fa-robot"></i>
        <strong>Analyse IA Automatique</strong>
        <p>La difficulté et le score seront automatiquement déterminés par notre IA après analyse de votre code et description.</p>
    </div>

    <?php if (!empty($form_errors['general'])): ?>
        <div class="error-alert">
            <h4><i class="fas fa-exclamation-triangle"></i> Erreurs détectées :</h4>
            <ul>
                <?php foreach ($form_errors['general'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="process_problem.php" method="POST" class="publish-form" id="publish-form">
        <!-- Section Informations de base -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i>
                Informations de base
            </h2>

            <div class="form-group">
                <label for="title" class="form-label">Titre du Problème *</label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       class="form-input" 
                       required 
                       placeholder="Ex: Algorithme de tri optimisé"
                       value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>"
                       maxlength="200">
                <?php if (!empty($form_errors['title'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['title'])); ?>
                    </div>
                <?php endif; ?>
                <div class="help-text">Choisissez un titre clair et descriptif (max 200 caractères)</div>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description détaillée *</label>
                <textarea id="description" 
                          name="description" 
                          class="form-textarea" 
                          required 
                          placeholder="Décrivez le problème en détail : contexte, objectifs, contraintes, exemples d'entrée/sortie..."
                          rows="8"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                <?php if (!empty($form_errors['description'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['description'])); ?>
                    </div>
                <?php endif; ?>
                <div class="help-text">Incluez des exemples d'entrée/sortie et expliquez clairement les attentes</div>
            </div>
        </div>

        <!-- Section Technique -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-code"></i>
                Spécifications techniques
            </h2>

            <div class="form-group">
                <label for="language" class="form-label">Langage de Programmation *</label>
                <select id="language" name="language" class="form-select" required>
                    <option value="">Sélectionnez un langage</option>
                    <?php foreach ($languages as $key => $value): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>" 
                                <?php echo (isset($form_data['language']) && $form_data['language'] === $key) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($value); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($form_errors['language'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['language'])); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="code" class="form-label">Code du problème *</label>
                <textarea id="code" 
                          name="code" 
                          class="form-textarea code-editor" 
                          required
                          placeholder="// Ajoutez le code du problème à analyser...
function problemExample() {
    // Code avec erreurs intentionnelles pour test
    // L'IA analysera ce code pour déterminer la difficulté
    return result;
}"><?php echo htmlspecialchars($form_data['code'] ?? ''); ?></textarea>
                <?php if (!empty($form_errors['code'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['code'])); ?>
                    </div>
                <?php endif; ?>
                <div class="help-text">
                    <strong>Important :</strong> Ce code sera analysé par l'IA pour déterminer automatiquement :
                    <br>• Le niveau de difficulté (Facile/Moyen/Difficile)
                    <br>• Le nombre et type d'erreurs
                    <br>• Le score de base selon la complexité
                </div>
            </div>
        </div>

        <!-- Section Catégorisation -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-tags"></i>
                Catégorisation
            </h2>

            <div class="form-group">
                <label class="form-label">Tags du problème</label>
                <input type="hidden" id="tags" name="tags" value="<?php echo htmlspecialchars($form_data['tags'] ?? ''); ?>">
                <div class="tags-container" id="tagsContainer">
                    <?php 
                    if (!empty($form_data['tags'])) {
                        $tags = explode(',', $form_data['tags']);
                        foreach ($tags as $tag) {
                            if (!empty(trim($tag))) {
                                echo '<div class="tag">' . htmlspecialchars(trim($tag)) . '<span class="remove-tag">×</span></div>';
                            }
                        }
                    }
                    ?>
                    <input type="text" 
                           class="tag-input" 
                           id="tagInput" 
                           placeholder="Ajouter un tag (Entrée pour valider)">
                </div>
                <?php if (!empty($form_errors['tags'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['tags'])); ?>
                    </div>
                <?php endif; ?>
                <div class="help-text">Ex: algorithme, tri, récursion, dynamique... (Appuyez sur Entrée pour ajouter)</div>
            </div>
        </div>

        <!-- Section Solution -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-lightbulb"></i>
                Solution et critères
            </h2>

            <div class="form-group">
                <label for="solution" class="form-label">Solution attendue ou critères d'évaluation *</label>
                <textarea id="solution" 
                          name="solution" 
                          class="form-textarea" 
                          required 
                          placeholder="Décrivez la solution attendue, les critères d'évaluation, les cas de test, la complexité souhaitée..."
                          rows="6"><?php echo htmlspecialchars($form_data['solution'] ?? ''); ?></textarea>
                <?php if (!empty($form_errors['solution'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['solution'])); ?>
                    </div>
                <?php endif; ?>
                <div class="help-text">Expliquez comment les solutions seront évaluées et quels sont les critères de réussite</div>
            </div>
        </div>

        <!-- Section de prévisualisation avec analyse IA -->
        <div class="preview-section">
            <i class="fas fa-robot"></i>
            <p><strong>Analyse IA en cours...</strong></p>
            <p>Une fois publié, l'IA analysera votre code pour :</p>
            <ul style="text-align: left; display: inline-block; margin: 10px 0;">
                <li>Identifier les types d'erreurs (syntaxe, logique, performance)</li>
                <li>Calculer la difficulté automatiquement</li>
                <li>Attribuer un score de base selon la complexité</li>
                <li>Ajouter des bonus pour les solutions non acceptées précédemment</li>
            </ul>
        </div>
    </form>

    <div class="submit-section">
        <button type="submit" form="publish-form" class="submit-btn">
            <i class="fas fa-robot"></i>
            Publier et Analyser avec l'IA
        </button>
        <p style="margin-top: 15px; color: #7f8c8d; font-size: 14px;">
            L'IA analysera automatiquement votre code pour déterminer la difficulté et le score
        </p>
    </div>
</div>

<?php
// Scripts JavaScript modifiés (sans gestion de difficulté manuelle)
$additional_scripts = "
    // Gestion des tags (identique)
    function updateTagsInput() {
        const tags = [];
        document.querySelectorAll('.tag').forEach(tag => {
            const tagText = tag.textContent.replace('×', '').trim();
            if (tagText) tags.push(tagText);
        });
        document.getElementById('tags').value = tags.join(',');
    }

    // Ajouter un tag
    const tagInput = document.getElementById('tagInput');
    const tagsContainer = document.getElementById('tagsContainer');

    tagInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            e.preventDefault();
            
            const tagText = this.value.trim();
            
            // Vérifier si le tag existe déjà
            const existingTags = Array.from(document.querySelectorAll('.tag'))
                .map(tag => tag.textContent.replace('×', '').trim().toLowerCase());
            
            if (existingTags.includes(tagText.toLowerCase())) {
                showMessage('Ce tag existe déjà!', 'warning');
                return;
            }
            
            // Créer le nouveau tag
            const tag = document.createElement('div');
            tag.className = 'tag';
            tag.innerHTML = tagText + '<span class=\"remove-tag\">×</span>';
            
            // Insérer avant l'input
            tagsContainer.insertBefore(tag, tagInput);
            
            // Vider l'input
            this.value = '';
            
            // Mettre à jour le champ caché
            updateTagsInput();
            
            // Animation d'apparition
            tag.style.opacity = '0';
            tag.style.transform = 'scale(0.8)';
            setTimeout(() => {
                tag.style.transition = 'all 0.3s ease';
                tag.style.opacity = '1';
                tag.style.transform = 'scale(1)';
            }, 10);
        }
    });

    // Supprimer un tag
    tagsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-tag')) {
            const tag = e.target.parentElement;
            
            // Animation de suppression
            tag.style.transition = 'all 0.3s ease';
            tag.style.opacity = '0';
            tag.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                tag.remove();
                updateTagsInput();
            }, 300);
        }
    });

    // Initialiser les tags existants
    updateTagsInput();

    // Validation en temps réel (sans difficulté et points)
    function validateField(fieldId, validationFn, errorMessage) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        field.addEventListener('blur', function() {
            const isValid = validationFn(this.value);
            const existingError = this.parentNode.querySelector('.field-error');
            
            if (!isValid && !existingError) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.innerHTML = '<i class=\"fas fa-exclamation-circle\"></i> ' + errorMessage;
                this.parentNode.appendChild(errorDiv);
                this.style.borderColor = '#e74c3c';
            } else if (isValid && existingError) {
                existingError.remove();
                this.style.borderColor = '#27ae60';
            }
        });
    }

    // Validations
    validateField('title', value => value.trim().length >= 5, 'Le titre doit contenir au moins 5 caractères');
    validateField('description', value => value.trim().length >= 50, 'La description doit contenir au moins 50 caractères');
    validateField('code', value => value.trim().length >= 20, 'Le code doit contenir au moins 20 caractères');
    validateField('solution', value => value.trim().length >= 20, 'La solution doit contenir au moins 20 caractères');

    // Analyse du code en temps réel pour prévisualisation
    function analyzeCodePreview() {
        const code = document.getElementById('code').value;
        const language = document.getElementById('language').value;
        
        if (code.trim().length > 50 && language) {
            // Simulation d'analyse IA
            const preview = document.querySelector('.preview-section');
            
            // Analyse basique pour la démo
            const codeLines = code.split('\\n').length;
            const hasLoops = /for|while|forEach/.test(code);
            const hasRecursion = /function.*\\{[\\s\\S]*\\1/.test(code);
            const hasComplexLogic = /if.*else|switch|case/.test(code);
            
            let estimatedDifficulty = 'Facile';
            let estimatedScore = 10;
            
            if (codeLines > 20 || hasRecursion) {
                estimatedDifficulty = 'Difficile';
                estimatedScore = 25;
            } else if (codeLines > 10 || hasLoops || hasComplexLogic) {
                estimatedDifficulty = 'Moyen';
                estimatedScore = 15;
            }
            
            preview.innerHTML = `
                <i class=\"fas fa-robot\"></i>
                <p><strong>Aperçu de l'analyse IA</strong></p>
                <div style=\"background: rgba(52, 152, 219, 0.1); padding: 15px; border-radius: 8px; margin: 10px 0;\">
                    <p><strong>Difficulté estimée :</strong> <span style=\"color: #3498db;\">${estimatedDifficulty}</span></p>
                    <p><strong>Score de base estimé :</strong> <span style=\"color: #27ae60;\">${estimatedScore} points</span></p>
                    <p><strong>Lignes de code :</strong> ${codeLines}</p>
                    <p><strong>Complexité détectée :</strong> ${hasRecursion ? 'Récursion' : hasLoops ? 'Boucles' : hasComplexLogic ? 'Logique conditionnelle' : 'Basique'}</p>
                </div>
                <p style=\"font-size: 12px; color: #7f8c8d;\">* Estimation préliminaire - L'analyse finale sera plus précise</p>
            `;
        }
    }

    // Mettre à jour l'analyse lors des changements de code
    document.getElementById('code').addEventListener('input', function() {
        clearTimeout(window.analyzeTimeout);
        window.analyzeTimeout = setTimeout(analyzeCodePreview, 1000);
    });

    document.getElementById('language').addEventListener('change', analyzeCodePreview);

    // Compteur de caractères pour les champs texte
    function addCharacterCounter(fieldId, maxLength) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        const counter = document.createElement('div');
        counter.style.cssText = 'font-size: 12px; color: #7f8c8d; text-align: right; margin-top: 5px;';
        field.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength ? maxLength - field.value.length : field.value.length;
            counter.textContent = maxLength ? 
                remaining + ' caractères restants' : 
                field.value.length + ' caractères';
            
            if (maxLength && remaining < 0) {
                counter.style.color = '#e74c3c';
            } else {
                counter.style.color = '#7f8c8d';
            }
        }
        
        field.addEventListener('input', updateCounter);
        updateCounter();
    }

    // Ajouter les compteurs
    addCharacterCounter('title', 200);
    addCharacterCounter('description');
    addCharacterCounter('code');
    addCharacterCounter('solution');

    // Animation du formulaire
    document.addEventListener('DOMContentLoaded', function() {
        const sections = document.querySelectorAll('.form-section');
        sections.forEach((section, index) => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                section.style.transition = 'all 0.6s ease';
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, index * 200);
        });
    });

    // Fonction pour afficher des messages
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.padding = '15px 20px';
        messageDiv.style.borderRadius = '8px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.maxWidth = '300px';
        messageDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        messageDiv.style.transition = 'opacity 0.3s ease';
        messageDiv.style.fontWeight = '500';
        
        switch(type) {
            case 'success':
                messageDiv.style.backgroundColor = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.border = '1px solid #c3e6cb';
                messageDiv.innerHTML = '<i class=\"fas fa-check-circle\"></i> ' + message;
                break;
            case 'error':
                messageDiv.style.backgroundColor = '#f8d7da';
                messageDiv.style.color = '#721c24';
                messageDiv.style.border = '1px solid #f5c6cb';
                messageDiv.innerHTML = '<i class=\"fas fa-exclamation-circle\"></i> ' + message;
                break;
            case 'warning':
                messageDiv.style.backgroundColor = '#fff3cd';
                messageDiv.style.color = '#856404';
                messageDiv.style.border = '1px solid #ffeaa7';
                messageDiv.innerHTML = '<i class=\"fas fa-exclamation-triangle\"></i> ' + message;
                break;
            default:
                messageDiv.style.backgroundColor = '#d1ecf1';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = '<i class=\"fas fa-info-circle\"></i> ' + message;
        }
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 4000);
    }

    // Validation finale avant soumission (sans points et difficulté)
    document.querySelector('form').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const language = document.getElementById('language').value;
        const code = document.getElementById('code').value.trim();
        const solution = document.getElementById('solution').value.trim();
        
        const errors = [];
        
        if (title.length < 5) errors.push('Le titre doit contenir au moins 5 caractères');
        if (description.length < 50) errors.push('La description doit contenir au moins 50 caractères');
        if (!language) errors.push('Veuillez sélectionner un langage');
        if (code.length < 20) errors.push('Le code doit contenir au moins 20 caractères');
        if (solution.length < 20) errors.push('La solution doit contenir au moins 20 caractères');
        
        if (errors.length > 0) {
            e.preventDefault();
            showMessage('Veuillez corriger les erreurs suivantes:\\n' + errors.join('\\n'), 'error');
            
            // Faire défiler vers le premier champ en erreur
            const firstErrorField = document.querySelector('.field-error');
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            return false;
        }
        
        // Animation de soumission
        const submitBtn = document.querySelector('.submit-btn');
        submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Analyse IA en cours...';
        submitBtn.disabled = true;
        
        // Afficher un message d'information
        showMessage('Envoi vers l\\'IA pour analyse automatique...', 'info');
    });

    console.log('🤖 Éditeur de problème avec IA initialisé');
    console.log('📊 L\\'IA analysera automatiquement la difficulté et le score');
";

// Inclure le pied de page
include 'footer.php';
?>
