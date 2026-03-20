<?php

namespace App\Util;

final class SlugUtil
{
    /** Umlaut/ß → ASCII, used for slugs and display normalization */
    private const UMLAUT_MAP = [
        'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue',
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
        'ß' => 'ss',
    ];

    /**
     * Convert a name to a URL slug: trim, replace umlauts, spaces → underscore.
     * Example: "Dollnsteiner Turm" → "Dollnsteiner_Turm"
     */
    public static function nameToSlug(string $name): string
    {
        $slug = strtr(trim($name), self::UMLAUT_MAP);
        return str_replace(' ', '_', $slug);
    }

    /**
     * Replace German umlauts and ß with ASCII equivalents.
     * Use for slugs, IDs, or display normalization.
     */
    public static function umlautsToAscii(string $value): string
    {
        return strtr($value, self::UMLAUT_MAP);
    }
}
