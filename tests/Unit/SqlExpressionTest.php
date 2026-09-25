<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Unit;

use Kaveraa\UnaccentSearch\Mode;
use Kaveraa\UnaccentSearch\Normalizer;
use Kaveraa\UnaccentSearch\SqlExpression;
use PHPUnit\Framework\TestCase;

final class SqlExpressionTest extends TestCase
{
    protected function tearDown(): void
    {
        Normalizer::reset();
    }

    public function test_mysql_utilise_cast_char(): void
    {
        $sql = SqlExpression::wrap('`name`', SqlExpression::MYSQL);

        self::assertStringContainsString('LOWER(CAST(`name` AS CHAR))', $sql);
        self::assertStringContainsString("'é', 'e'", $sql);
        self::assertSame(count(Normalizer::DEFAULT_REPLACEMENTS), substr_count($sql, 'REPLACE('));
    }

    public function test_postgresql_utilise_cast_text(): void
    {
        self::assertStringContainsString('LOWER(CAST("name" AS TEXT))', SqlExpression::wrap('"name"', SqlExpression::POSTGRESQL));
    }

    public function test_sqlite_ajoute_les_majuscules_accentuees(): void
    {
        $sql = SqlExpression::wrap('"name"', SqlExpression::SQLITE);

        self::assertStringContainsString("'É', 'e'", $sql);
        self::assertStringContainsString("'Œ', 'oe'", $sql);
    }

    public function test_les_apostrophes_sont_echappees(): void
    {
        // Apostrophe typographique (U+2019) remplacée par une apostrophe simple
        Normalizer::extend(["\u{2019}" => "'"]);

        self::assertStringContainsString("'\u{2019}', ''''", SqlExpression::wrap('`name`', SqlExpression::MYSQL));
    }

    public function test_base_non_supportee(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('sqlsrv');

        SqlExpression::wrap('[name]', 'sqlsrv');
    }

    public function test_mode_resolve_accepte_les_alias_de_datatable(): void
    {
        self::assertSame(Mode::Contains, Mode::resolve('contain'));
        self::assertSame(Mode::StartsWith, Mode::resolve('start_with'));
        self::assertSame(Mode::EndsWith, Mode::resolve('end_with'));
        self::assertSame(Mode::Exact, Mode::resolve('equal'));
        self::assertSame(Mode::Exact, Mode::resolve(Mode::Exact));

        $this->expectException(\ValueError::class);
        Mode::resolve('nimporte');
    }
}
