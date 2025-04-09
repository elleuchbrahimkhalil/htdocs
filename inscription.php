<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription au Repas de l'Association</title>
    <style>
        .orange-section {
            background-color: #FFA500;
            padding: 20px;
            margin: 20px 0;
        }
        form {
            width: 500px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        input[type="checkbox"] {
            margin-right: 5px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
        }
        .disponibilites {
            margin-bottom: 10px;
        }

        form {
            width: 500px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        input[type="checkbox"] {
            margin-right: 5px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
        }
        .disponibilites {
            margin-bottom: 10px;
        }
    
    </style>
</head>
<body>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données (à sécuriser impérativement !)
    $nom = htmlspecialchars($_POST["nom"]);
    $email = htmlspecialchars($_POST["email"]);
    $disponibilites = isset($_POST["disponibilites"]) ? $_POST["disponibilites"] : []; // Vérification si le champ existe
    $plat = htmlspecialchars($_POST["plat"]);

    // Affichage des données pour vérification (à adapter pour un usage réel)
    echo "<h2>Récapitulatif de votre inscription</h2>";
    echo "<p>Nom : " . $nom . "</p>";
    echo "<p>Email : " . $email . "</p>";
    echo "<p>Disponibilités :</p><ul>";
    foreach ($disponibilites as $date) {
        echo "<li>" . $date . "</li>";
    }
    echo "</ul>";
    echo "<p>Type de plat : " . $plat . "</p>";


        // Affichage des données pour vérification (à adapter pour un usage réel)
        echo "<h2>Récapitulatif de votre inscription</h2>";
        echo "<p>Nom : " . $nom . "</p>";
        echo "<p>Email : " . $email . "</p>";
        echo "<p>Disponibilités :</p><ul>";
        foreach ($disponibilites as $date) {
            echo "<li>" . $date . "</li>";
        }
        echo "</ul>";
        echo "<p>Type de plat : " . $plat . "</p>";

    // Ici, vous pourrez ajouter le code pour enregistrer les données 
    // dans une base de données, envoyer un email de confirmation, etc.

    // Handle file upload
    if(isset($_FILES['fileUpload'])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["fileUpload"]["name"]);
        
        if (move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $target_file)) {
            echo "<p>Le fichier ". basename( $_FILES["fileUpload"]["name"]). " a été uploadé.</p>";
        }
    }
}
?>

    <div class="orange-section">
    <form action="inscription.php" method="post" enctype="multipart/form-data">

            <h2>Inscription au Repas de l'Association</h2>
        <label for="nom">Nom :</label>
        <input type="text" id="nom" name="nom" required>

        <label for="password">Mot de passe :</label>

        <input type="password" id="password" name="password" required>

        <div class="disponibilites">
            <br><label>Disponibilités pour la semaine <?php echo date('w')    ?>  : </label><br>
            <?php
            // Dates proposées (vous pouvez les adapter)
            $dates = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche"];
            foreach ($dates as $date) {
                echo "<input type='checkbox' name='disponibilites[]' value='$date'> $date";
            }


            ?>
        </div>

        <label for="plat">Type de Plat que vous préparerez :</label>
        <select id="plat" name="plat">
            <option value="entree">Entrée</option>
            <option value="plat_principal">Plat Principal</option>
            <option value="dessert">Dessert</option>
            <option value="boisson">Boisson</option>
        </select>

        <input type="submit" value="S'inscrire">
        <label for="fileUpload">Joindre un fichier :</label>
        <input type="file" id="fileUpload" name="fileUpload" required>


    </form>

</div>
</body>
</html>
