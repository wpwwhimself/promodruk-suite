<?php

namespace App\DataIntegrators;

use Illuminate\Support\Collection;

abstract class ApiHandler
{
    private const URL = self::URL;

    abstract public function getData(string $params = null): Collection;
    abstract public function getPrefix(): string;

    public function getDataWithPrefix(string $product_code)
    {
        preg_match("/^[A-Z]*/", $product_code, $matches);
        $prefix = $matches[0];

        // abort fetch if prefix exists and itdoesn't match
        return (strlen($prefix) > 0 && $prefix != $this->getPrefix())
            ? collect()
            : $this->getData($product_code);
    }
}
