<?php

use Illuminate\Support\Str;

/**
 * converts stringified number, cleans it up and returns its actual value
 */
if (!function_exists('as_number')) {
    function as_number(string $string): float | int | null
    {
        if ($string = "") return null;

        $string = str_replace(" ", "", $string);
        $string = str_replace(",", ".", $string);

        return (Str::contains($string, "."))
            ? floatval($string)
            : intval($string);
    }
}
