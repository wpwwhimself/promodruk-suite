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
        $prefix = substr($product_code, 0, 2);

        return (!!preg_match("/^[A-Z]{2}$/", $prefix) && $prefix != $this->getPrefix())
            ? collect()
            : $this->getData($product_code);
    }
}
