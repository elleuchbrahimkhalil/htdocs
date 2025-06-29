<?php
/**
 * Composants des sections du problème
 */

function renderProblemDescription($problem) {
    ?>
    <div class="section">
        <h2 class="section-title"><i class="fas fa-info-circle"></i> Description</h2>
        <div><?= nl2br(htmlspecialchars($problem['description'])) ?></div>
    </div>
    <?php
}

function renderProblemCode($problem) {
    if (empty($problem['code'])) return;
    ?>
    <div class="section">
        <h2 class="section-title"><i class="fas fa-code"></i> Code du problème</h2>
        <div style="position: relative;">
            <pre class="code-block"><code class="language-<?= strtolower($problem['language']) ?>"><?= htmlspecialchars($problem['code']) ?></code></pre>
            <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copier</button>
        </div>
    </div>
    <?php
}

function renderProblemSolution($problem) {
    ?>
    <div class="section">
        <h2 class="section-title"><i class="fas fa-lightbulb"></i> Solution attendue</h2>
        <div><?= nl2br(htmlspecialchars($problem['solution'])) ?></div>
    </div>
    <?php
}

function renderUserSolutionStatus($user_solution) {
    if (!$user_solution) return;
    
    $status_class = '';
    $status_text = '';
    $status_icon = '';
    
    switch ($user_solution['status']) {
        case 'pending':
            $status_class = 'pending';
            $status_text = 'Votre solution est en cours d\'évaluation';
            $status_icon = 'fas fa-clock';
            break;
        case 'approved':
        case 'accepted':
            $status_class = 'approved';
            $status_text = 'Félicitations! Votre solution a été approuvée';
            $status_icon = 'fas fa-check-circle';
            break;
        case 'rejected':
            $status_class = 'rejected';
            $status_text = 'Votre solution a été rejetée. Vous pouvez soumettre une nouvelle solution.';
            $status_icon = 'fas fa-times-circle';
            break;
    }
    ?>
    <div class="user-solution-status <?= $status_class ?>">
        <i class="<?= $status_icon ?>"></i> <?= $status_text ?>
        <div style="font-size: 12px; margin-top: 5px; opacity: 0.8;">
            Soumise le <?= date('d/m/Y à H:i', strtotime($user_solution['created_at'])) ?>
            <?php if ($user_solution['evaluated_at']): ?>
                • Évaluée le <?= date('d/m/Y à H:i', strtotime($user_solution['evaluated_at'])) ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function renderSolutionsSection($solutions) {
    ?>
    <div class="solutions-section">
        <h2 class="section-title"><i class="fas fa-check-circle"></i> Solutions approuvées (<?= count($solutions) ?>)</h2>
        
        <?php if (empty($solutions)): ?>
            <div class="no-solutions">
                <i class="fas fa-code" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                <p>Aucune solution approuvée pour le moment.</p>
                <p>Soyez le premier à proposer une solution!</p>
            </div>
        <?php else: ?>
            <?php foreach ($solutions as $solution): ?>
                <div class="solution-card">
                    <div class="solution-header">
                        <div class="solution-author">
                            <img src="<?= !empty($solution['avatar_url']) ? htmlspecialchars($solution['avatar_url']) : 'default.png' ?>" 
                                 alt="Avatar" class="solution-avatar" onerror="this.src='default.png'">
                            <div>
                                <div><?= htmlspecialchars($solution['username']) ?></div>
                                <div class="solution-date"><?= date('d/m/Y à H:i', strtotime($solution['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($solution['explanation'])): ?>
                        <div class="solution-explanation">
                            <h4><i class="fas fa-comment-alt"></i> Explication</h4>
                            <?= nl2br(htmlspecialchars($solution['explanation'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($solution['solution_code'])): ?>
                        <div style="position: relative;">
                            <pre class="code-block"><code><?= htmlspecialchars($solution['solution_code']) ?></code></pre>
                            <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copier</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

function renderProblemActions($problem, $user_solution) {
    ?>
    <div class="actions">
        <div>
            <a href="submit_solution.php?problem_id=<?= $problem['problem_id'] ?>" class="btn btn-success">
                <i class="fas fa-paper-plane"></i> 
                <?= $user_solution ? 'Soumettre une nouvelle solution' : 'Soumettre une solution' ?>
            </a>
            
            <?php if (isLoggedIn()): ?>
                <a href="user_feedback.php" class="btn btn-outline">
                    <i class="fas fa-list"></i> Mes solutions
                </a>
            <?php endif; ?>
        </div>
        
        <div>
            <a href="exacueil.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>
        </div>
    </div>
    <?php
}
?>