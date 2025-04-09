<?php
// Set page title
$page_title = "Publier un Problème";

// Additional CSS specific to this page
$additional_css = "
    .publish-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
    }

    input[type=\"text\"],
    textarea,
    select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    textarea {
        min-height: 200px;
        resize: vertical;
    }

    .difficulty-select {
        display: flex;
        gap: 15px;
    }

    .difficulty-option {
        padding: 8px 16px;
        border: 2px solid #ddd;
        border-radius: 20px;
        cursor: pointer;
    }

    .difficulty-option.easy { color: #4CAF50; }
    .difficulty-option.medium { color: #FF9800; }
    .difficulty-option.hard { color: #F44336; }

    .difficulty-option.selected {
        background-color: currentColor;
        color: white;
    }
    
    .tags-input {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .tag {
        background: #e0e0e0;
        padding: 5px 10px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .submit-btn {
        background: #4CAF50;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }

    .submit-btn:hover {
        background: #45a049;
    }

    .error-list {
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .error-list ul {
        margin: 0;
        padding-left: 20px;
    }

    .field-error {
        color: #dc3545;
        font-size: 14px;
        margin-top: 5px;
    }

    .code-editor {
        font-family: 'Courier New', Courier, monospace;
        min-height: 200px;
    }
";

// Include header
include 'header.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Vous devez être connecté pour publier un problème";
    header('Location: exlogin.php');
    exit;
}

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
?>

<h1>Publier un Nouveau Problème</h1>

<?php if (!empty($form_errors['general'])): ?>
    <div class="error-list">
        <ul>
            <?php foreach ($form_errors['general'] as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="publish-container">
    <form action="process_problem.php" method="POST">
        <div class="form-group">
            <label for="title">Titre du Problème</label>
            <input type="text" id="title" name="title" required 
                   placeholder="Entrez un titre clair et concis"
                   value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>">
            <?php if (!empty($form_errors['title'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['title'])); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" required 
                      placeholder="Décrivez le problème en détail..."><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
            <?php if (!empty($form_errors['description'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['description'])); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="language">Langage de Programmation</label>
            <select id="language" name="language" required>
                <option value="">Sélectionnez un langage</option>
                <?php foreach ($languages as $key => $value): ?>
                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (isset($form_data['language']) && $form_data['language'] === $key) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($value); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($form_errors['language'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['language'])); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="code">Code du Problème (optionnel)</label>
            <textarea id="code" name="code" class="code-editor" 
                      placeholder="Ajoutez du code de démarrage ou un exemple..."><?php echo htmlspecialchars($form_data['code'] ?? ''); ?></textarea>
            <?php if (!empty($form_errors['code'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['code'])); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Difficulté</label>
            <input type="hidden" id="difficulty" name="difficulty" value="<?php echo htmlspecialchars($form_data['difficulty'] ?? 'medium'); ?>">
            <div class="difficulty-select">
                <div class="difficulty-option easy <?php echo (isset($form_data['difficulty']) && $form_data['difficulty'] === 'easy') ? 'selected' : ''; ?>" data-value="easy">Facile</div>
                <div class="difficulty-option medium <?php echo (!isset($form_data['difficulty']) || $form_data['difficulty'] === 'medium') ? 'selected' : ''; ?>" data-value="medium">Moyen</div>
                <div class="difficulty-option hard <?php echo (isset($form_data['difficulty']) && $form_data['difficulty'] === 'hard') ? 'selected' : ''; ?>" data-value="hard">Difficile</div>
            </div>
            <?php if (!empty($form_errors['difficulty'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['difficulty'])); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Tags</label>
            <input type="hidden" id="tags" name="tags" value="<?php echo htmlspecialchars($form_data['tags'] ?? ''); ?>">
            <div class="tags-input">
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
                <input type="text" id="tag-input" placeholder="Ajouter un tag...">
            </div>
            <?php if (!empty($form_errors['tags'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['tags'])); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="solution">Solution Attendue</label>
            <textarea id="solution" name="solution" required 
                      placeholder="Décrivez la solution ou les critères de résolution..."><?php echo htmlspecialchars($form_data['solution'] ?? ''); ?></textarea>
            <?php if (!empty($form_errors['solution'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['solution'])); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="points">Points</label>
            <input type="number" id="points" name="points" required min="1" max="100" 
                   placeholder="Nombre de points pour ce problème"
                   value="<?php echo htmlspecialchars($form_data['points'] ?? '10'); ?>">
            <?php if (!empty($form_errors['points'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['points'])); ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="submit-btn">Publier le Problème</button>
    </form>
</div>

<?php
// Additional scripts
$additional_scripts = "
    // Gestion de la sélection de difficulté
    document.querySelectorAll('.difficulty-option').forEach(option => {
        option.addEventListener('click', () => {
            document.querySelectorAll('.difficulty-option').forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
            document.getElementById('difficulty').value = option.getAttribute('data-value');
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

    const tagsInput = document.getElementById('tag-input');
    tagsInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && tagsInput.value.trim()) {
            const tag = document.createElement('div');
            tag.className = 'tag';
            tag.innerHTML = `
                \${tagsInput.value.trim()}
                <span class=\"remove-tag\">×</span>
            `;
            tagsInput.parentNode.insertBefore(tag, tagsInput);
            tagsInput.value = '';
            updateTagsInput();
            e.preventDefault();
        }
    });

    // Gestion de la suppression des tags
    document.querySelector('.tags-input').addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-tag')) {
            e.target.parentElement.remove();
            updateTagsInput();
        }
    });

    // Initialisation des tags
    updateTagsInput();
";

// Include footer
include 'footer.php';
?>