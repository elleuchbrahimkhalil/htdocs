<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Gestion des Notes</title>
    <style>
        form {
            margin: 20px;
            padding: 15px;
        }
        label {
            display: inline-block;
            width: 120px;
        }
        input[type="number"] {
            width: 60px;
            margin: 2px;
        }
        table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .moyenne-generale {
            font-weight: bold;
            background-color: #2c3e50 !important;
            color: white;
        }
    </style>
</head>
<body>
    <form action="activite.php" method="post">
        <label for="nom">Nom de l'étudiant:</label>
        <input type="text" id="nom" name="nom" required><br><br>
        
        <label for="html">HTML:</label>
        <input type="number" id="html1" name="html[]" min="0" max="20" required>
        <input type="number" id="html2" name="html[]" min="0" max="20" required>
        <input type="number" id="html3" name="html[]" min="0" max="20" required><br><br>
        
        <label for="css">CSS:</label>
        <input type="number" id="css1" name="css[]" min="0" max="20" required>
        <input type="number" id="css2" name="css[]" min="0" max="20" required>
        <input type="number" id="css3" name="css[]" min="0" max="20" required><br><br>
        
        <label for="js">JavaScript:</label>
        <input type="number" id="js1" name="js[]" min="0" max="20" required>
        <input type="number" id="js2" name="js[]" min="0" max="20" required>
        <input type="number" id="js3" name="js[]" min="0" max="20" required><br><br>
        
        <input type="submit" value="Envoyer">
    </form>

    <?php
    session_start();

    if (!isset($_SESSION['etudiants'])) {
        $_SESSION['etudiants'] = array();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nom = htmlspecialchars($_POST["nom"]);
        $notes = array(
            "html" => $_POST["html"],
            "css" => $_POST["css"],
            "js" => $_POST["js"]
        );

        // Add new student to session
        $_SESSION['etudiants'][$nom] = $notes;

        // Display table header
        echo "<table>";
        echo "<tr><th>Nom</th><th>Matière</th><th>Note 1</th><th>Note 2</th><th>Note 3</th><th>Moyenne</th></tr>";

        // Display all students
        foreach ($_SESSION['etudiants'] as $etudiant_nom => $matieres) {
            $moyennes_etudiant = array();
            
            foreach ($matieres as $matiere => $notes) {
                $moyenne = array_sum($notes) / count($notes);
                $moyennes_etudiant[] = $moyenne;
                
                echo "<tr>";
                echo "<td>" . $etudiant_nom . "</td>";
                echo "<td>" . strtoupper($matiere) . "</td>";
                echo "<td>" . $notes[0] . "</td>";
                echo "<td>" . $notes[1] . "</td>";
                echo "<td>" . $notes[2] . "</td>";
                echo "<td>" . number_format($moyenne, 2) . "</td>";
                echo "</tr>";
            }
            
            $moyenne_generale = array_sum($moyennes_etudiant) / count($moyennes_etudiant);
            echo "<tr class='moyenne-generale'>";
            echo "<td>" . $etudiant_nom . "</td>";
            echo "<td>MOYENNE GÉNÉRALE</td>";
            echo "<td colspan='3'></td>";
            echo "<td>" . number_format($moyenne_generale, 2) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    ?>
</body>
</html>