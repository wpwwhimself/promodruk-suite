<?php

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

function userCanSeeWithSetting(string $setting)
{
    return setting($setting) >= (Auth::id() ? 1 : 2);
}

/**
 * Formats a number as PLN
 * @param ?float $value
 */
function asPln($value)
{
    return $value === null ? null : number_format($value, 2, ",", " ") . " zł";
}

/**
 * Sorts two values based on the specified key, prioritizing non-null values.
 *
 * @param string $by The key to sort by.
 * @param mixed $a The first value to compare.
 * @param mixed $b The second value to compare.
 * @param bool $desc Whether to sort in descending order. Defaults to false.
 * @return int A negative integer, zero, or a positive integer as the first argument is considered to be less than, equal to, or greater than the second.
 */
function sortByNullsLast($by, $a, $b, $desc = false)
{
    if ($a[$by] === null && $b[$by] === null) return 0;
    if ($a[$by] === null) return 1;
    if ($b[$by] === null) return -1;
    return $desc ? $b[$by] <=> $a[$by] : $a[$by] <=> $b[$by];
}
