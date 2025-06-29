<?php
/**
 * Composant header du problème
 */
function renderProblemHeader($problem, $stats) {
    $difficulty_text = formatDifficulty($problem['difficulty']);
    $tags = formatTags($problem['tags']);
    ?>
    <div class="problem-header">
        <h1 class="problem-title"><?= htmlspecialchars($problem['title']) ?></h1>
      
        <?php if (isLoggedIn()): ?>
        <button class="favorite-btn <?= $problem['is_favorite'] ? 'active' : '' ?>" 
                data-pid="<?= $problem['problem_id'] ?>" 
                title="<?= $problem['is_favorite'] ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
            <i class="fas fa-star"></i>
        </button>
        <?php endif; ?>
      
        <div class="author-info">
            <img src="<?= !empty($problem['avatar_url']) ? htmlspecialchars($problem['avatar_url']) : 'default.png' ?>" 
               alt="Avatar" class="author-avatar" onerror="this.src='default.png'">
            <div>
                <div><?= htmlspecialchars($problem['author_name'] ?? $problem['username']) ?></div>
                <div class="solution-date">Publié le <?= date('d/m/Y à H:i', strtotime($problem['created_at'])) ?></div>
            </div>
        </div>
      
        <div class="problem-meta">
            <span><i class="fas fa-code"></i> <?= htmlspecialchars(ucfirst($problem['language'])) ?></span>
            <span class="difficulty <?= htmlspecialchars($problem['difficulty']) ?>">
                <?= $difficulty_text ?>
            </span>
            <span><i class="fas fa-award"></i> <?= htmlspecialchars($problem['points']) ?> points</span>
            <?php if ($problem['is_solved']): ?>
                <span style="color: #27ae60;"><i class="fas fa-check-circle"></i> Résolu</span>
            <?php endif; ?>
        </div>
      
        <?php if (!empty($tags)): ?>
            <div class="problem-tags">
                <?php foreach ($tags as $tag): ?>
                    <span class="tag"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
      
        <div class="problem-stats">
            <div class="stat-item">
                <div class="stat-value"><?= $stats['total_solutions'] ?? 0 ?></div>
                <div class="stat-label">Solutions soumises</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $stats['approved_solutions'] ?? 0 ?></div>
                <div class="stat-label">Solutions approuvées</div>
            </div>
        </div>
    </div>
    <?php
}
?>