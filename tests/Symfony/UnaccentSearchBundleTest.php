<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Symfony;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Kaveraa\UnaccentSearch\Doctrine\UnaccentFunction;
use Kaveraa\UnaccentSearch\Doctrine\UnaccentQuery;
use Kaveraa\UnaccentSearch\Normalizer;
use Kaveraa\UnaccentSearch\Tests\Doctrine\DoctrineTestCase;
use Kaveraa\UnaccentSearch\Tests\Doctrine\Entity\Product;
use Kaveraa\UnaccentSearch\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class UnaccentSearchBundleTest extends TestCase
{
    private ?TestKernel $kernel = null;

    protected function tearDown(): void
    {
        if ($this->kernel !== null) {
            (new Filesystem())->remove($this->kernel->getCacheDir());
            $this->kernel->shutdown();
        }
        Normalizer::reset();
    }

    public function test_la_fonction_dql_est_enregistree_automatiquement(): void
    {
        $em = $this->boot(new TestKernel())->getManager();

        self::assertSame(UnaccentFunction::class, $em->getConfiguration()->getCustomStringFunction('UNACCENT'));
        self::assertSame(['Élève', 'eleve'], $this->search($em, 'ELEVE'));
    }

    public function test_le_middleware_sqlite_est_enregistre(): void
    {
        if (TestDatabase::driver() !== 'sqlite') {
            self::markTestSkipped('Concerne uniquement SQLite.');
        }

        $connection = $this->boot(new TestKernel())->getConnection();

        // Requête SQL directe, sans passer par la fonction DQL : seul le middleware a pu enregistrer la fonction
        self::assertSame('eleve', $connection->fetchOne("SELECT unaccent_search('Élève')"));
    }

    public function test_plusieurs_entity_managers(): void
    {
        $registry = $this->boot(new TestKernel(multipleEntityManagers: true));

        foreach (['main', 'archive'] as $name) {
            $em = $registry->getManager($name);
            self::assertInstanceOf(EntityManagerInterface::class, $em);
            self::assertSame(
                UnaccentFunction::class,
                $em->getConfiguration()->getCustomStringFunction('UNACCENT'),
                "entity manager \"{$name}\"",
            );
        }
    }

    public function test_remplacements_de_la_configuration(): void
    {
        $em = $this->boot(new TestKernel(replacements: ['č' => 'c']))->getManager();

        self::assertSame(['čaj vert'], $this->search($em, 'caj'));
    }

    private function boot(TestKernel $kernel): ManagerRegistry
    {
        $this->kernel = $kernel;
        (new Filesystem())->remove($kernel->getCacheDir());
        $kernel->boot();

        /** @var ManagerRegistry $registry */
        $registry = $kernel->getContainer()->get('doctrine');

        return $registry;
    }

    /**
     * @return list<?string>
     */
    private function search(EntityManagerInterface $em, string $term): array
    {
        DoctrineTestCase::seed($em);

        $qb = $em->createQueryBuilder()->select('p')->from(Product::class, 'p')->orderBy('p.id');
        UnaccentQuery::andWhereLike($qb, 'p.name', $term);

        return array_map(fn (Product $p): ?string => $p->name, $qb->getQuery()->getResult());
    }
}
