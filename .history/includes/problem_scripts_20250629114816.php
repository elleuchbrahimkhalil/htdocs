<?php
/**
 * Scripts JavaScript pour la page problème
 * Retourne du JavaScript pur sans guillemets d'échappement
 */
function getProblemScripts() {
    ob_start();
    ?>
// Gestion des favoris
document.addEventListener('DOMContentLoaded', function() {
    const favoriteBtn = document.querySelector('.favorite-btn');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
            const problemId = this.dataset.pid;
            const isActive = this.classList.contains('active');
            
            // Désactiver le bouton pendant la requête
            this.disabled = true;
            const icon = this.querySelector('i');
            icon.className = 'fas fa-spinner fa-spin';
            
            const formData = new FormData();
            formData.append('problem_id', problemId);
            formData.append('favorite_action', isActive ? 'remove' : 'add');
            
            fetch('manage_favorites.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'added') {
                        this.classList.add('active');
                        this.title = 'Retirer des favoris';
                        showMessage('Ajouté aux favoris!', 'success');
                    } else if (data.action === 'removed') {
                        this.classList.remove('active');
                        this.title = 'Ajouter aux favoris';
                        showMessage('Retiré des favoris!', 'info');
                    }
                } else {
                    showMessage('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur de connexion', 'error');
            })
            .finally(() => {
                this.disabled = false;
                icon.className = 'fas fa-star';
            });
        });
    }
});

// Fonction pour copier le code
function copyCode(button) {
    const codeBlock = button.parentElement.querySelector('code');
    const text = codeBlock.textContent;
    
    navigator.clipboard.writeText(text).then(function() {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copié!';
        button.style.background = 'rgba(39, 174, 96, 0.1)';
        button.style.color = '#27ae60';
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.background = 'rgba(52, 152, 219, 0.1)';
            button.style.color = '#3498db';
        }, 2000);
    }).catch(function(err) {
        console.error('Erreur lors de la copie:', err);
        showMessage('Impossible de copier le code', 'error');
    });
}

// Fonction pour afficher des messages
function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.style.position = 'fixed';
    messageDiv.style.top = '20px';
    messageDiv.style.right = '20px';
    messageDiv.style.padding = '15px 20px';
    messageDiv.style.borderRadius = '6px';
    messageDiv.style.zIndex = '9999';
    messageDiv.style.maxWidth = '300px';
    messageDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    messageDiv.style.transition = 'opacity 0.3s ease';
    
    switch(type) {
        case 'success':
            messageDiv.style.backgroundColor = '#d4edda';
            messageDiv.style.color = '#155724';
            messageDiv.style.border = '1px solid #c3e6cb';
            messageDiv.innerHTML = '✅ ' + message;
            break;
        case 'error':
            messageDiv.style.backgroundColor = '#f8d7da';
            messageDiv.style.color = '#721c24';
            messageDiv.style.border = '1px solid #f5c6cb';
            messageDiv.innerHTML = '❌ ' + message;
            break;
        case 'info':
            messageDiv.style.backgroundColor = '#d1ecf1';
            messageDiv.style.color = '#0c5460';
            messageDiv.style.border = '1px solid #bee5eb';
            messageDiv.innerHTML = 'ℹ️ ' + message;
            break;
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

// Animation d'apparition des sections
document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('.section, .solutions-section, .actions');
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        setTimeout(() => {
            section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, (index + 1) * 200);
    });
});

// Gestion des erreurs d'images
document.addEventListener('DOMContentLoaded', function() {
    const avatars = document.querySelectorAll('.author-avatar, .solution-avatar');
    avatars.forEach(avatar => {
        avatar.addEventListener('error', function() {
            this.src = 'default.png';
        });
    });
});

// Fonction pour partager le problème
function shareProblem() {
    const url = window.location.href;
    const title = document.querySelector('.problem-title').textContent;
    
    if (navigator.share) {
        navigator.share({
            title: title,
            text: 'Découvrez ce problème de programmation: ' + title,
            url: url
        });
    } else {
        navigator.clipboard.writeText(url).then(() => {
            showMessage('Lien copié dans le presse-papiers!', 'success');
        });
    }
}

// Fonction pour imprimer le problème
function printProblem() {
    const printContent = document.querySelector('.problem-container').cloneNode(true);
    
    // Supprimer les éléments non nécessaires pour l'impression
    const elementsToRemove = printContent.querySelectorAll('.favorite-btn, .actions, .copy-btn');
    elementsToRemove.forEach(el => el.remove());
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Problème: ${document.querySelector('.problem-title').textContent}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .problem-container { box-shadow: none; }
                    .code-block { background: #f5f5f5; color: #333; border: 1px solid #ddd; }
                    .section { border-bottom: 1px solid #eee; }
                    @media print {
                        body { margin: 0; }
                        .problem-container { box-shadow: none; }
                    }
                </style>
            </head>
            <body>
                ${printContent.outerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Ajouter les boutons dynamiquement
document.addEventListener('DOMContentLoaded', function() {
    const actions = document.querySelector('.actions');
    if (actions) {
        // Bouton de partage
        const shareBtn = document.createElement('button');
        shareBtn.className = 'btn btn-outline';
        shareBtn.innerHTML = '<i class="fas fa-share"></i> Partager';
        shareBtn.onclick = shareProblem;
        actions.appendChild(shareBtn);
        
        // Bouton d'impression
        const printBtn = document.createElement('button');
        printBtn.className = 'btn btn-outline';
        printBtn.innerHTML = '<i class="fas fa-print"></i> Imprimer';
        printBtn.onclick = printProblem;
        actions.appendChild(printBtn);
    }
});

console.log('✅ Scripts de la page problème chargés');
    <?php
    return ob_get_clean();
}
?>
