<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch;

/**
 * Enregistre la fonction SQL unaccent_search() sur une connexion SQLite.
 *
 * SQLite n'accepte pas beaucoup de REPLACE() imbriqués (erreur "parser stack overflow"
 * avant la version 3.46) : sous SQLite, la normalisation est donc faite par une fonction
 * PHP, qui appelle Normalizer::normalize().
 *
 * Les ponts Laravel et Doctrine appellent register() automatiquement. Pour une connexion
 * PDO utilisée à la main :
 *
 *     SqliteFunction::register($pdo);
 */
final class SqliteFunction
{
    public const NAME = 'unaccent_search';

    /** @var \WeakMap<object, true>|null */
    private static ?\WeakMap $registered = null;

    /**
     * @param object $connection \PDO (driver sqlite), \Pdo\Sqlite ou \SQLite3
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
            // Connexion créée avec "new PDO()" (Laravel, Doctrine) : seule la méthode historique existe.
            // Elle est dépréciée depuis PHP 8.5 sans alternative pour ce type d'objet : l'avertissement est masqué.
            @$connection->sqliteCreateFunction(self::NAME, $callback, 1, $deterministic);
        } elseif ($connection instanceof \SQLite3) {
            $connection->createFunction(self::NAME, $callback, 1, \SQLITE3_DETERMINISTIC);
        } else {
            throw new \InvalidArgumentException(sprintf('Connexion SQLite non supportée : %s', $connection::class));
        }

        self::$registered[$connection] = true;
    }
}
