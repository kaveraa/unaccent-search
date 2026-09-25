# Changelog

**FR** Toutes les évolutions notables du paquet sont listées ici. Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le projet respecte le [versionnage sémantique](https://semver.org/lang/fr/).

**EN** All important changes of the package are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [semantic versioning](https://semver.org/).

## [1.0.1] - 2026-09-25

### Corrigé / Fixed

- **FR** SQLite : la recherche échouait avec "parser stack overflow" sur SQLite avant 3.46 (trop de `REPLACE()` imbriqués). Sous SQLite, la normalisation passe maintenant par une fonction PHP `unaccent_search()`, enregistrée automatiquement (Laravel, Doctrine, middleware `SqliteMiddleware` ajouté par le bundle Symfony). Les majuscules hors table (`Č`, `Ł`) sont aussi gérées.
  **EN** SQLite: the search failed with "parser stack overflow" on SQLite before 3.46 (too many nested `REPLACE()`). With SQLite, the normalization now uses a PHP function `unaccent_search()`, registered automatically (Laravel, Doctrine, `SqliteMiddleware` added by the Symfony bundle). Upper case letters that are not in the table (`Č`, `Ł`) also work now.

### Ajouté / Added

- **FR** `SqliteFunction::register()` pour une connexion SQLite utilisée à la main, et `Doctrine\SqliteMiddleware` pour Doctrine sans Symfony.
  **EN** `SqliteFunction::register()` for a SQLite connection used directly, and `Doctrine\SqliteMiddleware` for Doctrine without Symfony.

## [1.0.0] - 2026-09-25

### Ajouté / Added

- **FR** `Normalizer` : normalisation d'un texte (minuscules, sans accents ni ligatures) et construction de motifs LIKE échappés.
  **EN** `Normalizer`: normalizes a text (lower case, no accents, no ligatures) and builds escaped LIKE patterns.
- **FR** `SqlExpression` : expression SQL équivalente pour MySQL, MariaDB, PostgreSQL et SQLite, sans extension de base de données.
  **EN** `SqlExpression`: the same normalization in SQL for MySQL, MariaDB, PostgreSQL and SQLite, with no database extension.
- **FR** `Mode` : recherche *contient*, *commence par*, *se termine par* ou *égal*, avec les alias des datatables (`contain`, `start_with`, `end_with`, `equal`).
  **EN** `Mode`: *contains*, *starts with*, *ends with* or *equal* search, with the datatable names (`contain`, `start_with`, `end_with`, `equal`).
- **FR** Laravel : macros `whereLikeUnaccent`, `orWhereLikeUnaccent`, `whereNotLikeUnaccent`, `orWhereNotLikeUnaccent`, `whereAnyLikeUnaccent` et `orWhereAnyLikeUnaccent` sur le Query Builder et Eloquent, avec les colonnes JSON et les expressions brutes.
  **EN** Laravel: new methods `whereLikeUnaccent`, `orWhereLikeUnaccent`, `whereNotLikeUnaccent`, `orWhereNotLikeUnaccent`, `whereAnyLikeUnaccent` and `orWhereAnyLikeUnaccent` on the Query Builder and Eloquent, with JSON columns and raw expressions.
- **FR** Doctrine : fonction DQL `UNACCENT()` et helper `UnaccentQuery`.
  **EN** Doctrine: DQL function `UNACCENT()` and `UnaccentQuery` class.
- **FR** Symfony : `UnaccentSearchBundle`, qui enregistre la fonction DQL automatiquement, y compris avec plusieurs entity managers.
  **EN** Symfony: `UnaccentSearchBundle` adds the DQL function automatically, also with several entity managers.
- **FR** Table de remplacement extensible via la configuration Laravel, la configuration Symfony ou `Normalizer::extend()`.
  **EN** You can add characters to the replacement table with the Laravel configuration, the Symfony configuration or `Normalizer::extend()`.

[1.0.1]: https://github.com/kaveraa/unaccent-search/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/kaveraa/unaccent-search/releases/tag/v1.0.0
