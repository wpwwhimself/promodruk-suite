<?php

namespace App\Models;

use Wpwwhimself\Shipyard\Models\StandardPage as ShipyardStandardPage;

class StandardPage extends ShipyardStandardPage
{
    public const FROM_SHIPYARD = true;

    public function scopeForConnection($query)
    {
        return $query->orderBy("name");
    }

    public function scopeForCurrentDomain()
    {
        $domain = Domain::where("domain", request()->schemeAndHttpHost())->first();

        return ($domain)
            ? $domain->pages()
            : $this->visible();
    }
}
