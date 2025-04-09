<footer class="main-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>CodeChallenge</h3>
                <p>Améliorez vos compétences en programmation en résolvant des défis et en partageant vos solutions.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-github"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Liens Rapides</h3>
                <ul>
                    <li><a href="problems.php">Problèmes</a></li>
                    <li><a href="leaderboard.php">Classement</a></li>
                    <li><a href="about.php">À propos</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Ressources</h3>
                <ul>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">Tutoriels</a></li>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">API</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> CodeChallenge. Tous droits réservés.</p>
        </div>
    </footer>
    
    <style>
        .main-footer {
            background: var(--secondary-color);
            color: var(--text-light);
            padding: var(--spacing-xl) 0 0;
            margin-top: var(--spacing-xl);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-xl);
            padding: 0 var(--spacing-lg);
        }
        
        .footer-section h3 {
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: var(--spacing-md);
            font-size: 1.2rem;
        }
        
        .footer-section p {
            line-height: 1.6;
            opacity: 0.8;
        }
        
        .social-links {
            display: flex;
            gap: var(--spacing-md);
            margin-top: var(--spacing-md);
        }
        
        .social-links a {
            color: var(--text-light);
            font-size: 1.5rem;
            transition: color var(--transition-speed);
        }
        
        .social-links a:hover {
            color: var(--primary-color);
        }
        
        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-section ul li {
            margin-bottom: var(--spacing-sm);
        }
        
        .footer-section ul a {
            color: var(--text-light);
            text-decoration: none;
            opacity: 0.8;
            transition: opacity var(--transition-speed), color var(--transition-speed);
        }
        
        .footer-section ul a:hover {
            opacity: 1;
            color: var(--primary-color);
        }
        
        .footer-bottom {
            text-align: center;
            padding: var(--spacing-md) 0;
            margin-top: var(--spacing-xl);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            opacity: 0.7;
        }
    </style>
    
    <script>
        // Animation des éléments au défilement
        document.addEventListener('DOMContentLoaded', function() {
            const animateElements = document.querySelectorAll('.animate-on-scroll');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in-up');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            animateElements.forEach(element => {
                observer.observe(element);
            });
        });
    </script>
    <?php 
    // Afficher l'avatar utilisateur
    require_once 'exavatar.php';
    if (function_exists('displayUserAvatar')) {
        displayUserAvatar();
    }
    ?>
</div>
</div>

<?php if (isset($additional_scripts)): ?>
<script>
    <?php echo $additional_scripts; ?>
</script>
<?php endif; ?>
</body>
</html>
