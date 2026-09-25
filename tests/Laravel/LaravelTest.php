<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Laravel;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kaveraa\UnaccentSearch\Laravel\UnaccentSearchServiceProvider;
use Kaveraa\UnaccentSearch\Mode;
use Kaveraa\UnaccentSearch\Normalizer;
use Kaveraa\UnaccentSearch\Tests\Support\Dataset;
use Kaveraa\UnaccentSearch\Tests\Support\TestDatabase;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class LaravelTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [UnaccentSearchServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $driver = TestDatabase::driver();
        $connection = $driver === 'sqlite'
            ? ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']
            : [
                'driver' => $driver,
                ...TestDatabase::credentials(),
                'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
                'collation' => $driver === 'pgsql' ? null : 'utf8mb4_bin',
                'prefix' => '',
            ];

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $connection);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('products');
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->json('data')->nullable();
        });

        foreach (Dataset::rows() as $row) {
            Product::create($row + ['data' => ['city' => $row['name']]]);
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('products');
        Normalizer::reset();

        parent::tearDown();
    }

    /**
     * @param list<?string> $expected
     */
    #[DataProvider('searches')]
    public function test_whereLikeUnaccent(?string $term, Mode|string $mode, array $expected): void
    {
        $names = Product::whereLikeUnaccent('name', $term, $mode)->orderBy('id')->pluck('name')->all();

        self::assertSame($expected, $names);
    }

    /**
     * @return array<string, array{0: ?string, 1: Mode|string, 2: list<?string>}>
     */
    public static function searches(): array
    {
        return Dataset::searches();
    }

    public function test_fonctionne_sur_le_query_builder(): void
    {
        $names = DB::table('products')->whereLikeUnaccent('products.name', 'ELEVE')->orderBy('id')->pluck('name')->all();

        self::assertSame(['Élève', 'eleve'], $names);
    }

    public function test_terme_vide_ou_null_ne_filtre_pas(): void
    {
        self::assertCount(count(Dataset::rows()), Product::whereLikeUnaccent('name', '')->get());
        self::assertCount(count(Dataset::rows()), Product::whereLikeUnaccent('name', '   ')->get());
        self::assertCount(count(Dataset::rows()), Product::whereLikeUnaccent('name', null)->get());
        self::assertCount(count(Dataset::rows()), Product::whereAnyLikeUnaccent(['name', 'code'], null)->get());
    }

    public function test_orWhereLikeUnaccent(): void
    {
        $names = Product::whereLikeUnaccent('name', 'creme')
            ->orWhereLikeUnaccent('name', 'strasse')
            ->orderBy('id')
            ->pluck('name')
            ->all();

        self::assertSame(['Crème brûlée', 'Straße'], $names);
    }

    public function test_whereNotLikeUnaccent(): void
    {
        $names = Product::whereNotLikeUnaccent('name', 'COTON')->pluck('name')->all();

        self::assertNotContains('100% coton', $names);
        self::assertNotContains('coton_bio', $names);
        self::assertContains('Élève', $names);
    }

    public function test_whereAnyLikeUnaccent_cherche_dans_plusieurs_colonnes(): void
    {
        // "francais" est dans code (Français), "eleve" dans name
        $names = Product::whereAnyLikeUnaccent(['name', 'code'], 'francais')->pluck('name')->all();
        self::assertSame(['Garçon'], $names);

        // Le groupe OR est bien entre parenthèses : le where suivant s'applique à tout le groupe
        $names = Product::whereAnyLikeUnaccent(['name', 'code'], 'elv')->where('code', 'ELV-02')->pluck('name')->all();
        self::assertSame(['eleve'], $names);
    }

    public function test_colonne_json(): void
    {
        $names = Product::whereLikeUnaccent('data->city', 'creme')->pluck('name')->all();

        self::assertSame(['Crème brûlée'], $names);
    }

    public function test_expression_brute(): void
    {
        $concat = TestDatabase::driver() === 'sqlite' || TestDatabase::driver() === 'pgsql'
            ? DB::raw("name || ' ' || code")
            : DB::raw("CONCAT(name, ' ', code)");

        $names = Product::whereLikeUnaccent($concat, 'garcon francais')->pluck('name')->all();

        self::assertSame(['Garçon'], $names);
    }

    public function test_sous_requete_et_relation(): void
    {
        $count = Product::whereIn('id', function (Builder $query): void {
            $query->select('id')->from('products')->whereLikeUnaccent('name', 'eleve');
        })->count();

        self::assertSame(2, $count);
    }

    public function test_remplacements_de_la_config(): void
    {
        self::assertSame([], Product::whereLikeUnaccent('name', 'caj')->pluck('name')->all());

        Normalizer::extend(['č' => 'c']);

        self::assertSame(['čaj vert'], Product::whereLikeUnaccent('name', 'caj')->pluck('name')->all());
    }

    public function test_nom_de_colonne_invalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Product::whereLikeUnaccent('name; DROP TABLE products', 'x')->get();
    }

    public function test_le_terme_est_lie_en_parametre(): void
    {
        $query = Product::whereLikeUnaccent('name', "x' OR 1=1 --");

        self::assertStringNotContainsString('1=1', $query->toSql());
        self::assertSame([], $query->pluck('name')->all());
    }
}
