<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch;

/**
 * Builds the SQL expression that turns a column into the same form as Normalizer::normalize():
 *
 *     REPLACE(REPLACE(LOWER(CAST(column AS CHAR)), 'à', 'a'), 'é', 'e')...
 *
 * No database extension is needed (no unaccent, no special collation)
 * on MySQL, MariaDB and PostgreSQL. On SQLite, the expression calls the
 * unaccent_search() function, to register on the connection with SqliteFunction::register().
 */
final class SqlExpression
{
    public const MYSQL = 'mysql';
    public const MARIADB = 'mariadb';
    public const POSTGRESQL = 'pgsql';
    public const SQLITE = 'sqlite';

    /**
     * @param string $sql      SQL fragment already escaped (quoted column, expression...).
     *                         It is inserted as it is: it must never come from the user.
     * @param string $platform one of the constants of this class
     */
    public static function wrap(string $sql, string $platform): string
    {
        $expression = match ($platform) {
            self::MYSQL, self::MARIADB => "LOWER(CAST({$sql} AS CHAR))",
            self::POSTGRESQL => "LOWER(CAST({$sql} AS TEXT))",
            // Too many nested REPLACE() make SQLite fail: a PHP function does the work
            self::SQLITE => SqliteFunction::NAME."(CAST({$sql} AS TEXT))",
            default => throw new \InvalidArgumentException(sprintf(
                'Base de données "%s" non supportée (supportées : mysql, mariadb, pgsql, sqlite).',
                $platform,
            )),
        };

        if ($platform === self::SQLITE) {
            return $expression;
        }

        foreach (Normalizer::replacements() as $from => $to) {
            $expression = sprintf('REPLACE(%s, %s, %s)', $expression, self::quote($from), self::quote($to));
        }

        return $expression;
    }

    private static function quote(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
}
