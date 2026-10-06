<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch;

/**
 * Turns a text into its search form: lowercase, without accents or ligatures.
 *
 * The same replacement table is used on the PHP side (typed term) and on the SQL side
 * (compared column, see SqlExpression): both sides are therefore always normalized
 * the same way.
 */
final class Normalizer
{
    /**
     * Escape character used in the generated LIKE patterns (ESCAPE '!' clause).
     * It was chosen because it is written the same way in all supported databases.
     */
    public const LIKE_ESCAPE = '!';

    /**
     * Replacements applied after the text is put in lowercase.
     */
    public const DEFAULT_REPLACEMENTS = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ï' => 'i', 'î' => 'i', 'ì' => 'i', 'í' => 'i',
        'ô' => 'o', 'ö' => 'o', 'ò' => 'o', 'ó' => 'o', 'õ' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ÿ' => 'y', 'ý' => 'y',
        'ç' => 'c',
        'ñ' => 'n',
        'œ' => 'oe', 'æ' => 'ae',
        'ß' => 'ss',
        'ø' => 'o',
        'đ' => 'd',
    ];

    /** @var array<string, string>|null */
    private static ?array $replacements = null;

    /**
     * Normalizes a text: lowercase, then removes accents and ligatures.
     *
     *     Normalizer::normalize('Élève Œuvre'); // 'eleve oeuvre'
     */
    public static function normalize(string $value): string
    {
        return strtr(mb_strtolower($value, 'UTF-8'), self::replacements());
    }

    /**
     * Builds the LIKE pattern to compare with a column wrapped by SqlExpression.
     * Special characters typed by the user (%, _) are escaped: they are
     * searched as they are instead of acting as wildcards.
     *
     *     Normalizer::pattern('Élève');                  // '%eleve%'
     *     Normalizer::pattern('Élè', Mode::StartsWith);   // 'ele%'
     *     Normalizer::pattern('100%');                    // '%100!%%'
     */
    public static function pattern(string $term, Mode|string $mode = Mode::Contains): string
    {
        $escaped = self::escapeLike(self::normalize($term));

        return match (Mode::resolve($mode)) {
            Mode::Contains => '%'.$escaped.'%',
            Mode::StartsWith => $escaped.'%',
            Mode::EndsWith => '%'.$escaped,
            Mode::Exact => $escaped,
        };
    }

    /**
     * Escapes the LIKE wildcards (%, _) and the escape character itself.
     */
    public static function escapeLike(string $value): string
    {
        $e = self::LIKE_ESCAPE;

        return str_replace([$e, '%', '_'], [$e.$e, $e.'%', $e.'_'], $value);
    }

    /**
     * Active replacement table (default + entries added with extend()).
     *
     * @return array<string, string>
     */
    public static function replacements(): array
    {
        return self::$replacements ??= self::DEFAULT_REPLACEMENTS;
    }

    /**
     * Adds or replaces entries in the replacement table, for example
     * to handle other alphabets. Keys are put in lowercase.
     *
     *     Normalizer::extend(['ł' => 'l', 'š' => 's']);
     *
     * Call it only once when the application starts (ServiceProvider,
     * bundle configuration...), before any query.
     *
     * @param array<string, string> $replacements
     */
    public static function extend(array $replacements): void
    {
        $current = self::replacements();

        foreach ($replacements as $from => $to) {
            $from = (string) $from;
            if ($from === '') {
                throw new \InvalidArgumentException('Une clé de remplacement ne peut pas être vide.');
            }
            if (str_contains($from.$to, '\\')) {
                throw new \InvalidArgumentException("Le remplacement \"{$from}\" ne peut pas contenir d'antislash.");
            }

            $current[mb_strtolower($from, 'UTF-8')] = $to;
        }

        self::$replacements = $current;
    }

    /**
     * Goes back to the default table (useful in tests).
     */
    public static function reset(): void
    {
        self::$replacements = null;
    }
}
