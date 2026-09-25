<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Kaveraa\UnaccentSearch\Mode;
use Kaveraa\UnaccentSearch\Normalizer;

/**
 * Ajoute à un QueryBuilder Doctrine des conditions de recherche insensibles
 * à la casse et aux accents, sans écrire le DQL à la main :
 *
 *     $qb = $repository->createQueryBuilder('p');
 *     UnaccentQuery::andWhereLike($qb, 'p.name', $search);
 *     UnaccentQuery::andWhereAnyLike($qb, ['p.name', 'p.code'], $search);
 *
 * Un terme vide ou null n'ajoute aucune condition. Nécessite la fonction DQL
 * UNACCENT (enregistrée automatiquement par UnaccentSearchBundle).
 */
final class UnaccentQuery
{
    /**
     * Champ DQL accepté : "alias.champ" ou "alias.embedded.champ".
     */
    private const FIELD_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)+$/';

    private static int $parameterCounter = 0;

    /**
     * Ajoute "AND champ ressemble au terme".
     */
    public static function andWhereLike(QueryBuilder $qb, string $field, ?string $term, Mode|string $mode = Mode::Contains): QueryBuilder
    {
        $condition = self::condition($qb, $field, $term, $mode, false);

        return $condition === null ? $qb : $qb->andWhere($condition);
    }

    /**
     * Ajoute "OR champ ressemble au terme".
     */
    public static function orWhereLike(QueryBuilder $qb, string $field, ?string $term, Mode|string $mode = Mode::Contains): QueryBuilder
    {
        $condition = self::condition($qb, $field, $term, $mode, false);

        return $condition === null ? $qb : $qb->orWhere($condition);
    }

    /**
     * Ajoute "AND champ ne ressemble pas au terme".
     */
    public static function andWhereNotLike(QueryBuilder $qb, string $field, ?string $term, Mode|string $mode = Mode::Contains): QueryBuilder
    {
        $condition = self::condition($qb, $field, $term, $mode, true);

        return $condition === null ? $qb : $qb->andWhere($condition);
    }

    /**
     * Ajoute "AND (champ1 ressemble au terme OR champ2 ressemble au terme ...)" :
     * idéal pour un champ de recherche global.
     *
     * @param list<string> $fields
     */
    public static function andWhereAnyLike(QueryBuilder $qb, array $fields, ?string $term, Mode|string $mode = Mode::Contains): QueryBuilder
    {
        if (self::isBlank($term) || $fields === []) {
            return $qb;
        }

        $parameter = self::bindPattern($qb, $term, $mode);
        $conditions = array_map(
            fn (string $field): string => self::dql($field, $parameter, false),
            $fields,
        );

        return $qb->andWhere($qb->expr()->orX(...$conditions));
    }

    /**
     * Construit la condition DQL et lie son paramètre au QueryBuilder, pour un usage libre
     * (dans un orX() maison, un having...). Retourne null si le terme est vide.
     */
    public static function condition(QueryBuilder $qb, string $field, ?string $term, Mode|string $mode = Mode::Contains, bool $not = false): ?string
    {
        if (self::isBlank($term)) {
            return null;
        }

        return self::dql($field, self::bindPattern($qb, $term, $mode), $not);
    }

    private static function dql(string $field, string $parameter, bool $not): string
    {
        if (! preg_match(self::FIELD_PATTERN, $field)) {
            throw new \InvalidArgumentException("Champ DQL invalide : {$field} (attendu : alias.champ)");
        }

        return sprintf(
            "%s(%s) %sLIKE :%s ESCAPE '%s'",
            UnaccentFunction::NAME,
            $field,
            $not ? 'NOT ' : '',
            $parameter,
            Normalizer::LIKE_ESCAPE,
        );
    }

    private static function bindPattern(QueryBuilder $qb, string $term, Mode|string $mode): string
    {
        $parameter = 'unaccent_'.++self::$parameterCounter;
        $qb->setParameter($parameter, Normalizer::pattern($term, $mode));

        return $parameter;
    }

    private static function isBlank(?string $term): bool
    {
        return $term === null || trim($term) === '';
    }
}
