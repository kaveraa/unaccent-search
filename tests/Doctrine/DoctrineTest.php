<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Kaveraa\UnaccentSearch\Doctrine\UnaccentQuery;
use Kaveraa\UnaccentSearch\Mode;
use Kaveraa\UnaccentSearch\Normalizer;
use Kaveraa\UnaccentSearch\Tests\Doctrine\Entity\Product;
use Kaveraa\UnaccentSearch\Tests\Support\Dataset;
use Kaveraa\UnaccentSearch\Tests\Support\TestDatabase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DoctrineTest extends DoctrineTestCase
{
    /**
     * @param list<?string> $expected
     */
    #[DataProvider('searches')]
    public function test_andWhereLike(?string $term, Mode|string $mode, array $expected): void
    {
        $qb = $this->products();
        UnaccentQuery::andWhereLike($qb, 'p.name', $term, $mode);

        self::assertSame($expected, self::names($qb));
    }

    /**
     * @return array<string, array{0: ?string, 1: Mode|string, 2: list<?string>}>
     */
    public static function searches(): array
    {
        return Dataset::searches();
    }

    public function test_fonction_dql_ecrite_a_la_main(): void
    {
        $names = $this->em->createQuery(
            "SELECT p.name FROM ".Product::class." p WHERE UNACCENT(p.name) LIKE :term ESCAPE '!' ORDER BY p.id"
        )
            ->setParameter('term', Normalizer::pattern('ÉLÈVE'))
            ->getSingleColumnResult();

        self::assertSame(['Élève', 'eleve'], $names);
    }

    public function test_unaccent_dans_le_select(): void
    {
        $value = $this->em->createQuery(
            'SELECT UNACCENT(p.name) FROM '.Product::class." p WHERE p.code = 'DES-01'"
        )->getSingleScalarResult();

        self::assertSame('creme brulee', $value);
    }

    public function test_terme_vide_ou_null_ne_filtre_pas(): void
    {
        foreach (['', '   ', null] as $term) {
            $qb = $this->products();
            UnaccentQuery::andWhereLike($qb, 'p.name', $term);
            UnaccentQuery::andWhereAnyLike($qb, ['p.name', 'p.code'], $term);

            self::assertCount(count(Dataset::rows()), self::names($qb));
        }
    }

    public function test_orWhereLike(): void
    {
        $qb = $this->products();
        UnaccentQuery::andWhereLike($qb, 'p.name', 'creme');
        UnaccentQuery::orWhereLike($qb, 'p.name', 'strasse');

        self::assertSame(['Crème brûlée', 'Straße'], self::names($qb));
    }

    public function test_andWhereNotLike(): void
    {
        $qb = $this->products();
        UnaccentQuery::andWhereNotLike($qb, 'p.name', 'COTON');
        $names = self::names($qb);

        self::assertNotContains('100% coton', $names);
        self::assertNotContains('coton_bio', $names);
        self::assertContains('Élève', $names);
    }

    public function test_andWhereAnyLike_cherche_dans_plusieurs_champs(): void
    {
        $qb = $this->products();
        UnaccentQuery::andWhereAnyLike($qb, ['p.name', 'p.code'], 'francais');
        self::assertSame(['Garçon'], self::names($qb));

        // Le groupe OR est bien entre parenthèses : la condition suivante s'applique à tout le groupe
        $qb = $this->products();
        UnaccentQuery::andWhereAnyLike($qb, ['p.name', 'p.code'], 'elv');
        $qb->andWhere('p.code = :code')->setParameter('code', 'ELV-02');
        self::assertSame(['eleve'], self::names($qb));
    }

    public function test_plusieurs_recherches_sur_le_meme_querybuilder(): void
    {
        $qb = $this->products();
        UnaccentQuery::andWhereLike($qb, 'p.name', 'eleve');
        UnaccentQuery::andWhereLike($qb, 'p.code', 'elv-01');

        self::assertSame(['Élève'], self::names($qb));
    }

    public function test_condition_pour_un_usage_libre(): void
    {
        $qb = $this->products();
        $qb->andWhere($qb->expr()->orX(
            UnaccentQuery::condition($qb, 'p.name', 'garcon'),
            'p.code = :code',
        ))->setParameter('code', 'DES-01');

        self::assertSame(['Crème brûlée', 'Garçon'], self::names($qb));
        self::assertNull(UnaccentQuery::condition($qb, 'p.name', ''));
    }

    public function test_remplacements_supplementaires(): void
    {
        // Avec la collation par défaut de MySQL/MariaDB (utf8mb4_*_ci), LIKE ignore déjà les accents :
        // « č » est alors trouvé même sans remplacement supplémentaire.
        if (! in_array(TestDatabase::driver(), ['mysql', 'mariadb'], true)) {
            $qb = $this->products();
            UnaccentQuery::andWhereLike($qb, 'p.name', 'caj');
            self::assertSame([], self::names($qb));
        }

        Normalizer::extend(['č' => 'c']);

        $qb = $this->products();
        UnaccentQuery::andWhereLike($qb, 'p.name', 'caj');
        self::assertSame(['čaj vert'], self::names($qb));
    }

    public function test_champ_invalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UnaccentQuery::andWhereLike($this->products(), 'name) OR 1=1 OR (p.name', 'x');
    }

    private function products(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->orderBy('p.id');
    }

    /**
     * @return list<?string>
     */
    private static function names(QueryBuilder $qb): array
    {
        return array_map(fn (Product $p): ?string => $p->name, $qb->getQuery()->getResult());
    }
}
