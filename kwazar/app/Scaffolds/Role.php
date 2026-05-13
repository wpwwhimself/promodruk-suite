<?php

namespace App\Scaffolds;

use App\Scaffolds\Shipyard\Role as ShipyardRole;

class Role extends ShipyardRole
{
    protected static function items(): array
    {
        return [
            [
                "name" => "offer-manager",
                "icon" => "file",
                "description" => "Ma dostęp do swoich ofert",
            ],
            [
                "name" => "offer-master",
                "icon" => "file-plus",
                "description" => "Ma dostęp do wszystkich ofert",
            ],
        ];
    }
}
