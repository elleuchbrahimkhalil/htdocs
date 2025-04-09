<?php
require("1.5.html");
?>
<!DOCTYPE html>
<html><body>
<h1>Affichage des données saisies</h1>
<ul>
 <li>Nom: <?php print $_REQUEST['nom'] ?></li>
 <li>Prenom: <?php print $_REQUEST['prenom'] ?></li>
 <li>Sexe: <?php print $_REQUEST['sexe'] ?></li>
 <li>Vins:
 <ul>
 <?php
 if (isset($_REQUEST['vin']))
 foreach($_REQUEST['vin'] as $v) print "<li>$v</li>";
 ?>
 </ul>
</ul>
<a href="javascript:history.back()">Essayez à nouveau</a>
</body>
</html> 