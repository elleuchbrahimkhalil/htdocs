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
        """Analyse statique