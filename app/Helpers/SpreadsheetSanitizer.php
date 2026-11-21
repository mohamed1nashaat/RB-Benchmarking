<?php

namespace App\Helpers;

class SpreadsheetSanitizer
{
    /**
     * Sanitize a string value for safe use in spreadsheet cells.
     * Prevents formula injection attacks by prefixing dangerous characters.
     */
    public static function cell(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $firstChar = $value[0];

        // Prefix formula-triggering characters with a single quote
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
