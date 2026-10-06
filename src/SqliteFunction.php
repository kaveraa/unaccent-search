<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch;

/**
 * Registers the SQL function unaccent_search() on a SQLite connection.
 *
 * SQLite does not accept many nested REPLACE() (error "parser stack overflow"
 * before version 3.46): on SQLite, the normalization is therefore done by a PHP
 * function, which calls Normalizer::normalize().
 *
 * The Laravel and Doctrine bridges call register() automatically. For a PDO
 * connection used by hand:
 *
 *     SqliteFunction::register($pdo);
 */
final class SqliteFunction
{
    public const NAME = 'unaccent_search';

    /** @var \WeakMap<object, true>|null */
    private static ?\WeakMap $registered = null;

    /**
     * @param object $connection \PDO (sqlite driver), \Pdo\Sqlite or \SQLite3
     */
    public static function register(object $connection): void
    {
        self::$registered ??= new \WeakMap();
        if (isset(self::$registered[$connection])) {
            return;
        }

        $callback = static fn (mixed $value): ?string => $value === null ? null : Normalizer::normalize((string) $value);

        $modernPdo = class_exists(\Pdo\Sqlite::class);
        $deterministic = $modernPdo ? \Pdo\Sqlite::DETERMINISTIC : \PDO::SQLITE_DETERMINISTIC;

        if ($modernPdo && $connection instanceof \Pdo\Sqlite) {
            $connection->createFunction(self::NAME, $callback, 1, $deterministic);
        } elseif ($connection instanceof \PDO) {
            // Connection created with "new PDO()" (Laravel, Doctrine): only the old method exists.
            // It is deprecated since PHP 8.5 with no alternative for this kind of object: the warning is hidden.
            @$connection->sqliteCreateFunction(self::NAME, $callback, 1, $deterministic);
        } elseif ($connection instanceof \SQLite3) {
            $connection->createFunction(self::NAME, $callback, 1, \SQLITE3_DETERMINISTIC);
        } else {
            throw new \InvalidArgumentException(sprintf('Connexion SQLite non supportée : %s', $connection::class));
        }

        self::$registered[$connection] = true;
    }
}
