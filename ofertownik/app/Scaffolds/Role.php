<?php

namespace App\Scaffolds;

use App\Scaffolds\Shipyard\Role as ShipyardRole;

class Role extends ShipyardRole
{
    protected static function items(): array
    {
        return [
            [
                "name" => "product-manager",
                "icon" => "package-variant",
                "description" => "Ma dostęp do produktów i bytów z nim związanych (kategorie, tagi)",
            ],
        ];
    }
}
