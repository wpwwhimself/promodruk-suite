<?php

namespace App\DataIntegrators;

use Illuminate\Support\Collection;

abstract class ApiHandler
{
    private const URL = self::URL;

    abstract public function getData(string $params = null): Collection;
    abstract public function getPrefix(): string | array;

    public function getDataWithPrefix(string $product_code)
    {
        preg_match("/^[A-Z]*/", $product_code, $matches);
        $prefix = $matches[0];

        $provider_prefix = (gettype($this->getPrefix()) == "array")
            ? $this->getPrefix()
            : [$this->getPrefix()];

        // abort fetch if prefix exists and itdoesn't match
        return (strlen($prefix) > 0 && !in_array($prefix, $provider_prefix))
            ? collect()
            : $this->getData($product_code);
    }
}
