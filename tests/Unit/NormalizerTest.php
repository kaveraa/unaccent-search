<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Unit;

use Kaveraa\UnaccentSearch\Mode;
use Kaveraa\UnaccentSearch\Normalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NormalizerTest extends TestCase
{
    protected function tearDown(): void
    {
        Normalizer::reset();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function normalizations(): array
    {
        return [
            'accents français' => ['àâäéèêëïîôöùûüÿç', 'aaaeeeeiioouuuyc'],
            'majuscules accentuées' => ['ÀÉÈÊÎÔÛÇ', 'aeeeiouc'],
            'ligatures' => ['Œuvre Æther', 'oeuvre aether'],
            'eszett' => ['Straße', 'strasse'],
            'texte déjà normalisé' => ['hello world', 'hello world'],
            'caractère hors table conservé' => ['Łódź', 'łodź'],
            'chaîne vide' => ['', ''],
        ];
    }

    #[DataProvider('normalizations')]
    public function test_normalize(string $input, string $expected): void
    {
        self::assertSame($expected, Normalizer::normalize($input));
    }

    public function test_pattern_selon_le_mode(): void
    {
        self::assertSame('%eleve%', Normalizer::pattern('Élève'));
        self::assertSame('eleve%', Normalizer::pattern('Élève', Mode::StartsWith));
        self::assertSame('%eleve', Normalizer::pattern('Élève', Mode::EndsWith));
        self::assertSame('eleve', Normalizer::pattern('Élève', Mode::Exact));
        self::assertSame('eleve%', Normalizer::pattern('Élève', 'starts_with'));
    }

    public function test_pattern_echappe_les_jokers_like(): void
    {
        self::assertSame('%100!%%', Normalizer::pattern('100%'));
        self::assertSame('%a!_b%', Normalizer::pattern('a_b'));
        self::assertSame('%wow!!%', Normalizer::pattern('wow!'));
    }

    public function test_extend_ajoute_des_remplacements(): void
    {
        Normalizer::extend(['Ł' => 'l', 'ź' => 'z']);

        self::assertSame('lodz', Normalizer::normalize('Łódź'));
        self::assertArrayHasKey('ł', Normalizer::replacements(), 'les clés sont mises en minuscules');
    }

    public function test_reset_revient_a_la_table_par_defaut(): void
    {
        Normalizer::extend(['ł' => 'l']);
        Normalizer::reset();

        self::assertSame(Normalizer::DEFAULT_REPLACEMENTS, Normalizer::replacements());
    }

    public function test_extend_refuse_une_cle_vide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Normalizer::extend(['' => 'x']);
    }

    public function test_extend_refuse_un_antislash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Normalizer::extend(['ł' => '\\']);
    }
}
