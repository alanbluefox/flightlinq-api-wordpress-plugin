# Changelog

Entries before 1.6.0 are retained in French as historical release notes.

## 1.8.2 - 2026-07-05

- Reworked the public README for distribution.
- Expanded shortcode, REST API, route mapping, security, and PHP helper documentation.
- Cleaned up the changelog structure and removed duplicated release notes.
- Applied caching consistently across all public shortcodes.
- Corrected the cache enable/disable setting so an unchecked value is saved.
- Partitioned cached responses by FlightLinq API key to prevent stale cross-account data.
- Constrained cache duration and rejected secret Mapbox tokens before storage.
- Aligned the plugin header and runtime version constant with this release.

## 1.8.1 - 2026-07-05

- Moved the historical release notes from README.md to CHANGELOG.md.
- Simplified the README changelog section in preparation for public distribution.

## 1.8.0 - 2026-07-05

- Prepared the plugin structure for internationalization.
- Verified the `flightlinq-api` text domain and language directory setup.
- Reviewed visible PHP strings for compatibility with WordPress translation functions.
- Externalized route-map JavaScript strings in preparation for translation files.

## 1.7.4 - 2026-07-05

- Consolidated shortcode rendering and public-facing fallback behavior.
- Preserved routes without map coordinates in route table mode.
- Harmonized admin copy for public distribution.
- Improved dark-theme consistency for route cards and tables.

## 1.7.3 - 2026-07-05

- Simplified the FlightLinq Shortcodes admin page.
- Added collapsible sections for advanced examples and shortcode attributes.
- Improved the readability of shortcode documentation cards.

## 1.7.2 - 2026-07-05

- Improved the responsive layout of the shortcode documentation page.
- Simplified the compact table used by `[flightlinq_routes_map layout="table"]`.
- Added the optional `table_view="full"` detailed route table.
- Corrected excessive widths in examples and route tables.

## 1.7.1 - 2026-07-05

- Corrected the route-table rendering of `[flightlinq_routes_map]`, including dark-theme output.
- Reduced excessive row height and improved contrast.
- Updated frontend asset versions.

## 1.7.0 - 2026-07-05

- Added advanced shortcodes for fleet data, routes by hub, and route maps.
- Added Leaflet map output to `[flightlinq_routes_map]`, with OpenStreetMap by default and optional Mapbox tiles.
- Added admin settings for route mapping, provider selection, Mapbox configuration, and default map height.
- Added advanced filters, sorting, and limits for routes and pilot leaderboards.
- Improved responsive frontend shortcode rendering.
- Refined route maps with lightweight markers, route lines, popups, and a dedicated information toolbar.
- Corrected recent-flight card layouts, Leaflet sizing, and dark-theme readability.
- Expanded admin documentation for advanced shortcodes and route-map configuration.

## 1.6.0 - 2026-07-04

- Added an admin setting to enable or disable the public REST API while preserving the previous enabled-by-default behavior.
- Enforced the public REST setting across `flightlinq/v1`, returning HTTP 403 when disabled.
- Filtered public REST responses to supported external fields and hid technical FlightLinq errors.
- Added strict public route limits and stabilized REST request validation.
- Stabilized existing shortcodes with normalized collections and user-friendly frontend fallback messages.
- Updated admin help to reflect the shortcodes and public REST behavior available in this release.

## 1.5.1
- **Nouveau :** Shortcode `[flightlinq_pilot_leaderboard]` pour afficher le classement des pilotes avec les layouts `table`, `cards` et `podium`.
- **Correctif :** Correction de la syntaxe PHP dans `includes/class-shortcodes.php` après l'ajout du shortcode `[flightlinq_pilot_leaderboard]` (aucune erreur de parse restante).
- **Correctif :** Mise à jour du layout `cards` du classement pilotes pour utiliser une grille responsive cohérente avec les autres shortcodes.
- **Note :** Aucune modification de la couche API ni des shortcodes existants (`[flightlinq_airline_summary]`, `[flightlinq_recent_flights]`).

## 1.4.9
- **Correctif :** Amélioration du layout `cards` du shortcode `[flightlinq_recent_flights]` pour utiliser une vraie grille responsive (plusieurs colonnes quand l'espace le permet, une seule colonne sur mobile).
- **Correctif :** Les cartes remplissent désormais correctement leur cellule de grille sans largeur fixe inutile.
- **Note :** Aucun changement sur la logique PHP ni sur le layout `table`, uniquement des ajustements CSS.

## 1.4.7
- **Nouveau :** Shortcode `[flightlinq_recent_flights]` pour afficher les derniers vols approuvés de la compagnie.
- **Nouveau :** Attribut `limit` pour contrôler le nombre de vols affichés (1-50, défaut: 5).
- **Nouveau :** Attribut `layout="table|cards"` pour choisir entre un layout tableau ou cartes.
- **Nouveau :** Attributs `show_*` pour contrôler l'affichage de chaque champ (date, pilote, vol, route, avion, immatriculation, temps bloc, temps vol, score, landing rate).
- **Nouveau :** Méthode `render_recent_flights()` pour le rendu du shortcode des derniers vols.
- **Nouveau :** Méthodes utilitaires pour le formatage des données : `format_flight_date()`, `format_flight_time()`, `get_flight_pilot()`, `get_flight_number()`, `get_flight_route()`, `get_flight_aircraft_type()`, `get_flight_aircraft_registration()`, `get_flight_score()`, `get_flight_landing_rate()`.
- **Nouveau :** Méthodes `render_flights_table()` et `render_flights_cards()` pour les layouts tableau et cartes.
- **Nouveau :** Méthodes `render_error_message()` et `render_empty_message()` pour la gestion des erreurs et listes vides.
- **Amélioration :** CSS frontend ajouté pour le shortcode des derniers vols (tableau responsive, cartes grid).
- **Amélioration :** Documentation mise à jour dans la page admin Shortcodes pour `[flightlinq_recent_flights]`.
- **Amélioration :** Exemples de shortcodes ajoutés pour les différents layouts et configurations.
- **Amélioration :** PHPDoc mises à jour avec @since 1.4.7.
- **Note :** Le shortcode utilise la fonction publique existante `flightlinq_api_get_recent_flights()` via l'endpoint `/flights/recent`.

## 1.4.6
- **Nouveau :** Attribut `banner_fit="contain|cover"` pour contrôler le mode d'ajustement de l'image de bannière.
- **Nouveau :** Attribut `banner_ratio="auto|3-1"` pour contrôler le ratio du conteneur de bannière.
- **Amélioration :** Correction de l'affichage responsive de la bannière pour éviter la troncature.
- **Amélioration :** CSS frontend refactorisé pour supporter les nouveaux attributs banner_fit et banner_ratio.
- **Amélioration :** Valeur par défaut banner_fit="contain" pour afficher toute l'image sans rognage.
- **Amélioration :** Valeur par défaut banner_ratio="3-1" pour un conteneur responsive au ratio 3:1 (format standard FlightLinq).
- **Amélioration :** Ajout des classes CSS correspondantes dans le rendu HTML (flightlinq-banner-fit-contain, flightlinq-banner-fit-cover, flightlinq-banner-ratio-auto, flightlinq-banner-ratio-3-1).
- **Amélioration :** Documentation mise à jour dans la page admin Shortcodes pour les nouveaux attributs.
- **Amélioration :** Exemples de shortcodes ajoutés pour les combinaisons banner_fit et banner_ratio.
- **Amélioration :** PHPDoc mises à jour avec @since 1.4.6.
- **Note :** La bannière n'est plus tronquée par défaut grâce à banner_fit="contain" et banner_ratio="3-1".

## 1.4.5
- **Nouveau :** Recherche récursive des médias type BANNER dans toute la réponse API pour une détection plus robuste.
- **Nouveau :** Méthode `find_banner_media_recursive()` pour parcourir récursivement les tableaux et trouver les médias à n'importe quel niveau de profondeur.
- **Nouveau :** Méthode `get_airline_banner_media()` pour retourner un tableau normalisé avec 'url' et 'alt'.
- **Amélioration :** Détection de bannière plus fiable avec support des champs simples directs (bannerUrl, coverUrl, headerImageUrl).
- **Amélioration :** Support des champs flexibles (banner, cover, headerImage) qui peuvent être chaîne ou tableau avec url.
- **Amélioration :** Utilisation de altText ou title depuis les médias pour l'attribut alt de l'image.
- **Amélioration :** Tri par sortOrder pour sélectionner la première bannière logique si plusieurs sont disponibles.
- **Amélioration :** Ignoration des médias non publics (isPublic === false).
- **Amélioration :** Suppression de la section "Pourquoi la bannière ne s'affiche pas ?" de la page admin Shortcodes pour une interface plus simple.
- **Amélioration :** Description de l'attribut banner_url mise à jour pour expliquer la détection automatique.
- **Amélioration :** PHPDoc mises à jour avec @since 1.4.5.
- **Note :** La recherche récursive permet de trouver les médias type BANNER même s'ils sont imbriqués profondément dans la réponse API.

## 1.4.4
- **Nouveau :** Détection automatique de la bannière via collections de médias type BANNER dans l'API FlightLinq.
- **Nouveau :** Méthode `get_airline_banner_url()` pour récupérer l'URL de bannière avec priorité : banner_url manuel > champs simples > collections de médias.
- **Nouveau :** Méthode `find_media_url_by_type()` pour chercher des médias par type dans les collections.
- **Nouveau :** Méthode `get_airline_banner_alt()` pour récupérer l'alt text de la bannière depuis les médias ou générer un texte par défaut.
- **Nouveau :** Support des collections de médias : media, medias, mediaItems, airlineMedia, images, assets.
- **Nouveau :** Tri par sortOrder pour sélectionner la première bannière logique si plusieurs sont disponibles.
- **Amélioration :** Ignoration des médias non publics (isPublic === false).
- **Amélioration :** Utilisation de altText depuis les médias si disponible, sinon texte par défaut basé sur le nom de la compagnie.
- **Amélioration :** Documentation mise à jour dans la page admin Shortcodes pour expliquer la détection automatique des bannières.
- **Amélioration :** Exemple shortcode ajouté pour la bannière automatique : `[flightlinq_airline_summary show_banner="yes"]`.
- **Amélioration :** PHPDoc mises à jour avec @since 1.4.4 pour les nouvelles méthodes.
- **Note :** FlightLinq peut renvoyer les visuels de compagnie sous forme de collection de médias avec des objets contenant type, url, altText, sortOrder, isPublic, etc.

## 1.4.3
- **Nouveau :** Attribut `theme="inherit"` pour hériter du thème WordPress courant (recommandé par défaut).
- **Nouveau :** Attribut `banner_url=""` pour fournir une bannière manuelle (prioritaire sur les champs API).
- **Nouveau :** Section "Pourquoi la bannière ne s'affiche pas ?" dans la page admin Shortcodes.
- **Nouveau :** Documentation complète des champs API vérifiés pour la bannière (banner, bannerUrl, cover, coverUrl, headerImage, headerImageUrl).
- **Amélioration :** Chargement des assets admin (CSS/JS) sur la page Shortcodes.
- **Amélioration :** Harmonisation de la page Shortcodes avec les autres pages admin (utilisation de .flightlinq-help-container, .flightlinq-help-header, .flightlinq-help-card).
- **Amélioration :** CSS frontend pour theme="inherit" utilise currentColor pour s'adapter au thème WordPress.
- **Amélioration :** Exemples de shortcodes mis à jour avec theme="inherit" et banner_url.
- **Amélioration :** PHPDoc mises à jour avec @since 1.4.3 pour les méthodes modifiées.
- **Note :** theme="inherit" est maintenant la valeur par défaut pour une meilleure intégration avec les thèmes WordPress.

## 1.4.2
- **Nouveau :** Attribut `surface="transparent|card"` pour le shortcode `[flightlinq_airline_summary]` permettant de contrôler le fond indépendamment du thème.
- **Nouveau :** Séparation des responsabilités : `theme` contrôle les couleurs de texte et bordures, `surface` contrôle le fond.
- **Nouveau :** Stylisation complète de la page admin Shortcodes avec classes CSS dédiées (`.flightlinq-shortcodes-page`, `.flightlinq-shortcodes-card`, etc.).
- **Nouveau :** Lien "Voir les shortcodes" ajouté dans la carte "Aide rapide" de la page Paramètres.
- **Nouveau :** Section "Thème et surface" dans la page admin Shortcodes expliquant la différence entre les deux attributs.
- **Nouveau :** Exemples de shortcodes mis à jour avec les combinaisons theme/surface.
- **Amélioration :** CSS frontend refactorisé pour séparer la logique theme/surface.
- **Amélioration :** `surface="transparent"` : fond transparent, s'adapte au thème courant (recommandé).
- **Amélioration :** `surface="card"` : fond selon le thème choisi (blanc pour light, sombre pour dark).
- **Amélioration :** PHPDoc mises à jour pour les méthodes modifiées avec @since 1.4.2.
- **Amélioration :** Page admin Shortcodes utilise maintenant le même header bleu premium que les autres pages admin.
- **Note :** Les shortcodes prévus prochainement sont listés avec un badge "Prévu prochainement" pour éviter toute confusion.

## 1.4.1
- **Nouveau :** Page admin dédiée aux shortcodes "Shortcodes FlightLinq" avec documentation complète.
- **Nouveau :** Séparation de la documentation shortcodes de la page "Aide PHP / Exemples" (cette page reste dédiée aux appels PHP dans les thèmes).
- **Nouveau :** Attribut `theme="auto|light|dark"` pour le shortcode `[flightlinq_airline_summary]` avec adaptation automatique via `prefers-color-scheme`.
- **Nouveau :** Attribut `layout="card|compact"` pour le shortcode `[flightlinq_airline_summary]`.
- **Nouveau :** Contrôle granulaire de chaque élément du shortcode : `show_logo`, `show_banner`, `show_name`, `show_code`, `show_iata`, `show_headquarters`, `show_founded`, `show_website`, `show_description`.
- **Nouveau :** Contrôle granulaire de chaque statistique : `show_total_pilots`, `show_total_flights`, `show_total_hours`, `show_average_rating`.
- **Nouveau :** Gestion facultative de la bannière si un champ existe dans la réponse API (banner, bannerUrl, cover, coverUrl, headerImage, headerImageUrl).
- **Nouveau :** Formatage de la date de fondation avec `date_i18n()` selon les paramètres WordPress.
- **Nouveau :** Affichage du code compagnie et code IATA avec libellés explicites ("Code:", "IATA:").
- **Nouveau :** Méthode `render_shortcode_example()` pour afficher les exemples de shortcodes dans l'admin.
- **Amélioration :** CSS frontend avec variables CSS pour les thèmes (light, dark, auto).
- **Amélioration :** CSS frontend avec layout compact.
- **Amélioration :** CSS frontend avec styles pour la bannière et les méta-données.
- **Amélioration :** PHPDoc complètes pour les nouvelles méthodes et les méthodes modifiées.
- **Note :** Les shortcodes prévus prochainement sont listés dans la page admin mais ne sont pas encore implémentés.

## 1.4.0
- **Nouveau :** Introduction des shortcodes frontend avec styles propres.
- **Nouveau :** Shortcode `[flightlinq_airline_summary]` pour afficher un résumé de la compagnie (logo, nom, description, statistiques).
- **Nouveau :** Attributs du shortcode : show_logo, show_description, show_stats, show_website (défaut: yes).
- **Nouveau :** Création du fichier CSS frontend (assets/css/frontend.css) avec styles modernes et responsives.
- **Nouveau :** Fonction publique `flightlinq_api_get_cached_data()` pour la gestion du cache partagée.
- **Nouveau :** Section "Shortcodes disponibles" dans la page d'aide admin avec exemples d'utilisation.
- **Refonte :** Classe Shortcodes entièrement refondue pour la nouvelle génération de shortcodes.
- **Réactivation :** Les shortcodes sont réactivés avec la nouvelle architecture (v1.4.0).
- **Sécurité :** Messages d'erreur frontend sobre ("Données FlightLinq temporairement indisponibles").
- **Compatibilité :** Styles frontend compatibles avec n'importe quel thème WordPress.
- **Performance :** Les shortcodes utilisent le cache pour éviter les appels API répétés.
- **Note :** Les anciens shortcodes bruts (flightlinq_airline, flightlinq_pilots, etc.) ne sont pas réactivés dans cette phase.

## 1.3.7
- Correction du bouton "Vider le cache" qui affichait une alerte navigateur native.
- Suppression des alertes navigateur (alert, confirm) et remplacement par une confirmation inline moderne.
- Suppression du handler AJAX en double dans class-admin.php (le handler correct est dans class-cache.php).
- Ajout d'une zone de message dans la carte Cache API pour afficher les résultats de vidage (succès/erreur).
- Nouveau comportement du bouton "Vider le cache" : premier clic pour confirmer, deuxième clic pour exécuter.
- Ajout d'un état de chargement "Vidage en cours..." pendant l'appel AJAX.
- Les messages de succès ou d'erreur apparaissent maintenant dans la carte Cache API, pas dans une alerte navigateur.
- Mise à jour de la localisation JavaScript avec les nouveaux messages de cache.
- Ajout des styles CSS pour les messages cache (flightlinq-cache-message).
- Le bouton "Vider le cache" ne soumet plus le formulaire principal (type="button" conservé).

## 1.3.6
- Correction de l'espacement vertical réel entre les cartes principales (formulaire avec gap: 28px).
- Correction du bouton "Tester la connexion" qui ne s'agrandit plus quand le résultat s'affiche (nouvelle structure flightlinq-connection-test).
- Suppression définitive de la ligne horizontale inutile dans la carte "Actions" (variante flightlinq-settings-card--actions).
- Amélioration du padding des cartes de droite (24px) pour un meilleur confort de lecture.
- Amélioration du style des lignes d'état : fond gris clair (#f8fafc), bordure arrondie, espacement interne (12px 14px).
- Amélioration du style du code inline dans "Aide rapide" : fond gris clair (#f1f5f9), word-break pour éviter les débordements.
- Finalisation de l'interface de la page paramètres avec un rendu stable et professionnel.

## 1.3.5
- Amélioration de l'espacement vertical entre les cartes de la page paramètres (gap: 28px).
- Suppression de la ligne horizontale inutile dans la carte "Actions".
- Déplacement du bouton "Tester la connexion" dans la carte "Connexion API" pour une meilleure cohérence contextuelle.
- Le bouton "Enregistrer les modifications" est maintenant seul dans la carte "Actions" avec une description explicite.
- Le bouton "Vider le cache" reste dans la carte "Cache API".
- Amélioration de la lisibilité et de l'organisation de la page paramètres.

## 1.3.4
- Refonte complète de la page "Paramètres" avec interface moderne premium.
- Ajout d'un en-tête bleu premium avec badge "API serveur active".
- Création de la méthode get_masked_api_key() pour masquer sécurisé la clé API (12 premiers + 4 derniers caractères).
- Organisation des réglages en cartes modernes : Connexion API, Cache API, Actions.
- Création d'une sidebar "État du plugin" avec liste d'état complète (version, URL API, fonctions PHP, shortcodes, cache, clé API).
- Création d'une sidebar "Aide rapide" avec lien vers la page d'aide PHP et exemple de fonction.
- Ajout de boutons custom (primary, secondary, warning) pour une meilleure cohérence visuelle.
- Amélioration du responsive design avec breakpoint à 1100px.
- Suppression de la dépendance à la classe Client pour le masquage de clé API.
- Conservation des options existantes (flightlinq_api_key, flightlinq_cache_duration, flightlinq_enable_cache).
- Conservation des nonces et des handlers AJAX existants.

## 1.3.3
- Harmonisation de la page "Paramètres" avec la page "Aide PHP".
- Ajout du conteneur flightlinq-settings-container pour une largeur cohérente.
- Amélioration des styles des titres h1 et h2 de la page paramètres.
- Amélioration du style des séparateurs hr de la page paramètres.

## 1.3.2
- Correction définitive du bouton "Copier le code" (suppression de la classe WordPress button).
- Harmonisation des largeurs du header, note et cartes de contenu avec conteneur unique.
- Ajout de la classe flightlinq-help-container pour une largeur cohérente sur toute la page.

## 1.3.1
- Amélioration du contraste du bouton "Copier le code" (fond bleu, texte blanc).
- Ajout de l'état `is-copied` (fond vert) pour le bouton copier.
- Amélioration du sommaire avec bordure décorative et hover animé.
- Amélioration de l'en-tête avec cercle décoratif et ombre premium.
- Amélioration des cartes avec hover effect et bordures arrondies.
- Amélioration du badge PHP avec fond semi-transparent.
- Amélioration de la toolbar du bloc code avec meilleur espacement.
- Ajout de la classe `is-copied` dans le JavaScript du bouton copier.
- Amélioration du responsive design pour mobile.

## 1.3.0
- Refonte complète de la page d'aide PHP avec interface professionnelle développeur.
- Ajout d'un en-tête visuel avec gradient et badge "Aucun shortcode nécessaire".
- Amélioration du sommaire avec carte sticky et ancres internes.
- Refonte des blocs d'exemples avec header, toolbar et badge PHP.
- Amélioration du CSS admin avec blocs de code sur fond sombre.
- Correction du chargement des assets admin sur la page d'aide.
- Amélioration de la lisibilité et de l'ergonomie de la page d'aide.

## 1.2.0
- Amélioration de la page d'aide PHP avec nouvelle structure visuelle.
- Ajout de boutons "Copier le code" pour tous les exemples.
- Ajout d'un sommaire avec ancres pour une navigation facile.
- Correction des chemins FlightLinq dans les exemples (stats.totalPilots, pilots.total, flights.total, etc.).
- Ajout d'un exemple complet de template minimal.
- Amélioration du CSS admin pour une meilleure lisibilité.
- Ajout de JavaScript pour la fonctionnalité de copie de code avec fallback.

## 1.1.0
- Transformation en couche d'accès API pour le thème WordPress.
- Ajout de fonctions PHP publiques pour l'intégration dans les templates.
- Ajout de `function_exists()` à toutes les fonctions publiques pour éviter les conflits.
- Désactivation des shortcodes par défaut.
- Simplification de l'admin (suppression des diagnostics avancés).
- Conservation de l'URL API correcte : `https://api.flightlinq.com/api/v1/external`.
- Chargement de functions-template.php après l'initialisation des classes.
- Création d'un menu admin dédié FlightLinq API avec sous-menus Paramètres et Aide PHP / Exemples.

## 1.0.4
- Ajout de méthodes utilitaires sécurisées pour les shortcodes.
- Normalisation des réponses de collection.
- Ajout du diagnostic des shortcodes.

## 1.0.3
- Correction de l'URL de base de l'API (`api.flightlinq.com`).
- Ajout du test de comparaison ancienne/nouvelle URL.
- Vidage automatique du cache lors de la mise à jour.

## 1.0.2
- Ajout de la section diagnostic dans l'admin.
- Amélioration de la gestion des erreurs.

## 1.0.0
- Version initiale.
- Intégration de l'API FlightLinq.
- Page d'administration pour configurer la clé API.
- Cache des réponses API.
- Endpoints REST WordPress.
- Shortcodes pour afficher les données sur le frontend.
