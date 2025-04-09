<!DOCTYPE html>
<html>
<body>

<form action="etudiant.php" method="POST">
 <fieldset style="background-image: url('https://media3.giphy.com/media/v1.Y2lkPTc5MGI3NjExbzZndTU2OXdheGRxYXU2eDVybTc1dHVwaHZia3pycWMxcjltc3p3bCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/7AtHoQ9XWbpwLRxs0t/giphy.gif');">
    <table>
        <tr>
            <td>
                <label for="fname" style="color: red;">prenom:</label>
            </td>
            <td>
                <input type="text" id="fname" name="fname">
            </td>
        </tr>
        <tr>
            <td>
                <label for="mdp"style="color: red;">mot de passe:</label>
            </td>
            <td>
                <input type="password" id="mdp" name="mdp">
            </td>
        </tr>
        <tr>
            <td>
                <label for="association"style="color: red;">Association:</label>
            </td>
            <td>    
                <select name="association" id="asoc">
                    <option value="spor">sportif</option>
                    <option value="social">social</option>
                    <option value="letre">literal</option>
                    <option value="economie">economique</option>
                </select>
            </td>
        </tr>

        <tr>
            <td>
                <label for="exactday" style="color: red;"><?php echo "la jour pour la semaine $sjour"; ?></label>
            </td>
            <td>
                <input type="checkbox" id="exactday_lundi" name="exactday_lundi">
                <label for="exactday_lundi"style="color: red;">lundi</label>
                <input type="checkbox" id="exactday_mardi" name="exactday_mardi">
                <label for="exactday_mardi"style="color: red;">mardi</label>
                <input type="checkbox" id="exactday_jeudi" name="exactday_jeudi">
                <label for="exactday_jeudi"style="color: red;">jeudi</label>
            </td>
        </tr>
    </table>
    <br>
    <input type="submit" value="Submit">
 </fieldset>
</form>

<?php
$sjour = date('m/d/Y');
$prenom = isset($_POST['fname']) ? $_POST['fname'] : '';
$mdp = isset($_POST['mdp']) ? $_POST['mdp'] : '';
$association = isset($_POST['association']) ? $_POST['association'] : '';
$exactday_lundi = isset($_POST['exactday_lundi']) ? $_POST['exactday_lundi'] : '';
$exactday_mardi = isset($_POST['exactday_mardi']) ? $_POST['exactday_mardi'] : '';
$exactday_jeudi = isset($_POST['exactday_jeudi']) ? $_POST['exactday_jeudi'] : '';

// Vérification des champs obligatoires
if (empty($prenom) || empty($mdp) || empty($association) || (empty($exactday_lundi) && empty($exactday_mardi) && empty($exactday_jeudi))) {
    echo "Erreur : Veuillez remplir tous les champs obligatoires.";
} else {
    // Affichage des informations
    echo "Informations du formulaire : <br>";
    echo "Prénom : $prenom <br>";
    echo "Mot de passe : $mdp <br>";
    echo "Association : $association <br>";
    echo "Jours sélectionnés : ";
    if (!empty($exactday_lundi)) {
        echo "Lundi ";
    }
    if (!empty($exactday_mardi)) {
        echo "Mardi ";
    }
    if (!empty($exactday_jeudi)) {
        echo "Jeudi ";
    }
}
?>

</body>
</html>
