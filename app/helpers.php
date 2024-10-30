<?php

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * converts stringified number, cleans it up and returns its actual value
 */
if (!function_exists('as_number')) {
    function as_number(?string $string): float | int | null
    {
        if (empty($string)) return null;

        $string = str_replace(" ", "", $string);
        $string = str_replace(",", ".", $string);

        return (Str::contains($string, "."))
            ? round(floatval($string), 2)
            : intval($string);
    }
}

/**
 * checks whether user is a member of a given role by name
 */
if (!function_exists('userIs')) {
    function userIs(?string $role): bool
    {
        if (empty($role)) return true;
        return Auth::user()->roles->contains(Role::where("name", $role)->first());
    }
}

/**
 * turns number into braille dots
 */
if (!function_exists('numdots')) {
    function numdots(?int $number): string
    {
        $number ??= 0;
        $dots = ["⠀", "⠄", "⠆", "⠦", "⠧", "⠷", "⠿"];
        return str_repeat($dots[6], floor($number / 6)) . $dots[$number % 6];
    }
}
