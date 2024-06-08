<?php

namespace App\ApiHandlers;

use Illuminate\Http\Client\Response;

abstract class ApiHandler
{
    private const URL = self::URL;

    abstract public function call(string $func_name, string $params = null): Response;
}
