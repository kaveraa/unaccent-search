# Changelog

**FR** Toutes les évolutions notables du paquet sont listées ici. Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le projet respecte le [versionnage sémantique](https://semver.org/lang/fr/).

**EN** All important changes of the package are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [semantic versioning](https://semver.org/).

## [2.0.0] - 2026-09-25

### Modifié / Changed (incompatible / breaking)

- **FR** `Mode::resolve()` n'accepte plus que les valeurs de l'enum : `contains`, `starts_with`, `ends_with`, `exact`. Les alias courts sont supprimés : pour migrer, utilisez la valeur complète ou l'enum `Mode`.
  **EN** `Mode::resolve()` now only accepts the enum values: `contains`, `starts_with`, `ends_with`, `exact`. The short aliases are removed: to migrate, use the full value or the `Mode` enum.
- **FR** Laravel 11 n'est plus supporté : toutes ses versions ont une faille de sécurité connue et Composer ne les installe plus. Laravel 12 et 13 sont supportés.
  **EN** Laravel 11 is not supported any more: all its versions have a known security problem and Composer does not install them any more. Laravel 12 and 13 are supported.

### Ajouté / Added

- **FR** Guide de contribution (`CONTRIBUTING.md`) et modèle de Pull Request. Exemple de formulaire de recherche avec `Rule::enum(Mode::class)` dans les README.
  **EN** Contribution guide (`CONTRIBUTING.md`) and pull request template. Search form example with `Rule::enum(Mode::class)` in the README files.

## [1.0.2] - 2026-09-25

### Documentation

- **FR** Bannière en haut des README, badges téléchargements et version de PHP, badge de licence lu depuis GitHub.
  **EN** Banner at the top of the README files, downloads and PHP version badges, license badge read from GitHub.

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
- **FR** `Mode` : recherche *contient*, *commence par*, *se termine par* ou *égal*, avec des alias courts (retirés en 2.0.0).
  **EN** `Mode`: *contains*, *starts with*, *ends with* or *equal* search, with short aliases (removed in 2.0.0).
- **FR** Laravel : macros `whereLikeUnaccent`, `orWhereLikeUnaccent`, `whereNotLikeUnaccent`, `orWhereNotLikeUnaccent`, `whereAnyLikeUnaccent` et `orWhereAnyLikeUnaccent` sur le Query Builder et Eloquent, avec les colonnes JSON et les expressions brutes.
  **EN** Laravel: new methods `whereLikeUnaccent`, `orWhereLikeUnaccent`, `whereNotLikeUnaccent`, `orWhereNotLikeUnaccent`, `whereAnyLikeUnaccent` and `orWhereAnyLikeUnaccent` on the Query Builder and Eloquent, with JSON columns and raw expressions.
- **FR** Doctrine : fonction DQL `UNACCENT()` et helper `UnaccentQuery`.
  **EN** Doctrine: DQL function `UNACCENT()` and `UnaccentQuery` class.
- **FR** Symfony : `UnaccentSearchBundle`, qui enregistre la fonction DQL automatiquement, y compris avec plusieurs entity managers.
  **EN** Symfony: `UnaccentSearchBundle` adds the DQL function automatically, also with several entity managers.
- **FR** Table de remplacement extensible via la configuration Laravel, la configuration Symfony ou `Normalizer::extend()`.
  **EN** You can add characters to the replacement table with the Laravel configuration, the Symfony configuration or `Normalizer::extend()`.

[2.0.0]: https://github.com/kaveraa/unaccent-search/compare/v1.0.2...v2.0.0
[1.0.2]: https://github.com/kaveraa/unaccent-search/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/kaveraa/unaccent-search/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/kaveraa/unaccent-search/releases/tag/v1.0.0
