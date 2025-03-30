<!-- info.blade.php -->

<form method="post" action="{{ url('/info/store') }}">

    <div>
        <label for="nom">Nom:</label>
        <input type="text" id="nom" name="nom">
    </div>
    <div>
        <label for="prenom">Prénom:</label>
        <input type="text" id="prenom" name="prenom">
    </div>
    <div>
        <label for="age">Âge:</label>
        <input type="text" id="age" name="age">
    </div>
    <div>
        <label for="sexe">Sexe:</label>
        <select id="sexe" name="sexe">
            <option value="homme">Homme</option>
            <option value="femme">Femme</option>
        </select>
    </div>
    <div>
        <label>Loisirs:</label>
        <div>
            <input type="checkbox" id="football" name="loisirs[]" value="football">
            <label for="football">Football</label>
        </div>
        <div>
            <input type="checkbox" id="basketball" name="loisirs[]" value="basketball">
            <label for="basketball">Basketball</label>
        </div>
        <div>
            <input type="checkbox" id="lecture" name="loisirs[]" value="lecture">
            <label for="lecture">Lecture</label>
        </div>
        <!-- Ajoutez d'autres loisirs ici si nécessaire -->
    </div>
    <button type="submit">Soumettre</button>
</form>
