<?php

if (!function_exists("processFutureDelivery")) {
    function processFutureDelivery(array $future_delivery) {
        if (count($future_delivery) == 0)
            return "brak";

        // wybierz najbliższą dostawę
        $future_delivery = collect($future_delivery)
            ->sortBy("date")
            ->first();

        return $future_delivery["quantity"] . " ok. " . $future_delivery["date"];
    }
}
