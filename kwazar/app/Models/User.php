<?php

namespace App\Models;

use App\Models\Shipyard\User as ShipyardUser;

class User extends ShipyardUser
{
    public const FROM_SHIPYARD = true;

    protected $fillable = [
        'name',
        'email',
        'password',
        'default_discounts',
    ];

    public const EXTRA_SECTIONS = [
        "default-discounts" => [
            "title" => "Domyślne rabaty",
            "icon" => "sale",
            "show-on" => "edit",
            "component" => "user.discounts",
            // "role" => "",
        ],
    ];

    public function casts(): array
    {
        return [
            'password' => 'hashed',
            "default_discounts" => "array",
        ];
    }
}
