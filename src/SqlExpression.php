<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch;

/**
 * Génère l'expression SQL qui ramène une colonne à la même forme que Normalizer::normalize() :
 *
 *     REPLACE(REPLACE(LOWER(CAST(colonne AS CHAR)), 'à', 'a'), 'é', 'e')...
 *
 * Aucune extension de base de données n'est nécessaire (pas de unaccent, pas de collation
 * particulière) : la même requête fonctionne sur MySQL, MariaDB, PostgreSQL et SQLite.
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
            self::POSTGRESQL, self::SQLITE => "LOWER(CAST({$sql} AS TEXT))",
            default => throw new \InvalidArgumentException(sprintf(
                'Base de données « %s » non supportée (supportées : mysql, mariadb, pgsql, sqlite).',
                $platform,
            )),
        };

        foreach (self::replacementsFor($platform) as $from => $to) {
            $expression = sprintf('REPLACE(%s, %s, %s)', $expression, self::quote($from), self::quote($to));
        }

        return $expression;
    }

    /**
     * @return array<string, string>
     */
    private static function replacementsFor(string $platform): array
    {
        $replacements = Normalizer::replacements();

        // Le LOWER() de SQLite ne traite que l'ASCII : « É » resterait « É ».
        // On ajoute donc aussi les majuscules accentuées à la table.
        if ($platform === self::SQLITE) {
            foreach ($replacements as $from => $to) {
                $upper = mb_strtoupper($from, 'UTF-8');
                if ($upper !== $from && mb_strlen($upper, 'UTF-8') === 1) {
                    $replacements[$upper] = $to;
                }
            }
        }

        return $replacements;
    }

    private static function quote(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
}
