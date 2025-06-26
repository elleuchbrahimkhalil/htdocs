# 🤖 Intégration IA - CodeChallenge

## Vue d'ensemble

L'intégration IA permet d'analyser automatiquement les solutions soumises par les utilisateurs et de les valider ou rejeter selon des critères prédéfinis.

## 🚀 Installation rapide

### 1. Base de données SQL Server

```bash
# Exécuter le script SQL
sqlcmd -S localhost -d votre_base -i ai_tables_sqlserver.sql
```

### 2. Configuration PHP

```php
// Dans votre fichier de configuration
require_once 'ai_config.php';
require_once 'ai_solution_validator_sqlserver.php';
```

### 3. Test de l'installation

```bash
php test_ai_basic.php
```

## 📋 Fonctionnalités

### ✅ Validation automatique
- Analyse du code source
- Vérification de la logique
- Détection d'erreurs communes
- Score de qualité (0-100)

### 🎯 Actions automatiques
- **Score ≥ 90** : Approbation automatique
- **Score 30-89** : Révision humaine requise  
- **Score < 30** : Rejet automatique

### 📊 Statistiques et monitoring
- Nombre de solutions analysées
- Taux de réussite IA
- Temps d'analyse moyen
- Logs détaillés

## 🔧 Configuration

### Variables principales

```php
// ai_config.php
define('AI_ENABLED', true);
define('AI_API_URL', 'http://localhost:5000');
define('AI_AUTO_APPROVE_THRESHOLD', 90);
define('AI_AUTO_REJECT_THRESHOLD', 30);
```

### Base de données

Les tables suivantes sont créées automatiquement :
- `ai_analysis` : Résultats des analyses
- `ai_config` : Configuration dynamique
- `ai_logs` : Logs du système
- Colonnes ajoutées à `solutions` : `ai_status`, `ai_data`

## 🔄 Intégration dans le workflow

### 1. Soumission de solution

```php
// process_solution.php
if (isAIValidationEnabled()) {
    $result = analyzeNewSolution($solution_id);
    // Traitement automatique selon le score
}
```

### 2. Statuts IA possibles

- `pending` : En attente d'analyse
- `ai_testing` : Analyse en cours
- `ai_passed` : Validé par l'IA
- `ai_failed` : Rejeté par l'IA
- `ai_review` : Révision humaine requise
- `ai_error` : Erreur lors de l'analyse

### 3. Messages utilisateur

```php
$messages = [
    'ai_passed' => '🎉 Solution validée automatiquement !',
    'ai_review' => '🔍 Révision humaine en cours...',
    'ai_failed' => '⚠️ Solution à améliorer'
];
```

## 📈 Monitoring

### Vérification du statut

```php
$validator = new AISolutionValidator();
$stats = $validator->getAIStats();

echo "Solutions analysées : " . $stats['total_analyzed'];
echo "Taux de réussite : " . $stats['success_rate'] . "%";
```

### Logs système

```bash
# Voir les logs en temps réel
tail -f ai_system.log

# Analyser les erreurs
grep "ERROR" ai_system.log
```

## 🛠️ Dépannage

### Problèmes courants

#### ❌ API Flask inaccessible
```bash
# Vérifier si le service est démarré
curl http://localhost:5000/health

# Démarrer le service IA
cd ai_service
python app.py
```

#### ❌ Erreurs de base de données
```sql
-- Vérifier les tables IA
SELECT COUNT(*) FROM ai_analysis;
SELECT COUNT(*) FROM ai_config;
```

#### ❌ Solutions non analysées
```php
// Forcer l'analyse d'une solution
$validator = new AISolutionValidator();
$result = $validator->analyzeSolution($solution_id);
```

### Désactiver temporairement l'IA

```php
// ai_config.php
define('AI_ENABLED', false);
```

## 🔒 Sécurité

### Bonnes pratiques

1. **Validation des entrées** : Toutes les données sont nettoyées avant analyse
2. **Timeout** : Limite de 60s par analyse pour éviter les blocages
3. **Logs** : Traçabilité complète des actions IA
4. **Fallback** : En cas d'erreur IA, le processus manuel reste disponible

### Permissions

- Seuls les administrateurs peuvent modifier la configuration IA
- Les utilisateurs voient uniquement le résultat final
- Les logs détaillés sont protégés

## 📚 API Reference

### AISolutionValidator

```php
// Analyser une solution
$result = $validator->analyzeSolution($solution_id);

// Obtenir les statistiques
$stats = $validator->getAIStats();

// Nettoyer les anciennes données
$validator->cleanupOldData(30); // 30 jours

// Validation manuelle
$validator->validateSolutionManually($solution_id, $feedback, true);
```

### Fonctions utilitaires

```php
// Vérifier si l'IA est activée
if (isAIValidationEnabled()) { ... }

// Analyser une nouvelle solution
$result = analyzeNewSolution($solution_id);

// Configuration
$value = getAIConfig('timeout', 60);
```

## 🚀 Évolutions futures

### Prochaines fonctionnalités
- [ ] Analyse de performance du code
- [ ] Détection de plagiat
- [ ] Suggestions d'amélioration
- [ ] Interface de monitoring avancée
- [ ] API REST pour intégrations externes

### Optimisations prévues
- [ ] Cache des analyses
- [ ] Analyse en parallèle
- [ ] Machine learning adaptatif
- [ ] Intégration avec d'autres outils

## 📞 Support

### En cas de problème

1. **Vérifier les logs** : `ai_system.log`
2. **Tester la configuration** : `php test_ai_basic.php`
3. **Consulter les statistiques** : Table `ai_analysis`
4. **Contacter l'équipe technique**

### Ressources utiles

- Documentation technique : `/docs/ai/`
- Exemples de code : `/examples/ai/`
- Tests unitaires : `/tests/ai/`
- Configuration avancée : `ai_config_advanced.php`

---

*Dernière mise à jour : Décembre 2024*
*Version : 1.0.0*
```

Et pour finir, créons un script d'installation automatique :

```bash:install_ai.sh
#!/bin/bash

echo "🤖 Installation de l'IA CodeChallenge"
echo "====================================="
echo ""

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Vérifications préalables
log_info "Vérification des prérequis..."

# Vérifier PHP
if ! command -v php &> /dev/null; then
    log_error "PHP n'est pas installé"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
log_success "PHP $PHP_VERSION détecté"

# Vérifier les extensions PHP requises
REQUIRED_EXTENSIONS=("pdo" "json" "curl")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "$ext"; then
        log_success "Extension PHP $ext : OK"
    else
        log_error "Extension PHP $ext manquante"
        exit 1
    fi
done

# Vérifier SQL Server (optionnel)
log_info "Vérification de SQL Server..."
if command -v sqlcmd &> /dev/null; then
    log_success "SQL Server CLI disponible"
    SQLSERVER_AVAILABLE=true
else
    log_warning "SQL Server CLI non trouvé - installation manuelle requise"
    SQLSERVER_AVAILABLE=false
fi

# Créer les répertoires nécessaires
log_info "Création des répertoires..."

mkdir -p logs
mkdir -p ai_service
mkdir -p config
mkdir -p tests

log_success "Répertoires créés"

# Copier les fichiers de configuration
log_info "Configuration des fichiers..."

# Créer le fichier de configuration personnalisé
cat > config/ai_local.php << 'EOF'
<?php
/**
 * Configuration IA locale - À personnaliser
 */

// Configuration base de données SQL Server
define('AI_DB_SERVER', 'localhost');
define('AI_DB_NAME', 'votre_base_de_donnees');
define('AI_DB_USER', 'votre_utilisateur');
define('AI_DB_PASS', 'votre_mot_de_passe');

// Configuration API Flask
define('AI_FLASK_HOST', 'localhost');
define('AI_FLASK_PORT', 5000);

// Paramètres de performance
define('AI_MAX_CONCURRENT_ANALYSES', 5);
define('AI_ANALYSIS_TIMEOUT', 60);

// Seuils de validation
define('AI_EXCELLENT_THRESHOLD', 90);
define('AI_GOOD_THRESHOLD', 70);
define('AI_POOR_THRESHOLD', 30);

// Logs
define('AI_LOG_LEVEL', 'INFO');
define('AI_LOG_ROTATION', true);
define('AI_LOG_MAX_FILES', 5);
?>
EOF

log_success "Fichier de configuration créé : config/ai_local.php"

# Installation de la base de données
if [ "$SQLSERVER_AVAILABLE" = true ]; then
    log_info "Installation des tables SQL Server..."
    
    read -p "Voulez-vous installer les tables IA maintenant ? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        read -p "Serveur SQL Server : " SQL_SERVER
        read -p "Base de données : " SQL_DATABASE
        read -p "Utilisateur : " SQL_USER
        read -s -p "Mot de passe : " SQL_PASS
        echo
        
        if sqlcmd -S "$SQL_SERVER" -d "$SQL_DATABASE" -U "$SQL_USER" -P "$SQL_PASS" -i ai_tables_sqlserver.sql; then
            log_success "Tables IA installées avec succès"
        else
            log_error "Erreur lors de l'installation des tables"
        fi
    else
        log_warning "Installation des tables reportée - voir README_AI.md"
    fi
fi

# Test de l'installation
log_info "Test de l'installation..."

if php test_ai_basic.php > logs/install_test.log 2>&1; then
    log_success "Tests de base réussis"
else
    log_warning "Certains tests ont échoué - voir logs/install_test.log"
fi

# Permissions des fichiers
log_info "Configuration des permissions..."

chmod 755 ai_solution_validator_sqlserver.php
chmod 755 ai_config.php
chmod 755 test_ai_basic.php
chmod 777 logs/

log_success "Permissions configurées"

# Service IA Flask (optionnel)
log_info "Configuration du service IA Flask..."

cat > ai_service/requirements.txt << 'EOF'
Flask==2.3.3
requests==2.31.0
numpy==1.24.3
scikit-learn==1.3.0
python-dotenv==1.0.0
gunicorn==21.2.0
EOF

cat > ai_service/app.py << 'EOF