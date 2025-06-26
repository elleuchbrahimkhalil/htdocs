from flask import Flask, request, jsonify
import json
import re
import ast
import subprocess
import tempfile
import os
from datetime import datetime
import logging

app = Flask(__name__)
logging.basicConfig(level=logging.INFO)

class SolutionAnalyzer:
    def __init__(self):
        self.difficulty_keywords = {
            'easy': ['print', 'input', 'if', 'for', 'while', 'basic'],
            'medium': ['function', 'class', 'recursion', 'algorithm', 'data structure'],
            'hard': ['optimization', 'complexity', 'advanced', 'dynamic programming', 'graph']
        }
    
    def analyze_difficulty(self, problem_description, solution_code):
        """Analyser la difficulté réelle du problème basée sur la solution"""
        try:
            # Compter les mots-clés de complexité
            text = (problem_description + " " + solution_code).lower()
            
            scores = {
                'easy': sum(1 for keyword in self.difficulty_keywords['easy'] if keyword in text),
                'medium': sum(1 for keyword in self.difficulty_keywords['medium'] if keyword in text),
                'hard': sum(1 for keyword in self.difficulty_keywords['hard'] if keyword in text)
            }
            
            # Analyser la complexité du code
            complexity_score = self._analyze_code_complexity(solution_code)
            
            if complexity_score > 15:
                return 'hard'
            elif complexity_score > 8:
                return 'medium'
            else:
                return 'easy'
                
        except Exception as e:
            logging.error(f"Erreur analyse difficulté: {e}")
            return 'unknown'
    
    def _analyze_code_complexity(self, code):
        """Analyser la complexité du code"""
        try:
            # Compter les structures de contrôle
            complexity = 0
            complexity += len(re.findall(r'\bif\b', code))
            complexity += len(re.findall(r'\bfor\b', code))
            complexity += len(re.findall(r'\bwhile\b', code))
            complexity += len(re.findall(r'\bdef\b', code)) * 2
            complexity += len(re.findall(r'\bclass\b', code)) * 3
            complexity += len(re.findall(r'\btry\b', code))
            complexity += len(re.findall(r'\bexcept\b', code))
            
            # Analyser la profondeur d'imbrication
            lines = code.split('\n')
            max_indent = 0
            for line in lines:
                if line.strip():
                    indent = len(line) - len(line.lstrip())
                    max_indent = max(max_indent, indent // 4)
            
            complexity += max_indent * 2
            
            return complexity
            
        except Exception as e:
            logging.error(f"Erreur analyse complexité: {e}")
            return 5  # Valeur par défaut
    
    def validate_output(self, solution_code, expected_output, language='python'):
        """Valider si le code produit l'output attendu"""
        try:
            if language.lower() == 'python':
                return self._test_python_code(solution_code, expected_output)
            else:
                # Pour d'autres langages, analyse statique pour l'instant
                return self._static_output_analysis(solution_code, expected_output)
                
        except Exception as e:
            logging.error(f"Erreur validation output: {e}")
            return {
                'valid': False,
                'error': str(e),
                'details': 'Erreur lors de l\'exécution du test'
            }
    
    def _test_python_code(self, code, expected_output):
        """Tester le code Python de manière sécurisée"""
        try:
            # Créer un fichier temporaire
            with tempfile.NamedTemporaryFile(mode='w', suffix='.py', delete=False) as f:
                f.write(code)
                temp_file = f.name
            
            try:
                # Exécuter le code avec timeout
                result = subprocess.run(
                    ['python', temp_file],
                    capture_output=True,
                    text=True,
                    timeout=10  # Timeout de 10 secondes
                )
                
                if result.returncode == 0:
                    actual_output = result.stdout.strip()
                    expected_clean = expected_output.strip() if expected_output else ""
                    
                    # Comparer les outputs
                    if actual_output == expected_clean:
                        return {
                            'valid': True,
                            'actual_output': actual_output,
                            'expected_output': expected_clean,
                            'match': True
                        }
                    else:
                        # Vérifier si c'est une correspondance partielle
                        similarity = self._calculate_similarity(actual_output, expected_clean)
                        return {
                            'valid': similarity > 0.8,
                            'actual_output': actual_output,
                            'expected_output': expected_clean,
                            'match': False,
                            'similarity': similarity
                        }
                else:
                    return {
                        'valid': False,
                        'error': result.stderr,
                        'details': 'Erreur d\'exécution du code'
                    }
                    
            finally:
                # Nettoyer le fichier temporaire
                os.unlink(temp_file)
                
        except subprocess.TimeoutExpired:
            return {
                'valid': False,
                'error': 'Timeout',
                'details': 'Le code a pris trop de temps à s\'exécuter'
            }
        except Exception as e:
            return {
                'valid': False,
                'error': str(e),
                'details': 'Erreur lors du test d\'exécution'
            }
    
    def _static_output_analysis(self, code, expected_output):
        """Analyse statique pour les langages non-Python"""
        try:
            # Rechercher les instructions print/console.log/cout
            print_patterns = [
                r'print\s*\(["\']([^"\']*)["\']',  # Python
                r'console\.log\s*\(["\']([^"\']*)["\']',  # JavaScript
                r'cout\s*<<\s*["\']([^"\']*)["\']',  # C++
                r'System\.out\.println\s*\(["\']([^"\']*)["\']'  # Java
            ]
            
            found_outputs = []
            for pattern in print_patterns:
                matches = re.findall(pattern, code, re.IGNORECASE)
                found_outputs.extend(matches)
            
            if not found_outputs:
                return {
                    'valid': False,
                    'details': 'Aucune instruction de sortie détectée',
                    'confidence': 0.3
                }
            
            # Comparer avec l'output attendu
            combined_output = ' '.join(found_outputs)
            similarity = self._calculate_similarity(combined_output, expected_output or "")
            
            return {
                'valid': similarity > 0.7,
                'found_outputs': found_outputs,
                'similarity': similarity,
                'confidence': 0.6  # Moins fiable que l'exécution réelle
            }
            
        except Exception as e:
            return {
                'valid': False,
                'error': str(e),
                'confidence': 0.1
            }
    
    def _calculate_similarity(self, text1, text2):
        """Calculer la similarité entre deux textes"""
        try:
            from difflib import SequenceMatcher
            return SequenceMatcher(None, text1.lower(), text2.lower()).ratio()
        except:
            # Fallback simple
            if text1.lower() == text2.lower():
                return 1.0
            elif text1.lower() in text2.lower() or text2.lower() in text1.lower():
                return 0.8
            else:
                return 0.0
    
    def detect_errors(self, solution_code, language='python'):
        """Détecter les erreurs potentielles dans le code"""
        errors = []
        warnings = []
        
        try:
            if language.lower() == 'python':
                errors, warnings = self._analyze_python_code(solution_code)
            elif language.lower() == 'javascript':
                errors, warnings = self._analyze_javascript_code(solution_code)
            else:
                errors, warnings = self._generic_code_analysis(solution_code)
            
            return {
                'errors': errors,
                'warnings': warnings,
                'error_count': len(errors),
                'warning_count': len(warnings)
            }
            
        except Exception as e:
            logging.error(f"Erreur détection d'erreurs: {e}")
            return {
                'errors': [f"Erreur d'analyse: {str(e)}"],
                'warnings': [],
                'error_count': 1,
                'warning_count': 0
            }
    
    def _analyze_python_code(self, code):
        """Analyser le code Python pour détecter les erreurs"""
        errors = []
        warnings = []
        
        try:
            # Vérifier la syntaxe
            ast.parse(code)
        except SyntaxError as e:
            errors.append(f"Erreur de syntaxe ligne {e.lineno}: {e.msg}")
        except Exception as e:
            errors.append(f"Erreur de parsing: {str(e)}")
        
        # Vérifications communes
        lines = code.split('\n')
        
        for i, line in enumerate(lines, 1):
            line_stripped = line.strip()
            
            # Vérifier les variables non définies (basique)
            if re.search(r'\b(undefined|null)\b', line_stripped):
                warnings.append(f"Ligne {i}: Utilisation de valeurs potentiellement non définies")
            
            # Vérifier les divisions par zéro potentielles
            if re.search(r'/\s*0\b', line_stripped):
                errors.append(f"Ligne {i}: Division par zéro détectée")
            
            # Vérifier les boucles infinies potentielles
            if re.search(r'while\s+True\s*:', line_stripped) and 'break' not in code:
                warnings.append(f"Ligne {i}: Boucle infinie potentielle détectée")
            
            # Vérifier l'indentation
            if line and not line_stripped and i < len(lines) - 1:
                continue
            if line and line[0] not in [' ', '\t'] and ':' in line and i < len(lines) - 1:
                next_line = lines[i] if i < len(lines) else ""
                if next_line.strip() and not next_line.startswith((' ', '\t')):
                    warnings.append(f"Ligne {i}: Problème d'indentation potentiel")
        
        return errors, warnings
    
    def _analyze_javascript_code(self, code):
        """Analyser le code JavaScript"""
        errors = []
        warnings = []
        
        # Vérifications basiques pour JavaScript
        if 'var ' in code and ('let ' in code or 'const ' in code):
            warnings.append("Mélange de var et let/const détecté")
        
        if re.search(r'==\s*null', code):
            warnings.append("Comparaison avec null, considérez === null")
        
        if re.search(r'function\s+\w+\s*\([^)]*\)\s*{[^}]*}', code):
            if 'return' not in code:
                warnings.append("Fonction sans instruction return détectée")
        
        return errors, warnings
    
    def _generic_code_analysis(self, code):
        """Analyse générique pour tous les langages"""
        errors = []
        warnings = []
        
        # Vérifications génériques
        if len(code.strip()) < 10:
            warnings.append("Code très court, solution potentiellement incomplète")
        
        if code.count('(') != code.count(')'):
            errors.append("Parenthèses non équilibrées")
        
        if code.count('{') != code.count('}'):
            errors.append("Accolades non équilibrées")
        
        if code.count('[') != code.count(']'):
            errors.append("Crochets non équilibrés")
        
        return errors, warnings
    
    def calculate_overall_score(self, difficulty_match, output_validation, error_analysis):
        """Calculer le score global de la solution"""
        try:
            score = 0
            
            # Score basé sur la validation de l'output (50% du score)
            if output_validation.get('valid', False):
                if output_validation.get('match', False):
                    score += 50  # Output parfait
                else:
                    # Score basé sur la similarité
                    similarity = output_validation.get('similarity', 0)
                    score += int(50 * similarity)
            
            # Score basé sur l'absence d'erreurs (30% du score)
            error_count = error_analysis.get('error_count', 0)
            warning_count = error_analysis.get('warning_count', 0)
            
            if error_count == 0:
                if warning_count == 0:
                    score += 30  # Aucune erreur ni avertissement
                elif warning_count <= 2:
                    score += 25  # Quelques avertissements
                else:
                    score += 15  # Beaucoup d'avertissements
            elif error_count <= 2:
                score += 10  # Quelques erreurs mineures
            # Sinon, pas de points pour cette section
            
            # Score basé sur la correspondance de difficulté (20% du score)
            if difficulty_match:
                score += 20
            else:
                score += 10  # Partiellement correct
            
            return min(100, max(0, score))  # S'assurer que le score est entre 0 et 100
            
        except Exception as e:
            logging.error(f"Erreur calcul score: {e}")
            return 0

# Instance globale de l'analyseur
analyzer = SolutionAnalyzer()

@app.route('/analyze-solution', methods=['POST'])
def analyze_solution():
    """Endpoint principal pour analyser une solution"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({
                'success': False,
                'error': 'Aucune donnée reçue'
            }), 400
        
        # Extraire les données
        problem = data.get('problem', {})
        solution = data.get('solution', {})
        
        problem_title = problem.get('title', '')
        problem_description = problem.get('description', '')
        problem_difficulty = problem.get('difficulty', 'medium')
        expected_output = problem.get('expected_output', '')
        language = problem.get('language', 'python')
        
        solution_code = solution.get('code', '')
        solution_explanation = solution.get('explanation', '')
        developer = solution.get('developer', 'unknown')
        
        if not solution_code:
            return jsonify({
                'success': False,
                'error': 'Code de solution manquant'
            }), 400
        
        # Analyser la difficulté
        detected_difficulty = analyzer.analyze_difficulty(problem_description, solution_code)
        difficulty_match = detected_difficulty == problem_difficulty
        
        # Valider l'output
        output_validation = analyzer.validate_output(solution_code, expected_output, language)
        
        # Détecter les erreurs
        error_analysis = analyzer.detect_errors(solution_code, language)
        
        # Calculer le score global
        overall_score = analyzer.calculate_overall_score(
            difficulty_match, 
            output_validation, 
            error_analysis
        )
        
        # Générer un feedback IA
        ai_feedback = generate_ai_feedback(
            overall_score, 
            difficulty_match, 
            output_validation, 
            error_analysis,
            detected_difficulty,
            problem_difficulty
        )
        
        # Préparer la réponse
        result = {
            'success': True,
            'solution_id': data.get('solution_id'),
            'ai_score': overall_score,
            'difficulty_check': detected_difficulty,
            'difficulty_match': difficulty_match,
            'output_validation': 'valid' if output_validation.get('valid', False) else 'invalid',
            'error_detection': 'errors' if error_analysis.get('error_count', 0) > 0 else 'clean',
            'ai_feedback': ai_feedback,
            'details': {
                'difficulty_analysis': {
                    'detected': detected_difficulty,
                    'expected': problem_difficulty,
                    'match': difficulty_match
                },
                'output_analysis': output_validation,
                'error_analysis': error_analysis,
                'code_metrics': {
                    'lines_of_code': len(solution_code.split('\n')),
                    'complexity_score': analyzer._analyze_code_complexity(solution_code),
                    'language': language
                }
            },
            'timestamp': datetime.now().isoformat()
        }
        
        logging.info(f"Solution {data.get('solution_id')} analysée - Score: {overall_score}")
        
        return jsonify(result)
        
    except Exception as e:
        logging.error(f"Erreur lors de l'analyse: {str(e)}")
        return jsonify({
            'success': False,
            'error': f'Erreur interne: {str(e)}'
        }), 500

def generate_ai_feedback(score, difficulty_match, output_validation, error_analysis, detected_difficulty, expected_difficulty):
    """Générer un feedback intelligent basé sur l'analyse"""
    feedback_parts = []
    
    # Feedback sur le score global
    if score >= 90:
        feedback_parts.append("🎉 Excellente solution ! Votre code est de très haute qualité.")
    elif score >= 80:
        feedback_parts.append("✅ Très bonne solution avec quelques points d'amélioration mineurs.")
    elif score >= 70:
        feedback_parts.append("👍 Bonne solution, mais il y a quelques aspects à améliorer.")
    elif score >= 60:
        feedback_parts.append("⚠️ Solution acceptable mais nécessite des améliorations significatives.")
    else:
        feedback_parts.append("❌ Solution nécessitant des corrections importantes.")
    
    # Feedback sur la difficulté
    if not difficulty_match:
        if detected_difficulty == 'easy' and expected_difficulty in ['medium', 'hard']:
            feedback_parts.append(f"💡 Votre solution semble plus simple que prévu. Considérez une approche plus sophistiquée pour un problème {expected_difficulty}.")
        elif detected_difficulty == 'hard' and expected_difficulty in ['easy', 'medium']:
            feedback_parts.append(f"🔧 Votre solution semble complexe pour un problème {expected_difficulty}. Une approche plus simple pourrait être plus appropriée.")
    
    # Feedback sur l'output
    if not output_validation.get('valid', False):
        if 'error' in output_validation:
            feedback_parts.append(f"🐛 Erreur d'exécution: {output_validation['error']}")
        else:
            feedback_parts.append("📤 L'output de votre solution ne correspond pas exactement à ce qui est attendu.")
    
    # Feedback sur les erreurs
    error_count = error_analysis.get('error_count', 0)
    warning_count = error_analysis.get('warning_count', 0)
    
    if error_count > 0:
        feedback_parts.append(f"🚨 {error_count} erreur(s) détectée(s) dans votre code.")
    
    if warning_count > 0:
        feedback_parts.append(f"⚠️ {warning_count} avertissement(s) - considérez ces améliorations.")
    
    if error_count == 0 and warning_count == 0:
        feedback_parts.append("✨ Aucune erreur détectée dans votre code !")
    
    return " ".join(feedback_parts)

@app.route('/health', methods=['GET'])
def health_check():
    """Endpoint de vérification de santé"""
    return jsonify({
        'status': 'healthy',
        'service': 'AI Solution Validator',
        'timestamp': datetime.now().isoformat(),
        'version': '1.0.0'
    })

@app.route('/test', methods=['POST'])
def test_analysis():
    """Endpoint de test pour vérifier le fonctionnement"""
    test_data = {
        'solution_id': 'test_001',
        'problem': {
            'title': 'Test Problem',
            'description': 'Simple test problem',
            'difficulty': 'easy',
            'expected_output': 'Hello World',
            'language': 'python'
        },
        'solution': {
            'code': 'print("Hello World")',
            'explanation': 'Simple print statement',
            'developer': 'test_user'
        }
    }
    
    # Utiliser les données de test ou celles fournies
    data = request.get_json() or test_data
    
    # Rediriger vers l'analyse normale
    return analyze_solution_internal(data)

def analyze_solution_internal(data):
    """Version interne de l'analyse (pour éviter la duplication de code)"""
    try:
        problem = data.get('problem', {})
        solution = data.get('solution', {})
        
        solution_code = solution.get('code', '')
        if not solution_code:
            return jsonify({
                'success': False,
                'error': 'Code de solution manquant'
            }), 400
        
        # Effectuer l'analyse complète
        detected_difficulty = analyzer.analyze_difficulty(
            problem.get('description', ''), 
            solution_code
        )
        
        difficulty_match = detected_difficulty == problem.get('difficulty', 'medium')
        
        output_validation = analyzer.validate_output(
            solution_code, 
            problem.get('expected_output', ''), 
            problem.get('language', 'python')
        )
        
        error_analysis = analyzer.detect_errors(
            solution_code, 
            problem.get('language', 'python')
        )
        
        overall_score = analyzer.calculate_overall_score(
            difficulty_match, 
            output_validation, 
            error_analysis
        )
        
        ai_feedback = generate_ai_feedback(
            overall_score, 
            difficulty_match, 
            output_validation, 
            error_analysis,
            detected_difficulty,
            problem.get('difficulty', 'medium')
        )
        
        return jsonify({
            'success': True,
            'solution_id': data.get('solution_id'),
            'ai_score': overall_score,
            'difficulty_check': detected_difficulty,
            'difficulty_match': difficulty_match,
            'output_validation': 'valid' if output_validation.get('valid', False) else 'invalid',
            'error_detection': 'errors' if error_analysis.get('error_count', 0) > 0 else 'clean',
            'ai_feedback': ai_feedback,
            'details': {
                'difficulty_analysis': {
                    'detected': detected_difficulty,
                    'expected': problem.get('difficulty', 'medium'),
                    'match': difficulty_match
                },
                'output_analysis': output_validation,
                'error_analysis': error_analysis,
                'timestamp': datetime.now().isoformat()
            }
        })
        
    except Exception as e:
        logging.error(f"Erreur analyse interne: {str(e)}")
        return jsonify({
            'success': False,
            'error': f'Erreur interne: {str(e)}'
        }), 500

@app.route('/batch-analyze', methods=['POST'])
def batch_analyze():
    """Analyser plusieurs solutions en lot"""
    try:
        data = request.get_json()
        solutions = data.get('solutions', [])
        
        if not solutions:
            return jsonify({
                'success': False,
                'error': 'Aucune solution à analyser'
            }), 400
        
        results = []
        
        for solution_data in solutions:
            try:
                # Analyser chaque solution
                result = analyze_solution_internal(solution_data)
                if hasattr(result, 'get_json'):
                    result_data = result.get_json()
                else:
                    result_data = result
                
                results.append({
                    'solution_id': solution_data.get('solution_id'),
                    'result': result_data
                })
                
            except Exception as e:
                results.append({
                    'solution_id': solution_data.get('solution_id'),
                    'result': {
                        'success': False,
                        'error': str(e)
                    }
                })
        
        return jsonify({
            'success': True,
            'total_analyzed': len(results),
            'results': results,
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        logging.error(f"Erreur analyse en lot: {str(e)}")
        return jsonify({
            'success': False,
            'error': f'Erreur lors de l\'analyse en lot: {str(e)}'
        }), 500

@app.route('/stats', methods=['GET'])
def get_stats():
    """Obtenir les statistiques de l'API"""
    # En production, ces stats seraient stockées en base
    return jsonify({
        'success': True,
        'stats': {
            'total_analyses': 0,  # À implémenter avec une vraie base de données
            'average_score': 0,
            'success_rate': 0,
            'uptime': '100%',
            'last_analysis': datetime.now().isoformat()
        },
        'service_info': {
            'version': '1.0.0',
            'status': 'operational',
            'supported_languages': ['python', 'javascript', 'java', 'cpp'],
            'features': [
                'difficulty_analysis',
                'output_validation',
                'error_detection',
                'code_complexity_analysis'
            ]
        }
    })

@app.errorhandler(404)
def not_found(error):
    return jsonify({
        'success': False,
        'error': 'Endpoint non trouvé',
        'available_endpoints': [
            '/analyze-solution',
            '/batch-analyze',
            '/test',
            '/health',
            '/stats'
        ]
    }), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({
        'success': False,
        'error': 'Erreur interne du serveur',
        'message': 'Veuillez réessayer plus tard'
    }), 500

if __name__ == '__main__':
    print("🤖 Démarrage de l'API IA pour la validation des solutions...")
    print("📡 Endpoints disponibles:")
    print("   POST /analyze-solution - Analyser une solution")
    print("   POST /batch-analyze - Analyser plusieurs solutions")
    print("   POST /test - Tester l'API")
    print("   GET /health - Vérifier la santé du service")
    print("   GET /stats - Obtenir les statistiques")
    print("🚀 Serveur démarré sur http://localhost:5000")
    
    app.run(debug=True, host='0.0.0.0', port=5000)
