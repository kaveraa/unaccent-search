<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Unit;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Kaveraa\UnaccentSearch\Doctrine\SqliteMiddleware;
use Kaveraa\UnaccentSearch\Normalizer;
use Kaveraa\UnaccentSearch\SqliteFunction;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
final class SqliteFunctionTest extends TestCase
{
    protected function tearDown(): void
    {
        Normalizer::reset();
    }

    public function test_fonction_sur_pdo(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        SqliteFunction::register($pdo);
        SqliteFunction::register($pdo); // un second appel ne fait rien

        self::assertSame('creme brulee', $pdo->query("SELECT unaccent_search('Crème BRÛLÉE')")->fetchColumn());
        self::assertSame('ečole', $pdo->query("SELECT unaccent_search('ÉČOLE')")->fetchColumn(), 'majuscule hors table mise en minuscule');
        self::assertNull($pdo->query('SELECT unaccent_search(NULL)')->fetchColumn());
    }

    public function test_fonction_utilise_les_remplacements_ajoutes(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        SqliteFunction::register($pdo);
        Normalizer::extend(['č' => 'c']);

        self::assertSame('ecole', $pdo->query("SELECT unaccent_search('ÉČOLE')")->fetchColumn());
    }

    #[RequiresPhpExtension('sqlite3')]
    public function test_fonction_sur_sqlite3(): void
    {
        $db = new \SQLite3(':memory:');
        SqliteFunction::register($db);

        self::assertSame('eleve', $db->querySingle("SELECT unaccent_search('Élève')"));
    }

    public function test_connexion_non_supportee(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SqliteFunction::register(new \stdClass());
    }

    public function test_middleware_doctrine(): void
    {
        $config = (new Configuration())->setMiddlewares([new SqliteMiddleware()]);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);

        self::assertSame('eleve', $connection->fetchOne("SELECT unaccent_search('Élève')"));
    }
}
