# Changelog

Toutes les évolutions notables du paquet sont listées ici.
Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le projet respecte le [versionnage sémantique](https://semver.org/lang/fr/).

## [1.0.0] - 2026-09-25

### Ajouté

- `Normalizer` : normalisation d'un texte (minuscules, sans accents ni ligatures) et construction de motifs LIKE échappés.
- `SqlExpression` : expression SQL équivalente pour MySQL, MariaDB, PostgreSQL et SQLite, sans extension de base de données.
- `Mode` : recherche *contient*, *commence par*, *se termine par* ou *égal*, avec les alias des datatables (`contain`, `start_with`, `end_with`, `equal`).
- Laravel : macros `whereLikeUnaccent`, `orWhereLikeUnaccent`, `whereNotLikeUnaccent`, `orWhereNotLikeUnaccent`, `whereAnyLikeUnaccent` et `orWhereAnyLikeUnaccent` sur le Query Builder et Eloquent, avec prise en charge des colonnes JSON et des expressions brutes.
- Doctrine : fonction DQL `UNACCENT()` et helper `UnaccentQuery`.
- Symfony : `UnaccentSearchBundle`, qui enregistre la fonction DQL automatiquement, y compris avec plusieurs entity managers.
- Table de remplacement extensible via la configuration Laravel, la configuration Symfony ou `Normalizer::extend()`.

[1.0.0]: https://github.com/kaveraa/unaccent-search/releases/tag/v1.0.0
