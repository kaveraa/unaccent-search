<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Kaveraa\UnaccentSearch\Mode;
use Kaveraa\UnaccentSearch\Normalizer;

/**
 * Adds search conditions to a Doctrine QueryBuilder that ignore case
 * and accents, without writing the DQL by hand:
 *
 *     $qb = $repository->createQueryBuilder('p');
 *     UnaccentQuery::andWhereLike($qb, 'p.name', $search);
 *     UnaccentQuery::andWhereAnyLike($qb, ['p.name', 'p.code'], $search);
 *
 * An empty or null term adds no condition. Needs the DQL function
 * UNACCENT (registered automatically by UnaccentSearchBundle).
 */
final class UnaccentQuery
{
    /**
     * Accepted DQL field: "alias.field" or "alias.embedded.field".
     */
    private const FIELD_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)+$/';

    private static int $parameterCounter = 0;

    /**
     * Adds "AND field looks like the term".
     */
    public static function andWhereLike(QueryBuilder $qb, string $field, ?string $term, Mode|string $mode = Mode::Contains): QueryBuilder
    {
        $condition = self::condition($qb, $field, $term, $mode, false);

        return $condition === null ? $qb : $qb->andWhere($condition);
    }

    /**
     * Adds "OR field looks like the term".
     */
    public static function orWhereLike(QueryBuilder $qb, string $field, ?string $term, Mode|string $mode = Mode::Contains): QueryBuilder
    {
        $condition = self::condition($qb, $field, $term, $mode, false);

        return $condition === null ? $qb : $qb->orWhere($condition);
    }

    /**
     * Adds "AND field does not look like the term".
     */
    public static function andWhereNotLike(QueryBuilder $qb, string $field, ?string $term, Mode|string $mode = Mode::Contains): QueryBuilder
    {
        $condition = self::condition($qb, $field, $term, $mode, true);

        return $condition === null ? $qb : $qb->andWhere($condition);
    }

    /**
     * Adds "AND (field1 looks like the term OR field2 looks like the term ...)":
     * ideal for a global search box.
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
     * Builds the DQL condition and binds its parameter to the QueryBuilder, for free use
     * (in your own orX(), a having...). Returns null if the term is empty.
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
