<?php

use App\Models\Role;
use Illuminate\Http\Request;
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

/**
 * prepares form data for use - converts checkboxes to booleans, explodes arrays and so on
 * also removes _token and submit mode
 * @param array $raw_form_data form data straight from request
 * @param array $casts types of form data as in ["field_name" => "type"]
 * Available types:
 * - string - default
 * - number
 * - bool
 * - array
 * @param ?array $return_as_well fields to return along processed data, for further use
 */
if (!function_exists('prepareFormData')) {
    function prepareFormData(Request $form_data, ?array $casts = [], ?array $return_as_well = null): array
    {
        $form_data = $form_data->except(["_token", "mode"]);

        foreach ($casts as $field => $type) {
            switch ($type) {
                case "number":
                    $form_data[$field] = as_number($form_data[$field]);
                    break;
                case "bool":
                    $form_data[$field] = boolval($form_data[$field] ?? false);
                    break;
                case "array":
                    $form_data[$field] = array_filter(explode(",", $form_data[$field] ?? ""));
                    break;
                case "string":
                default:
                    ;
            }
        }

        return ($return_as_well)
            ? array_merge(
                ["form_data" => $form_data],
                array_filter($form_data, fn ($k) => in_array($k, $return_as_well), ARRAY_FILTER_USE_KEY)
            )
            : $form_data;
    }
}
