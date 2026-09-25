<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch;

/**
 * Ramène un texte à sa forme de recherche : minuscules, sans accents ni ligatures.
 *
 * C'est la même table de remplacement qui sert côté PHP (terme saisi) et côté SQL
 * (colonne comparée, voir SqlExpression) : les deux côtés sont donc toujours normalisés
 * de façon identique.
 */
final class Normalizer
{
    /**
     * Caractère d'échappement utilisé dans les motifs LIKE générés (clause ESCAPE '!').
     * Il est choisi parce qu'il s'écrit de la même façon dans toutes les bases supportées.
     */
    public const LIKE_ESCAPE = '!';

    /**
     * Remplacements appliqués après la mise en minuscules.
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
     * Normalise un texte : minuscules puis suppression des accents et ligatures.
     *
     *     Normalizer::normalize('Élève Œuvre'); // 'eleve oeuvre'
     */
    public static function normalize(string $value): string
    {
        return strtr(mb_strtolower($value, 'UTF-8'), self::replacements());
    }

    /**
     * Construit le motif LIKE à comparer à une colonne passée par SqlExpression.
     * Les caractères spéciaux saisis par l'utilisateur (%, _) sont échappés : ils sont
     * recherchés tels quels au lieu de servir de jokers.
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
     * Échappe les jokers LIKE (%, _) et le caractère d'échappement lui-même.
     */
    public static function escapeLike(string $value): string
    {
        $e = self::LIKE_ESCAPE;

        return str_replace([$e, '%', '_'], [$e.$e, $e.'%', $e.'_'], $value);
    }

    /**
     * Table de remplacement active (par défaut + ajouts faits via extend()).
     *
     * @return array<string, string>
     */
    public static function replacements(): array
    {
        return self::$replacements ??= self::DEFAULT_REPLACEMENTS;
    }

    /**
     * Ajoute ou remplace des entrées dans la table de remplacement, par exemple
     * pour gérer d'autres alphabets. Les clés sont mises en minuscules.
     *
     *     Normalizer::extend(['ł' => 'l', 'š' => 's']);
     *
     * À appeler une seule fois au démarrage de l'application (ServiceProvider,
     * configuration du bundle...), avant toute requête.
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
     * Revient à la table par défaut (utile dans les tests).
     */
    public static function reset(): void
    {
        self::$replacements = null;
    }
}
