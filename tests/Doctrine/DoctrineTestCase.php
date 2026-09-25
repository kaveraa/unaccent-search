<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Kaveraa\UnaccentSearch\Doctrine\UnaccentFunction;
use Kaveraa\UnaccentSearch\Normalizer;
use Kaveraa\UnaccentSearch\Tests\Doctrine\Entity\Product;
use Kaveraa\UnaccentSearch\Tests\Support\Dataset;
use Kaveraa\UnaccentSearch\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

abstract class DoctrineTestCase extends TestCase
{
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/Entity'], true);
        if (method_exists($config, 'enableNativeLazyObjects') && \PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        }
        $config->addCustomStringFunction(UnaccentFunction::NAME, UnaccentFunction::class);

        $this->em = new EntityManager(DriverManager::getConnection(TestDatabase::doctrineParams(), $config), $config);

        self::seed($this->em);
    }

    protected function tearDown(): void
    {
        (new SchemaTool($this->em))->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
        $this->em->close();
        Normalizer::reset();
    }

    /**
     * Crée la table products et y insère le jeu de données commun.
     */
    public static function seed(EntityManagerInterface $em): void
    {
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        foreach (Dataset::rows() as $row) {
            $em->persist(new Product($row['name'], $row['code']));
        }
        $em->flush();
        $em->clear();
    }
}
