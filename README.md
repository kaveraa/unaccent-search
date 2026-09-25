# Unaccent Search

<p align="center"><img src="https://raw.githubusercontent.com/kaveraa/unaccent-search/main/art/banner.svg" alt="Unaccent Search" width="100%"></p>

[![Tests](https://github.com/kaveraa/unaccent-search/actions/workflows/tests.yml/badge.svg)](https://github.com/kaveraa/unaccent-search/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/kaveraa/unaccent-search.svg)](https://packagist.org/packages/kaveraa/unaccent-search)
[![Licence](https://img.shields.io/github/license/kaveraa/unaccent-search.svg)](https://github.com/kaveraa/unaccent-search/blob/main/LICENSE)
[![Downloads](https://img.shields.io/packagist/dt/kaveraa/unaccent-search.svg)](https://packagist.org/packages/kaveraa/unaccent-search)
[![PHP](https://img.shields.io/packagist/dependency-v/kaveraa/unaccent-search/php.svg)](https://packagist.org/packages/kaveraa/unaccent-search)

**Français** - [English](https://github.com/kaveraa/unaccent-search/blob/main/README.en.md)

Recherche SQL **insensible à la casse et aux accents** pour **Laravel** et **Symfony / Doctrine**.

Un utilisateur qui tape `eleve` trouve `Élève`, `ÉLÈVE` et `élève`. `creme brulee` trouve `Crème brûlée`, et `oeuvre` trouve `Œuvre`.

```php
// Laravel
Product::whereLikeUnaccent('name', $request->search)->get();

// Symfony / Doctrine
UnaccentQuery::andWhereLike($qb, 'p.name', $search);
```

- **Aucune extension de base de données** : pas besoin de `unaccent` ni d'une collation particulière.
- **MySQL, MariaDB, PostgreSQL et SQLite** avec le même code.
- **Prêt à l'emploi** : `composer require` suffit sous Laravel, une ligne dans `bundles.php` sous Symfony.
- **Sécurisé** : le terme recherché est toujours passé en paramètre lié, les noms de colonnes sont vérifiés, et `%` et `_` saisis par l'utilisateur sont cherchés tels quels.
- **Un terme vide ne filtre rien** : on peut passer directement `$request->search`, même s'il est `null`.

---

## Sommaire

- [Installation](#installation)
- [Laravel](#laravel)
- [Symfony](#symfony)
- [Doctrine sans Symfony](#doctrine-sans-symfony)
- [Les modes de recherche](#les-modes-de-recherche)
- [Ajouter des caractères](#ajouter-des-caractères)
- [Utiliser le normaliseur seul](#utiliser-le-normaliseur-seul)
- [Comment ça marche](#comment-ça-marche)
- [Limites à connaître](#limites-à-connaître)
- [Développement](#développement)

## Installation

```bash
composer require kaveraa/unaccent-search
```

Prérequis : PHP 8.2 ou plus, avec l'extension `mbstring`.

| Framework | Versions supportées |
|---|---|
| Laravel | 12, 13 |
| Symfony | 6.4, 7.x, 8.x (avec DoctrineBundle 2.13+ ou 3.x ; testé en CI à partir de 7.2) |
| Doctrine ORM | 3.x (DBAL 4) |

---

## Laravel

### Mise en place

Rien à faire : le ServiceProvider est découvert automatiquement par Laravel.

### Rechercher dans une colonne

```php
use App\Models\Product;

$products = Product::whereLikeUnaccent('name', $request->input('search'))->get();
```

Les méthodes sont ajoutées au **Query Builder**. Elles fonctionnent donc aussi sur les modèles Eloquent, les relations, les sous-requêtes et `DB::table()` :

```php
DB::table('products')->whereLikeUnaccent('products.name', 'eleve')->get();

$user->orders()->whereLikeUnaccent('reference', $search)->paginate();
```

### Rechercher dans plusieurs colonnes (recherche globale)

`whereAnyLikeUnaccent` trouve les lignes dont **au moins une** des colonnes correspond. Les conditions sont regroupées entre parenthèses, donc les `where` suivants s'appliquent bien à tout le groupe :

```php
Product::whereAnyLikeUnaccent(['name', 'commercial_name', 'code'], $request->search)
    ->where('active', true)   // (name LIKE ... OR commercial_name LIKE ... OR code LIKE ...) AND active = 1
    ->paginate();
```

### Toutes les méthodes

| Méthode | Condition ajoutée |
|---|---|
| `whereLikeUnaccent($colonne, $terme, $mode = Mode::Contains)` | `AND colonne ressemble au terme` |
| `orWhereLikeUnaccent($colonne, $terme, $mode)` | `OR colonne ressemble au terme` |
| `whereNotLikeUnaccent($colonne, $terme, $mode)` | `AND colonne ne ressemble pas au terme` |
| `orWhereNotLikeUnaccent($colonne, $terme, $mode)` | `OR colonne ne ressemble pas au terme` |
| `whereAnyLikeUnaccent([$col1, $col2], $terme, $mode)` | `AND (col1 ressemble OR col2 ressemble ...)` |
| `orWhereAnyLikeUnaccent([$col1, $col2], $terme, $mode)` | `OR (col1 ressemble OR col2 ressemble ...)` |

`$mode` indique comment le terme doit correspondre : *contient* (par défaut), *commence par*, *se termine par* ou *égal*. Voir [les modes de recherche](#les-modes-de-recherche).

### Colonnes acceptées

```php
->whereLikeUnaccent('name', $terme)                  // colonne
->whereLikeUnaccent('products.name', $terme)         // table.colonne (utile avec des jointures)
->whereLikeUnaccent('data->address->city', $terme)   // clé d'une colonne JSON
->whereLikeUnaccent(DB::raw("CONCAT(first_name, ' ', last_name)"), $terme) // expression SQL
```

> **Attention :** une expression `DB::raw()` est insérée telle quelle dans la requête : n'y mettez jamais de saisie utilisateur. Les noms de colonnes en texte sont vérifiés, et un nom invalide lève une `InvalidArgumentException`.

### Exemple : formulaire de recherche

Le mode peut venir directement de la requête, validé avec `Rule::enum()` :

```php
use Illuminate\Validation\Rule;
use Kaveraa\UnaccentSearch\Mode;

public function index(Request $request)
{
    $validated = $request->validate([
        'q' => ['nullable', 'string', 'max:100'],
        'mode' => ['nullable', Rule::enum(Mode::class)], // contains, starts_with, ends_with, exact
    ]);

    return Product::query()
        ->whereAnyLikeUnaccent(['name', 'code'], $validated['q'] ?? null, $validated['mode'] ?? Mode::Contains)
        ->paginate();
}
```

### Configuration (facultative)

Pour [ajouter des caractères](#ajouter-des-caractères) à la table de remplacement :

```bash
php artisan vendor:publish --tag=unaccent-search-config
```

```php
// config/unaccent-search.php
return [
    'replacements' => ['ł' => 'l', 'š' => 's'],
];
```

### Autocomplétion dans l'IDE

Les macros sont reconnues par [Laravel Idea](https://laravel-idea.com/) et par [barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) (`php artisan ide-helper:generate`).

---

## Symfony

### Mise en place

Activez le bundle dans `config/bundles.php` :

```php
return [
    // ...
    Kaveraa\UnaccentSearch\Symfony\UnaccentSearchBundle::class => ['all' => true],
];
```

C'est tout. Le bundle déclare la fonction DQL `UNACCENT` auprès de Doctrine, sans modifier `doctrine.yaml`, y compris si vous avez plusieurs entity managers.

### Rechercher depuis un repository

Le helper `UnaccentQuery` ajoute les conditions à un `QueryBuilder` Doctrine :

```php
use Kaveraa\UnaccentSearch\Doctrine\UnaccentQuery;

class ProductRepository extends ServiceEntityRepository
{
    /** @return Product[] */
    public function search(?string $search): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.name');

        UnaccentQuery::andWhereAnyLike($qb, ['p.name', 'p.commercialName', 'p.code'], $search);

        return $qb->getQuery()->getResult();
    }
}
```

### Toutes les méthodes

| Méthode | Condition ajoutée |
|---|---|
| `UnaccentQuery::andWhereLike($qb, 'p.champ', $terme, $mode = Mode::Contains)` | `AND champ ressemble au terme` |
| `UnaccentQuery::orWhereLike($qb, 'p.champ', $terme, $mode)` | `OR champ ressemble au terme` |
| `UnaccentQuery::andWhereNotLike($qb, 'p.champ', $terme, $mode)` | `AND champ ne ressemble pas au terme` |
| `UnaccentQuery::andWhereAnyLike($qb, ['p.a', 'p.b'], $terme, $mode)` | `AND (a ressemble OR b ressemble ...)` |
| `UnaccentQuery::condition($qb, 'p.champ', $terme, $mode, $not = false)` | Retourne la condition DQL (ou `null` si le terme est vide) pour la combiner librement |

Les paramètres sont liés automatiquement, avec des noms uniques. Vous pouvez donc appeler plusieurs méthodes sur le même `QueryBuilder`.

Avec `condition()`, vous composez vos propres expressions :

```php
$qb->andWhere($qb->expr()->orX(
    UnaccentQuery::condition($qb, 'p.name', $search),
    'p.reference = :ref',
))->setParameter('ref', $search);
```

### Écrire le DQL à la main

La fonction `UNACCENT()` est utilisable partout dans le DQL. Construisez le paramètre avec `Normalizer::pattern()` et gardez `ESCAPE '!'` :

```php
use Kaveraa\UnaccentSearch\Normalizer;

$em->createQuery("SELECT p FROM App\Entity\Product p WHERE UNACCENT(p.name) LIKE :term ESCAPE '!'")
   ->setParameter('term', Normalizer::pattern($search))
   ->getResult();
```

### Configuration (facultative)

```yaml
# config/packages/unaccent_search.yaml
unaccent_search:
    replacements:
        'ł': 'l'
        'š': 's'
```

---

## Doctrine sans Symfony

Enregistrez la fonction DQL sur la configuration de l'ORM, puis utilisez `UnaccentQuery` comme ci-dessus :

```php
use Kaveraa\UnaccentSearch\Doctrine\UnaccentFunction;

$config->addCustomStringFunction(UnaccentFunction::NAME, UnaccentFunction::class);
```

Avec SQLite et un cache de requêtes Doctrine, ajoutez aussi le middleware, qui prépare chaque connexion SQLite (sans effet sur les autres bases) :

```php
use Kaveraa\UnaccentSearch\Doctrine\SqliteMiddleware;

$config->setMiddlewares([new SqliteMiddleware()]);
```

---

## Les modes de recherche

Le mode se passe sous forme d'enum `Kaveraa\UnaccentSearch\Mode` ou de texte.

| Mode | Texte accepté | Motif généré pour `Élève` | Trouve |
|---|---|---|---|
| `Mode::Contains` *(défaut)* | `contains` | `%eleve%` | "Un **élève** motivé" |
| `Mode::StartsWith` | `starts_with` | `eleve%` | "**Élève**s de CM2" |
| `Mode::EndsWith` | `ends_with` | `%eleve` | "Nouvel **élève**" |
| `Mode::Exact` | `exact` | `eleve` | "**ÉLÈVE**" uniquement |

```php
use Kaveraa\UnaccentSearch\Mode;

Product::whereLikeUnaccent('name', 'cre', Mode::StartsWith)->get();
Product::whereLikeUnaccent('name', 'cre', 'starts_with')->get(); // équivalent
```

---

## Ajouter des caractères

La table par défaut couvre le français et les accents européens les plus courants :

| Caractères | Deviennent |
|---|---|
| à â ä á ã å | a |
| é è ê ë | e |
| ï î ì í | i |
| ô ö ò ó õ ø | o |
| ù û ü ú | u |
| ÿ ý | y |
| ç / ñ / đ | c / n / d |
| œ / æ / ß | oe / ae / ss |

Les majuscules sont gérées automatiquement : `É` est d'abord mis en minuscule, puis remplacé.

Pour d'autres alphabets, ajoutez des entrées via la configuration ([Laravel](#configuration-facultative), [Symfony](#configuration-facultative-1)) ou directement en PHP, **au démarrage de l'application** :

```php
use Kaveraa\UnaccentSearch\Normalizer;

Normalizer::extend(['ł' => 'l', 'š' => 's', 'č' => 'c']);
```

Sous MySQL, MariaDB et PostgreSQL, chaque entrée ajoute un `REPLACE()` à la requête : n'ajoutez que les caractères présents dans vos données.

---

## Utiliser le normaliseur seul

Le cœur du paquet ne dépend d'aucun framework :

```php
use Kaveraa\UnaccentSearch\Normalizer;
use Kaveraa\UnaccentSearch\Mode;

Normalizer::normalize('Crème Brûlée');          // 'creme brulee'
Normalizer::pattern('Élève');                    // '%eleve%'  (motif LIKE échappé)
Normalizer::pattern('Élève', Mode::StartsWith);  // 'eleve%'
Normalizer::pattern('100%');                     // '%100!%%'  (le % est cherché littéralement)
```

`SqlExpression::wrap()` produit l'expression SQL normalisée d'une colonne pour une base donnée, si vous construisez vos requêtes à la main :

```php
use Kaveraa\UnaccentSearch\SqlExpression;

$sql = SqlExpression::wrap('`name`', SqlExpression::MYSQL);
// REPLACE(REPLACE(... LOWER(CAST(`name` AS CHAR)) ..., 'à', 'a') ...)

$pdo->prepare("SELECT * FROM products WHERE {$sql} LIKE ? ESCAPE '!'")
    ->execute([Normalizer::pattern($search)]);
```

Sous SQLite, l'expression appelle la fonction `unaccent_search()`. Enregistrez-la une fois sur votre connexion (les ponts Laravel et Doctrine le font pour vous) :

```php
use Kaveraa\UnaccentSearch\SqliteFunction;

SqliteFunction::register($pdo); // PDO, Pdo\Sqlite ou SQLite3
```

---

## Comment ça marche

La recherche compare deux valeurs normalisées **de la même façon** :

1. **Côté PHP**, le terme saisi est mis en minuscules, ses accents sont remplacés, puis les jokers `%` et `_` sont échappés : `Élève` devient `%eleve%`.
2. **Côté SQL**, la colonne passe dans la même table de remplacement : `REPLACE(REPLACE(LOWER(name), 'é', 'e'), 'è', 'e')...`. Sous SQLite, qui refuse trop de `REPLACE()` imbriqués, c'est une fonction PHP `unaccent_search()` qui fait le même travail. Elle est enregistrée automatiquement sur la connexion.
3. La comparaison se fait avec `LIKE ? ESCAPE '!'`, et le terme est toujours un paramètre lié.

Comme la même table sert des deux côtés, la recherche d'un caractère absent de la table (par exemple `č`) le cherche tel quel au lieu de ne rien trouver.

---

## Limites à connaître

- **Performances.** L'expression appliquée à la colonne empêche l'utilisation d'un index : la base parcourt toute la table. C'est sans importance jusqu'à quelques centaines de milliers de lignes. Au-delà, stockez plutôt une colonne déjà normalisée (remplie avec `Normalizer::normalize()`), indexez-la et cherchez dedans.
- **MySQL / MariaDB.** Avec une collation `utf8mb4_*_ci` (le cas par défaut), MySQL ignore déjà certains accents dans `LIKE`. Le paquet donne alors les mêmes résultats, ou un peu plus. Avec une collation `_bin` ou `_cs`, seul le paquet rend la recherche insensible aux accents.
- **Bases supportées.** MySQL, MariaDB, PostgreSQL et SQLite. Une autre base (SQL Server, Oracle) lève une `InvalidArgumentException`.

---

## Développement

```bash
git clone https://github.com/kaveraa/unaccent-search.git
cd unaccent-search
composer install
composer test
```

Par défaut, les tests tournent sur SQLite en mémoire (extension `pdo_sqlite` requise). Pour tester sur les autres bases, démarrez-les avec Docker puis indiquez-les dans `UNACCENT_DB` :

```bash
docker compose up -d
UNACCENT_DB=mysql UNACCENT_DB_PORT=33306 composer test
UNACCENT_DB=mariadb UNACCENT_DB_PORT=33307 composer test
UNACCENT_DB=pgsql UNACCENT_DB_PORT=35432 composer test
```

La CI GitHub Actions lance la suite sur les quatre bases et plusieurs versions de PHP.

Pour proposer une modification (branche, tests, règles, Pull Request), lisez le guide [CONTRIBUTING.md](https://github.com/kaveraa/unaccent-search/blob/main/CONTRIBUTING.md).

Voir le [CHANGELOG](CHANGELOG.md) pour l'historique des versions.

## Licence

MIT. Voir [LICENSE](LICENSE).
