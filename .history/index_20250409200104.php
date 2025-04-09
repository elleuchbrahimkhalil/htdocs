<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Site Mixte</title>
    <link rel="stylesheet" href="common.css">
    <style>
        iframe {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 20px 0;
        }
        header, footer {
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <header>
        <h1>Application Web</h1>
        <p>Partie statique hébergée sur GitHub Pages</p>
    </header>
    
    <main class="container">
        <section>
            <h2>Contenu dynamique</h2>
<iframe src="http://<?php echo $_SERVER['HTTP_HOST']; ?>/exacueil.php" 
                    style="width:100%; height:600px"
                    title="Partie dynamique"></iframe>
        </section>
    </main>

    <footer>
        <p>© 2024 - Contenu dynamique hébergé sur votre serveur PHP</p>
    </footer>
</body>
</html>
