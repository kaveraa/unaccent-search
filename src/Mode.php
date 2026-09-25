<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch;

/**
 * Façon dont le terme recherché doit correspondre à la valeur en base.
 */
enum Mode: string
{
    /** La valeur contient le terme : LIKE '%terme%' (par défaut) */
    case Contains = 'contains';

    /** La valeur commence par le terme : LIKE 'terme%' */
    case StartsWith = 'starts_with';

    /** La valeur se termine par le terme : LIKE '%terme' */
    case EndsWith = 'ends_with';

    /** La valeur est égale au terme, aux accents et à la casse près : LIKE 'terme' */
    case Exact = 'exact';

    /**
     * Accepte une instance de Mode ou son nom texte, y compris les conditions
     * historiques des datatables ('contain', 'start_with', 'end_with', 'equal').
     *
     * @throws \ValueError si le mode est inconnu
     */
    public static function resolve(self|string $mode): self
    {
        if ($mode instanceof self) {
            return $mode;
        }

        return match ($mode) {
            'contain' => self::Contains,
            'start_with', 'starts' => self::StartsWith,
            'end_with', 'ends' => self::EndsWith,
            'equal', 'equals' => self::Exact,
            default => self::from($mode),
        };
    }
}
