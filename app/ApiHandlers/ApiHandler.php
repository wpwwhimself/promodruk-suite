<?php

namespace App\ApiHandlers;

use Illuminate\Support\Collection;

abstract class ApiHandler
{
    private const URL = self::URL;

    abstract public function getData(string $params = null): Collection;
}
