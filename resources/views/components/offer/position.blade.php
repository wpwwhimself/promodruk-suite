@props([
    "marking",
    "productId",
    "basePricePerUnit" => null,
])

<div class="offer-position flex-right stretch top">
    <div class="data flex-right">
        @foreach (array_filter([0, $basePricePerUnit], fn ($price) => !is_null($price)) as $product_price)
        <div class="grid" class="--col-count: 1;">
            <h4>
                @if ($product_price == 0)
                {{ $marking["technique"] }}
                <small class="ghost">{{ $marking["print_size"] }}</small>
                @else
                <small class="ghost">Cena: produkt + znakowanie</small>
                @endif
            </h4>

            @foreach ($marking["main_price_modifiers"] ?? ["" => null] as $label => $modifier)
            @if (!empty($modifier)) <span>{{ $label }}</span> @endif
            <ul>
                @foreach ($marking["quantity_prices"] as $requested_quantity => $price_per_unit)
                @php
                $mod_price_per_unit = eval("return $price_per_unit $modifier;");
                @endphp
                <li>
                    {{ $requested_quantity }} szt:
                    <strong>{{ as_pln(($mod_price_per_unit + $product_price) * $requested_quantity * (1 + $marking["surcharge"] / 100)) }}</strong>
                    <small class="ghost">{{ as_pln(($mod_price_per_unit + $product_price) * (1 + $marking["surcharge"] / 100)) }}/szt.</small>
                </li>
                @endforeach
            </ul>
            @endforeach
        </div>
        @endforeach

        <x-input-field type="number"
            name="surcharge[{{ $productId }}][{{ $marking['technique'] }}]" label="Nadwyżka (%)"
            min="0" step="0.1"
            :value="$marking['surcharge']"
        />
    </div>

    <div class="images flex-right">
        <img class="thumbnail"
            src="{{ $marking["images"][0] }}"
            {{ Popper::pop("<img src='" . $marking["images"][0] . "' />") }}
        />
    </div>
</div>
