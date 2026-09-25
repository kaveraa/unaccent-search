<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch;

/**
 * Génère l'expression SQL qui ramène une colonne à la même forme que Normalizer::normalize() :
 *
 *     REPLACE(REPLACE(LOWER(CAST(colonne AS CHAR)), 'à', 'a'), 'é', 'e')...
 *
 * Aucune extension de base de données n'est nécessaire (pas de unaccent, pas de collation
 * particulière) sur MySQL, MariaDB et PostgreSQL. Sous SQLite, l'expression appelle la
 * fonction unaccent_search(), à enregistrer sur la connexion avec SqliteFunction::register().
 */
final class SqlExpression
{
    public const MYSQL = 'mysql';
    public const MARIADB = 'mariadb';
    public const POSTGRESQL = 'pgsql';
    public const SQLITE = 'sqlite';

    /**
     * @param string $sql      fragment SQL déjà échappé (colonne entre guillemets, expression...).
     *                         Il est inséré tel quel : il ne doit jamais venir de l'utilisateur.
     * @param string $platform une des constantes de cette classe
     */
    public static function wrap(string $sql, string $platform): string
    {
        $expression = match ($platform) {
            self::MYSQL, self::MARIADB => "LOWER(CAST({$sql} AS CHAR))",
            self::POSTGRESQL => "LOWER(CAST({$sql} AS TEXT))",
            // Trop de REPLACE() imbriqués font échouer SQLite : une fonction PHP fait le travail
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
