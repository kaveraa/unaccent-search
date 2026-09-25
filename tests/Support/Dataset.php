<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Support;

use Kaveraa\UnaccentSearch\Mode;

/**
 * Jeu de données et cas de recherche partagés par les tests Laravel et Doctrine :
 * les deux ponts doivent donner exactement les mêmes résultats.
 */
final class Dataset
{
    /**
     * @return list<array{name: ?string, code: ?string}>
     */
    public static function rows(): array
    {
        return [
            ['name' => 'Élève', 'code' => 'ELV-01'],
            ['name' => 'eleve', 'code' => 'ELV-02'],
            ['name' => 'ÉCOLE maternelle', 'code' => 'ECO-01'],
            ['name' => 'Crème brûlée', 'code' => 'DES-01'],
            ['name' => 'Œuvre d\'art', 'code' => 'ART-01'],
            ['name' => 'čaj vert', 'code' => 'THE-01'],
            ['name' => '100% coton', 'code' => 'TEX-01'],
            ['name' => 'coton_bio', 'code' => 'TEX-02'],
            ['name' => 'Straße', 'code' => 'VIL-02'],
            ['name' => 'Garçon', 'code' => 'Français'],
            ['name' => null, 'code' => 'NUL-01'],
        ];
    }

    /**
     * Recherches sur la colonne name : [terme, mode, noms attendus].
     *
     * @return array<string, array{0: ?string, 1: Mode|string, 2: list<?string>}>
     */
    public static function searches(): array
    {
        return [
            'sans accent trouve les accentués' => ['eleve', Mode::Contains, ['Élève', 'eleve']],
            'avec accents et majuscules' => ['ÉLÈVE', Mode::Contains, ['Élève', 'eleve']],
            'majuscule accentuée en base' => ['ecole', Mode::Contains, ['ÉCOLE maternelle']],
            'plusieurs mots' => ['creme brulee', Mode::Contains, ['Crème brûlée']],
            'ligature œ saisie en oe' => ['oeuvre', Mode::Contains, ['Œuvre d\'art']],
            'ligature œ saisie telle quelle' => ['œuvre', Mode::Contains, ['Œuvre d\'art']],
            'apostrophe' => ["d'art", Mode::Contains, ['Œuvre d\'art']],
            'cédille' => ['garcon', Mode::Contains, ['Garçon']],
            'ß en ss' => ['strasse', Mode::Contains, ['Straße']],
            'caractère hors table cherché tel quel' => ['čaj', Mode::Contains, ['čaj vert']],
            '% cherché littéralement' => ['100%', Mode::Contains, ['100% coton']],
            '_ cherché littéralement' => ['n_b', Mode::Contains, ['coton_bio']],
            'commence par' => ['CRE', Mode::StartsWith, ['Crème brûlée']],
            'se termine par' => ['bio', Mode::EndsWith, ['coton_bio']],
            'égal' => ['eleve', Mode::Exact, ['Élève', 'eleve']],
            'égal ne fait pas de contient' => ['elev', Mode::Exact, []],
            'mode texte (datatable)' => ['cre', 'start_with', ['Crème brûlée']],
            'aucun résultat' => ['introuvable', Mode::Contains, []],
        ];
    }

    /**
     * @return list<?string>
     */
    public static function allNames(): array
    {
        return array_column(self::rows(), 'name');
    }
}
