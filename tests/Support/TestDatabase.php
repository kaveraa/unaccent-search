<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Support;

/**
 * Base de données utilisée par les tests d'intégration, choisie par variables d'environnement :
 *
 *     UNACCENT_DB=sqlite (défaut, en mémoire) | mysql | mariadb | pgsql
 *     UNACCENT_DB_HOST, UNACCENT_DB_PORT, UNACCENT_DB_DATABASE, UNACCENT_DB_USERNAME, UNACCENT_DB_PASSWORD
 */
final class TestDatabase
{
    public static function driver(): string
    {
        return getenv('UNACCENT_DB') ?: 'sqlite';
    }

    /**
     * @return array{host: string, port: int, database: string, username: string, password: string}
     */
    public static function credentials(): array
    {
        $driver = self::driver();

        return [
            'host' => getenv('UNACCENT_DB_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('UNACCENT_DB_PORT') ?: ($driver === 'pgsql' ? 5432 : 3306)),
            'database' => getenv('UNACCENT_DB_DATABASE') ?: 'unaccent_search',
            'username' => getenv('UNACCENT_DB_USERNAME') ?: ($driver === 'pgsql' ? 'postgres' : 'root'),
            'password' => getenv('UNACCENT_DB_PASSWORD') ?: 'secret',
        ];
    }

    /**
     * Paramètres de connexion Doctrine DBAL.
     *
     * @return array<string, mixed>
     */
    public static function doctrineParams(): array
    {
        $driver = self::driver();

        if ($driver === 'sqlite') {
            return ['driver' => 'pdo_sqlite', 'memory' => true];
        }

        $c = self::credentials();

        return [
            'driver' => $driver === 'pgsql' ? 'pdo_pgsql' : 'pdo_mysql',
            'host' => $c['host'],
            'port' => $c['port'],
            'dbname' => $c['database'],
            'user' => $c['username'],
            'password' => $c['password'],
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
        ];
    }
}
