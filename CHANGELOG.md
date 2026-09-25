# Changelog

🇫🇷 Toutes les évolutions notables du paquet sont listées ici. Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le projet respecte le [versionnage sémantique](https://semver.org/lang/fr/).

🇬🇧 All important changes of the package are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [semantic versioning](https://semver.org/).

## [1.0.0] - 2026-09-25

### Ajouté / Added

- 🇫🇷 `Normalizer` : normalisation d'un texte (minuscules, sans accents ni ligatures) et construction de motifs LIKE échappés.
  🇬🇧 `Normalizer`: normalizes a text (lower case, no accents, no ligatures) and builds escaped LIKE patterns.
- 🇫🇷 `SqlExpression` : expression SQL équivalente pour MySQL, MariaDB, PostgreSQL et SQLite, sans extension de base de données.
  🇬🇧 `SqlExpression`: the same normalization in SQL for MySQL, MariaDB, PostgreSQL and SQLite, with no database extension.
- 🇫🇷 `Mode` : recherche *contient*, *commence par*, *se termine par* ou *égal*, avec les alias des datatables (`contain`, `start_with`, `end_with`, `equal`).
  🇬🇧 `Mode`: *contains*, *starts with*, *ends with* or *equal* search, with the datatable names (`contain`, `start_with`, `end_with`, `equal`).
- 🇫🇷 Laravel : macros `whereLikeUnaccent`, `orWhereLikeUnaccent`, `whereNotLikeUnaccent`, `orWhereNotLikeUnaccent`, `whereAnyLikeUnaccent` et `orWhereAnyLikeUnaccent` sur le Query Builder et Eloquent, avec les colonnes JSON et les expressions brutes.
  🇬🇧 Laravel: new methods `whereLikeUnaccent`, `orWhereLikeUnaccent`, `whereNotLikeUnaccent`, `orWhereNotLikeUnaccent`, `whereAnyLikeUnaccent` and `orWhereAnyLikeUnaccent` on the Query Builder and Eloquent, with JSON columns and raw expressions.
- 🇫🇷 Doctrine : fonction DQL `UNACCENT()` et helper `UnaccentQuery`.
  🇬🇧 Doctrine: DQL function `UNACCENT()` and `UnaccentQuery` class.
- 🇫🇷 Symfony : `UnaccentSearchBundle`, qui enregistre la fonction DQL automatiquement, y compris avec plusieurs entity managers.
  🇬🇧 Symfony: `UnaccentSearchBundle` adds the DQL function automatically, also with several entity managers.
- 🇫🇷 Table de remplacement extensible via la configuration Laravel, la configuration Symfony ou `Normalizer::extend()`.
  🇬🇧 You can add characters to the replacement table with the Laravel configuration, the Symfony configuration or `Normalizer::extend()`.

[1.0.0]: https://github.com/kaveraa/unaccent-search/releases/tag/v1.0.0
