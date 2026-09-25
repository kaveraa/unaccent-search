# Unaccent Search

[![Tests](https://github.com/kaveraa/unaccent-search/actions/workflows/tests.yml/badge.svg)](https://github.com/kaveraa/unaccent-search/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/kaveraa/unaccent-search.svg)](https://packagist.org/packages/kaveraa/unaccent-search)
[![License](https://img.shields.io/packagist/l/kaveraa/unaccent-search.svg)](LICENSE)

[Français](https://github.com/kaveraa/unaccent-search/blob/main/README.md) - **English**

SQL search **without accents and without case** for **Laravel** and **Symfony / Doctrine**.

When a user types `eleve`, the search finds `Élève`, `ÉLÈVE` and `élève`. `creme brulee` finds `Crème brûlée`. `oeuvre` finds `Œuvre`.

```php
// Laravel
Product::whereLikeUnaccent('name', $request->search)->get();

// Symfony / Doctrine
UnaccentQuery::andWhereLike($qb, 'p.name', $search);
```

- **No database extension**: you do not need `unaccent` or a special collation.
- **MySQL, MariaDB, PostgreSQL and SQLite**: the same code works on all of them.
- **Easy to install**: with Laravel, `composer require` is enough. With Symfony, add one line in `bundles.php`.
- **Secure**: the search term is always a bound parameter, column names are checked, and `%` and `_` typed by the user are searched as normal characters.
- **An empty search does not filter**: you can pass `$request->search` directly, even when it is `null`.

---

## Contents

- [Installation](#installation)
- [Laravel](#laravel)
- [Symfony](#symfony)
- [Doctrine without Symfony](#doctrine-without-symfony)
- [Search modes](#search-modes)
- [Add characters](#add-characters)
- [Use the normalizer alone](#use-the-normalizer-alone)
- [How it works](#how-it-works)
- [Limits](#limits)
- [Development](#development)

## Installation

```bash
composer require kaveraa/unaccent-search
```

Requirements: PHP 8.2 or more, with the `mbstring` extension.

| Framework | Supported versions |
|---|---|
| Laravel | 11, 12 |
| Symfony | 6.4, 7.x, 8.x (with DoctrineBundle 2.13+ or 3.x) |
| Doctrine ORM | 3.x (DBAL 4) |

---

## Laravel

### Setup

Nothing to do. Laravel finds the service provider automatically.

### Search in one column

```php
use App\Models\Product;

$products = Product::whereLikeUnaccent('name', $request->input('search'))->get();
```

The methods are added to the **Query Builder**. So they also work with Eloquent models, relations, sub-queries and `DB::table()`:

```php
DB::table('products')->whereLikeUnaccent('products.name', 'eleve')->get();

$user->orders()->whereLikeUnaccent('reference', $search)->paginate();
```

### Search in several columns (global search)

`whereAnyLikeUnaccent` finds the rows where **at least one** column matches. The conditions are grouped in parentheses, so the next `where` applies to the whole group:

```php
Product::whereAnyLikeUnaccent(['name', 'commercial_name', 'code'], $request->search)
    ->where('active', true)   // (name LIKE ... OR commercial_name LIKE ... OR code LIKE ...) AND active = 1
    ->paginate();
```

### All methods

| Method | Condition added |
|---|---|
| `whereLikeUnaccent($column, $term, $mode = Mode::Contains)` | `AND column matches the term` |
| `orWhereLikeUnaccent($column, $term, $mode)` | `OR column matches the term` |
| `whereNotLikeUnaccent($column, $term, $mode)` | `AND column does not match the term` |
| `orWhereNotLikeUnaccent($column, $term, $mode)` | `OR column does not match the term` |
| `whereAnyLikeUnaccent([$col1, $col2], $term, $mode)` | `AND (col1 matches OR col2 matches ...)` |
| `orWhereAnyLikeUnaccent([$col1, $col2], $term, $mode)` | `OR (col1 matches OR col2 matches ...)` |

`$mode` says how the term must match: *contains* (default), *starts with*, *ends with* or *equal*. See [search modes](#search-modes).

### Accepted columns

```php
->whereLikeUnaccent('name', $term)                  // column
->whereLikeUnaccent('products.name', $term)         // table.column (useful with joins)
->whereLikeUnaccent('data->address->city', $term)   // key in a JSON column
->whereLikeUnaccent(DB::raw("CONCAT(first_name, ' ', last_name)"), $term) // SQL expression
```

> **Warning:** a `DB::raw()` expression is put in the query as it is: never put user input in it. Text column names are checked. An invalid name throws an `InvalidArgumentException`.

### Example: datatable filters

The old datatable conditions (`contain`, `start_with`, `end_with`, `equal`) are accepted as modes:

```php
public function scopeFilterString(Builder $query, array $search): void
{
    match ($search['condition']) {
        'contain', 'start_with', 'end_with', 'equal'
            => $query->whereLikeUnaccent($search['field'], $search['value'], $search['condition']),
        'not_contain'
            => $query->whereNotLikeUnaccent($search['field'], $search['value']),
        'not_equal'
            => $query->whereNotLikeUnaccent($search['field'], $search['value'], Mode::Exact),
        'is_null' => $query->whereNull($search['field']),
        'is_not_null' => $query->whereNotNull($search['field']),
        default => null,
    };
}
```

### Configuration (optional)

To [add characters](#add-characters) to the replacement table:

```bash
php artisan vendor:publish --tag=unaccent-search-config
```

```php
// config/unaccent-search.php
return [
    'replacements' => ['ł' => 'l', 'š' => 's'],
];
```

### Auto-completion in your IDE

The methods are found by [Laravel Idea](https://laravel-idea.com/) and by [barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) (`php artisan ide-helper:generate`).

---

## Symfony

### Setup

Add the bundle in `config/bundles.php`:

```php
return [
    // ...
    Kaveraa\UnaccentSearch\Symfony\UnaccentSearchBundle::class => ['all' => true],
];
```

That is all. The bundle adds the DQL function `UNACCENT` to Doctrine. You do not need to change `doctrine.yaml`, even with several entity managers.

### Search in a repository

The `UnaccentQuery` class adds the conditions to a Doctrine `QueryBuilder`:

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

### All methods

| Method | Condition added |
|---|---|
| `UnaccentQuery::andWhereLike($qb, 'p.field', $term, $mode = Mode::Contains)` | `AND field matches the term` |
| `UnaccentQuery::orWhereLike($qb, 'p.field', $term, $mode)` | `OR field matches the term` |
| `UnaccentQuery::andWhereNotLike($qb, 'p.field', $term, $mode)` | `AND field does not match the term` |
| `UnaccentQuery::andWhereAnyLike($qb, ['p.a', 'p.b'], $term, $mode)` | `AND (a matches OR b matches ...)` |
| `UnaccentQuery::condition($qb, 'p.field', $term, $mode, $not = false)` | Returns the DQL condition (or `null` if the term is empty), to use it as you want |

The parameters are bound automatically, with unique names. So you can call several methods on the same `QueryBuilder`.

With `condition()`, you can build your own expressions:

```php
$qb->andWhere($qb->expr()->orX(
    UnaccentQuery::condition($qb, 'p.name', $search),
    'p.reference = :ref',
))->setParameter('ref', $search);
```

### Write DQL yourself

You can use the `UNACCENT()` function anywhere in DQL. Build the parameter with `Normalizer::pattern()` and keep `ESCAPE '!'`:

```php
use Kaveraa\UnaccentSearch\Normalizer;

$em->createQuery("SELECT p FROM App\Entity\Product p WHERE UNACCENT(p.name) LIKE :term ESCAPE '!'")
   ->setParameter('term', Normalizer::pattern($search))
   ->getResult();
```

### Configuration (optional)

```yaml
# config/packages/unaccent_search.yaml
unaccent_search:
    replacements:
        'ł': 'l'
        'š': 's'
```

---

## Doctrine without Symfony

Add the DQL function to the ORM configuration, then use `UnaccentQuery` like above:

```php
use Kaveraa\UnaccentSearch\Doctrine\UnaccentFunction;

$config->addCustomStringFunction(UnaccentFunction::NAME, UnaccentFunction::class);
```

With SQLite and a Doctrine query cache, also add the middleware. It prepares each SQLite connection (it does nothing on other databases):

```php
use Kaveraa\UnaccentSearch\Doctrine\SqliteMiddleware;

$config->setMiddlewares([new SqliteMiddleware()]);
```

---

## Search modes

You can give the mode as a `Kaveraa\UnaccentSearch\Mode` enum or as text.

| Mode | Accepted text | Pattern for `Élève` | Finds |
|---|---|---|---|
| `Mode::Contains` *(default)* | `contains`, `contain` | `%eleve%` | "Un **élève** motivé" |
| `Mode::StartsWith` | `starts_with`, `start_with` | `eleve%` | "**Élève**s de CM2" |
| `Mode::EndsWith` | `ends_with`, `end_with` | `%eleve` | "Nouvel **élève**" |
| `Mode::Exact` | `exact`, `equal` | `eleve` | "**ÉLÈVE**" only |

```php
use Kaveraa\UnaccentSearch\Mode;

Product::whereLikeUnaccent('name', 'cre', Mode::StartsWith)->get();
Product::whereLikeUnaccent('name', 'cre', 'starts_with')->get(); // same result
```

---

## Add characters

The default table covers French and the most common European accents:

| Characters | Become |
|---|---|
| à â ä á ã å | a |
| é è ê ë | e |
| ï î ì í | i |
| ô ö ò ó õ ø | o |
| ù û ü ú | u |
| ÿ ý | y |
| ç / ñ / đ | c / n / d |
| œ / æ / ß | oe / ae / ss |

Upper case letters work automatically: `É` is first changed to lower case, then replaced.

For other alphabets, add entries with the configuration ([Laravel](#configuration-optional), [Symfony](#configuration-optional-1)) or directly in PHP, **when the application starts**:

```php
use Kaveraa\UnaccentSearch\Normalizer;

Normalizer::extend(['ł' => 'l', 'š' => 's', 'č' => 'c']);
```

With MySQL, MariaDB and PostgreSQL, each entry adds one `REPLACE()` to the query. Only add the characters that are in your data.

---

## Use the normalizer alone

The core of the package does not need any framework:

```php
use Kaveraa\UnaccentSearch\Normalizer;
use Kaveraa\UnaccentSearch\Mode;

Normalizer::normalize('Crème Brûlée');          // 'creme brulee'
Normalizer::pattern('Élève');                    // '%eleve%'  (escaped LIKE pattern)
Normalizer::pattern('Élève', Mode::StartsWith);  // 'eleve%'
Normalizer::pattern('100%');                     // '%100!%%'  (the % is searched as a normal character)
```

`SqlExpression::wrap()` gives the normalized SQL expression of a column for one database. Use it if you write your queries yourself:

```php
use Kaveraa\UnaccentSearch\SqlExpression;

$sql = SqlExpression::wrap('`name`', SqlExpression::MYSQL);
// REPLACE(REPLACE(... LOWER(CAST(`name` AS CHAR)) ..., 'à', 'a') ...)

$pdo->prepare("SELECT * FROM products WHERE {$sql} LIKE ? ESCAPE '!'")
    ->execute([Normalizer::pattern($search)]);
```

With SQLite, the expression calls the `unaccent_search()` function. Register it once on your connection (the Laravel and Doctrine parts of the package do it for you):

```php
use Kaveraa\UnaccentSearch\SqliteFunction;

SqliteFunction::register($pdo); // PDO, Pdo\Sqlite or SQLite3
```

---

## How it works

The search compares two values that are normalized **in the same way**:

1. **In PHP**, the search term is changed to lower case, the accents are replaced, and the `%` and `_` characters are escaped: `Élève` becomes `%eleve%`.
2. **In SQL**, the column goes through the same replacement table: `REPLACE(REPLACE(LOWER(name), 'é', 'e'), 'è', 'e')...`. SQLite does not accept too many nested `REPLACE()`, so with SQLite a PHP function `unaccent_search()` does the same work. It is registered on the connection automatically.
3. The comparison uses `LIKE ? ESCAPE '!'`. The term is always a bound parameter.

The same table is used on both sides. So if a character is not in the table (for example `č`), it is searched as it is, and the search still finds it.

---

## Limits

- **Performance.** The expression on the column blocks the use of an index: the database reads the whole table. This is not a problem up to a few hundred thousand rows. For bigger tables, store a normalized column (filled with `Normalizer::normalize()`), add an index on it, and search in it.
- **MySQL / MariaDB.** With a `utf8mb4_*_ci` collation (the default), MySQL already ignores some accents in `LIKE`. The package then gives the same results, or a few more. With a `_bin` or `_cs` collation, only the package makes the search ignore accents.
- **Supported databases.** MySQL, MariaDB, PostgreSQL and SQLite. Another database (SQL Server, Oracle) throws an `InvalidArgumentException`.

---

## Development

```bash
git clone https://github.com/kaveraa/unaccent-search.git
cd unaccent-search
composer install
composer test
```

By default, the tests use SQLite in memory (you need the `pdo_sqlite` extension). To test the other databases, start them with Docker, then choose one with `UNACCENT_DB`:

```bash
docker compose up -d
UNACCENT_DB=mysql UNACCENT_DB_PORT=33306 composer test
UNACCENT_DB=mariadb UNACCENT_DB_PORT=33307 composer test
UNACCENT_DB=pgsql UNACCENT_DB_PORT=35432 composer test
```

GitHub Actions runs the tests on the four databases and on several PHP versions.

See the [CHANGELOG](CHANGELOG.md) for the list of versions.

## License

MIT. See [LICENSE](LICENSE).
