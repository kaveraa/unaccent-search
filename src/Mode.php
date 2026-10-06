<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch;

/**
 * How the search term must match the value in the database.
 */
enum Mode: string
{
    /** The value contains the term: LIKE '%term%' (default) */
    case Contains = 'contains';

    /** The value starts with the term: LIKE 'term%' */
    case StartsWith = 'starts_with';

    /** The value ends with the term: LIKE '%term' */
    case EndsWith = 'ends_with';

    /** The value equals the term, ignoring accents and case: LIKE 'term' */
    case Exact = 'exact';

    /**
     * Accepts a Mode instance or its text value ('contains', 'starts_with', 'ends_with', 'exact').
     *
     * @throws \ValueError if the mode is unknown
     */
    public static function resolve(self|string $mode): self
    {
        return $mode instanceof self ? $mode : self::from($mode);
    }
}
