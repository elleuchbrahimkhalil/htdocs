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

// CSS spécifique à cette page
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

    .difficulty-selector {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .difficulty-option {
        padding: 12px 20px;
        border: 2px solid #e0e6ed;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
        background: white;
        min-width: 100px;
        text-align: center;
    }

    .difficulty-option.easy {
        color: #27ae60;
        border-color: #27ae60;
    }

    .difficulty-option.easy.selected {
        background: #27ae60;
        color: white;
    }

    .difficulty-option.medium {
        color: #f39c12;
        border-color: #f39c12;
    }

    .difficulty-option.medium.selected {
        background: #f39c12;
        color: white;
    }

    .difficulty-option.hard {
        color: #e74c3c;
        border-color: #e74c3c;
    }

    .difficulty-option.hard.selected {
        background: #e74c3c;
        color: white;
    }

    .difficulty-option:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
        
        .difficulty-selector {
            justify-content: center;
        }
        
        .difficulty-option {
            flex: 1;
            min-width: auto;
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

    <form action="process_problem.php" method="POST" class="publish-form">
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

            <div class="form-row">
                <div class="form-col">
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
                </div>

                <div class="form-col">
                    <div class="form-group">
                        <label for="points" class="form-label">Points attribués *</label>
                        <input type="number" 
                               id="points" 
                               name="points" 
                               class="form-input points-input" 
                               required 
                               min="1" 
                               max="100" 
                               placeholder="10"
                               value="<?php echo htmlspecialchars($form_data['points'] ?? '10'); ?>">
                        <?php if (!empty($form_errors['points'])): ?>
                            <div class="field-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars(implode(', ', $form_errors['points'])); ?>
                            </div>
                        <?php endif; ?>
                        <div class="help-text">Entre 1 et 100 points selon la difficulté</div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Niveau de difficulté *</label>
                <input type="hidden" id="difficulty" name="difficulty" value="<?php echo htmlspecialchars($form_data['difficulty'] ?? 'medium'); ?>">
                <div class="difficulty-selector">
                    <div class="difficulty-option easy <?php echo (isset($form_data['difficulty']) && $form_data['difficulty'] === 'easy') ? 'selected' : ''; ?>" 
                         data-value="easy">
                        <i class="fas fa-leaf"></i> Facile
                    </div>
                    <div class="difficulty-option medium <?php echo (!isset($form_data['difficulty']) || $form_data['difficulty'] === 'medium') ? 'selected' : ''; ?>" 
                         data-value="medium">
                        <i class="fas fa-balance-scale"></i> Moyen
                    </div>
                    <div class="difficulty-option hard <?php echo (isset($form_data['difficulty']) && $form_data['difficulty'] === 'hard') ? 'selected' : ''; ?>" 
                         data-value="hard">
                        <i class="fas fa-fire"></i> Difficile
                    </div>
                </div>
                <?php if (!empty($form_errors['difficulty'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['difficulty'])); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="code" class="form-label">Code de démarrage (optionnel)</label>
                <textarea id="code" 
                          name="code" 
                          class="form-textarea code-editor" 
                          placeholder="// Ajoutez du code de démarrage, des exemples ou des templates...
function solutionExample() {
    // Votre code ici
    return result;
}"><?php echo htmlspecialchars($form_data['code'] ?? ''); ?></textarea>
                <?php if (!empty($form_errors['code'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['code'])); ?>
                    </div>
                <?php endif; ?>
                <div class="help-text">Code de démarrage, template ou exemple pour aider les participants</div>
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

        <!-- Section de prévisualisation -->
        <div class="preview-section">
            <i class="fas fa-eye"></i>
            <p><strong>Prévisualisation</strong></p>
            <p>Votre problème sera visible par tous les membres de la communauté une fois publié.</p>
        </div>
    </form>

    <div class="submit-section">
        <button type="submit" form="publish-form" class="submit-btn">
            <i class="fas fa-paper-plane"></i>
            Publier le Problème
        </button>
        <p style="margin-top: 15px; color: #7f8c8d; font-size: 14px;">
            En publiant, vous acceptez que votre problème soit accessible à tous les utilisateurs
        </p>
    </div>
</div>

<?php
// Scripts JavaScript pour l'interactivité
$additional_scripts = "
    // Gestion de la sélection de difficulté
    document.querySelectorAll('.difficulty-option').forEach(option => {
        option.addEventListener('click', function() {
            // Retirer la sélection de tous les autres
            document.querySelectorAll('.difficulty-option').forEach(opt => 
                opt.classList.remove('selected')
            );
            
            // Ajouter la sélection à l'option cliquée
            this.classList.add('selected');
            
            // Mettre à jour le champ caché
            document.getElementById('difficulty').value = this.getAttribute('data-value');
            
            // Animation
            this.style.transform = 'scale(1.05)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Gestion des tags
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

    // Validation en temps réel
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
    validateField('solution', value => value.trim().length >= 20, 'La solution doit contenir au moins 20 caractères');
    validateField('points', value => {
        const num = parseInt(value);
        return num >= 1 && num <= 100;
    }, 'Les points doivent être entre 1 et 100');

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

    // Sauvegarde automatique en brouillon (localStorage)
    function saveDraft() {
        const formData = {
            title: document.getElementById('title').value,
            description: document.getElementById('description').value,
            language: document.getElementById('language').value,
            code: document.getElementById('code').value,
            difficulty: document.getElementById('difficulty').value,
            tags: document.getElementById('tags').value,
            solution: document.getElementById('solution').value,
            points: document.getElementById('points').value,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('problem_draft', JSON.stringify(formData));
    }

    // Charger le brouillon
    function loadDraft() {
        const draft = localStorage.getItem('problem_draft');
        if (draft) {
            try {
                const data = JSON.parse(draft);
                
                // Vérifier si le brouillon n'est pas trop ancien (24h)
                const draftTime = new Date(data.timestamp);
                const now = new Date();
                const hoursDiff = (now - draftTime) / (1000 * 60 * 60);
                
                if (hoursDiff < 24) {
                    if (confirm('Un brouillon a été trouvé. Voulez-vous le charger?')) {
                        document.getElementById('title').value = data.title || '';
                        document.getElementById('description').value = data.description || '';
                        document.getElementById('language').value = data.language || '';
                        document.getElementById('code').value = data.code || '';
                        document.getElementById('difficulty').value = data.difficulty || 'medium';
                        document.getElementById('solution').value = data.solution || '';
                        document.getElementById('points').value = data.points || '10';
                        
                        // Mettre à jour la sélection de difficulté
                        document.querySelectorAll('.difficulty-option').forEach(opt => 
                            opt.classList.remove('selected')
                        );
                        const selectedDifficulty = document.querySelector('.difficulty-option[data-value=\"' + (data.difficulty || 'medium') + '\"]');
                        if (selectedDifficulty) {
                            selectedDifficulty.classList.add('selected');
                        }
                        
                        // Charger les tags
                        if (data.tags) {
                            document.getElementById('tags').value = data.tags;
                            const tagsContainer = document.getElementById('tagsContainer');
                            const tagInput = document.getElementById('tagInput');
                            
                            // Supprimer les tags existants
                            document.querySelectorAll('.tag').forEach(tag => tag.remove());
                            
                            // Ajouter les tags du brouillon
                            data.tags.split(',').forEach(tagText => {
                                if (tagText.trim()) {
                                    const tag = document.createElement('div');
                                    tag.className = 'tag';
                                    tag.innerHTML = tagText.trim() + '<span class=\"remove-tag\">×</span>';
                                    tagsContainer.insertBefore(tag, tagInput);
                                }
                            });
                        }
                        
                        showMessage('Brouillon chargé avec succès!', 'success');
                    }
                } else {
                    // Supprimer le brouillon trop ancien
                    localStorage.removeItem('problem_draft');
                }
            } catch (e) {
                console.error('Erreur lors du chargement du brouillon:', e);
                localStorage.removeItem('problem_draft');
            }
        }
    }

    // Sauvegarder automatiquement toutes les 30 secondes
    setInterval(saveDraft, 30000);

    // Sauvegarder lors de la saisie
    document.addEventListener('input', function(e) {
        if (e.target.matches('input, textarea, select')) {
            clearTimeout(window.draftTimeout);
            window.draftTimeout = setTimeout(saveDraft, 2000);
        }
    });

    // Charger le brouillon au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        // Attendre un peu pour que tous les éléments soient initialisés
        setTimeout(loadDraft, 500);
    });

    // Supprimer le brouillon lors de la soumission réussie
    document.querySelector('form').addEventListener('submit', function() {
        localStorage.removeItem('problem_draft');
    });

    // Confirmation avant de quitter la page si des données sont saisies
    let formModified = false;
    document.addEventListener('input', function(e) {
        if (e.target.matches('input, textarea, select')) {
            formModified = true;
        }
    });

    window.addEventListener('beforeunload', function(e) {
        if (formModified) {
            const message = 'Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter?';
            e.returnValue = message;
            return message;
        }
    });

    // Prévisualisation en temps réel
    function updatePreview() {
        const title = document.getElementById('title').value;
        const difficulty = document.getElementById('difficulty').value;
        const language = document.getElementById('language').value;
        const points = document.getElementById('points').value;
        
        if (title || difficulty || language || points) {
            const preview = document.querySelector('.preview-section');
            let previewHTML = '<i class=\"fas fa-eye\"></i><p><strong>Aperçu de votre problème</strong></p>';
            
            if (title) {
                previewHTML += '<h3 style=\"color: #2c3e50; margin: 10px 0;\">' + title + '</h3>';
            }
            
            const meta = [];
            if (language) meta.push('<i class=\"fas fa-code\"></i> ' + document.querySelector('#language option[value=\"' + language + '\"]').textContent);
            if (difficulty) {
                const difficultyText = difficulty === 'easy' ? 'Facile' : difficulty === 'medium' ? 'Moyen' : 'Difficile';
                meta.push('<span class=\"difficulty ' + difficulty + '\">' + difficultyText + '</span>');
            }
            if (points) meta.push('<i class=\"fas fa-award\"></i> ' + points + ' points');
            
            if (meta.length > 0) {
                previewHTML += '<div style=\"font-size: 14px; color: #7f8c8d; margin: 10px 0;\">' + meta.join(' • ') + '</div>';
            }
            
            preview.innerHTML = previewHTML;
        }
    }

    // Mettre à jour la prévisualisation lors des changements
    document.addEventListener('input', updatePreview);
    document.addEventListener('change', updatePreview);

    // Animation des boutons
    document.querySelectorAll('.btn, .submit-btn, .difficulty-option').forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });

    // Validation finale avant soumission
    document.querySelector('form').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const language = document.getElementById('language').value;
        const solution = document.getElementById('solution').value.trim();
        const points = parseInt(document.getElementById('points').value);
        
        const errors = [];
        
        if (title.length < 5) errors.push('Le titre doit contenir au moins 5 caractères');
        if (description.length < 50) errors.push('La description doit contenir au moins 50 caractères');
        if (!language) errors.push('Veuillez sélectionner un langage');
        if (solution.length < 20) errors.push('La solution doit contenir au moins 20 caractères');
        if (isNaN(points) || points < 1 || points > 100) errors.push('Les points doivent être entre 1 et 100');
        
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
        submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Publication en cours...';
        submitBtn.disabled = true;
        
        // Sauvegarder une dernière fois
        saveDraft();
    });

    // Raccourcis clavier
    document.addEventListener('keydown', function(e) {
        // Ctrl+S pour sauvegarder le brouillon
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveDraft();
            showMessage('Brouillon sauvegardé!', 'success');
        }
        
        // Ctrl+Enter pour soumettre le formulaire
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            document.querySelector('form').submit();
        }
    });

    console.log('📝 Éditeur de problème initialisé');
    console.log('💡 Raccourcis: Ctrl+S (sauvegarder), Ctrl+Enter (publier)');
";

// Inclure le pied de page
include 'footer.php';
?>
