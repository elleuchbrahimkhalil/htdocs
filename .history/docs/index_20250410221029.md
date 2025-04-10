---
layout: default
title: "Mon Site"
description: "Site statique avec intégration PHP"
---

# Bienvenue

Ce site combine :
- GitHub Pages pour le contenu statique
- Un serveur PHP séparé pour les fonctionnalités dynamiques

{% if jekyll.environment == "production" %}
<a href="/exacueil.php" class="btn">Accéder à l'application</a>
{% endif %}
