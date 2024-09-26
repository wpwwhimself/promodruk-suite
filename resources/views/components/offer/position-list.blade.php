@props([
    "products",
    "user",
    "showPricesPerUnit" => false,
])

@foreach ($products as $product)
<x-app.section
    title="{!! $product['name'] !!} ({{ $product['original_color_name'] }})"
    :subtitle="$product['id']"
    class="product flex-down"
>
    <x-slot:buttons>
        @if ($product["quantities"]) <span class="button" onclick="showQuantities(this.closest('section'))">Ilości</span> @endif
        <span class="button" onclick="deleteProductFromOffer(this.closest('section'))">Usuń</span>
    </x-slot:buttons>

    <input type="hidden" name="product_ids[]" value="{{ $product['id'] }}">

    <div class="{{ implode(" ", array_filter([
        "flex-right",
        "center",
        "middle",
        !$product["quantities"] ?: "hidden",
    ])) }}">
        <x-input-field type="number"
            name="quantities_maker[{{ $product['id'] }}]" label="Dodaj ilość"
            data-product="{{ $product['id'] }}"
            min="0" step="1"
        />
        <div class="quantities flex-right center middle"></div>
    </div>

    @if ($product["quantities"])
        <div class="flex-right">
            <div>
                <span>Wartość produktu netto:</span>
                <ul>
                    @foreach ($product["quantities"] as $qty)
                    <li>
                        {{ $qty }} szt:
                        <strong>{{ as_pln($product["price"] * pow($qty, !$showPricesPerUnit) * (1 + $product["surcharge"] / 100)) }}</strong>
                    </li>
                    @endforeach
                </ul>
            </div>

            <x-input-field type="number"
                name="surcharge[{{ $product['id'] }}][product]" label="Nadwyżka (%)"
                min="0" step="0.1"
                :value="$product['surcharge']"
            />
        </div>

        @foreach ($product["markings"] as $position_name => $techniques)
        <h3 style="grid-column: span 2">{{ $position_name }}</h3>

        <div class="flex-down">
            @foreach ($techniques as $t)
            <div class="offer-position flex-right stretch top">
                <div class="data flex-right">
                    @foreach (array_filter([0, $product["price"]], fn ($price) => !is_null($price)) as $product_price)
                    <div class="grid" class="--col-count: 1;">
                        <h4>
                            @if ($product_price == 0)
                            {{ $t["technique"] }}
                            <small class="ghost">{{ $t["print_size"] }}</small>
                            @else
                            <small class="ghost">Cena: produkt + znakowanie</small>
                            @endif
                        </h4>

                        @foreach ($t["main_price_modifiers"] ?? ["" => null] as $label => $modifier)
                        @if (!empty($modifier)) <span>{{ $label }}</span> @endif
                        <ul>
                            @foreach ($t["quantity_prices"] as $requested_quantity => $price_per_unit)
                            @php
                            $mod_price_per_unit = eval("return $price_per_unit $modifier;");
                            @endphp
                            <li>
                                {{ $requested_quantity }} szt:
                                <strong>{{ as_pln(($mod_price_per_unit + $product_price) * pow($requested_quantity, !$showPricesPerUnit) * (1 + $t["surcharge"] / 100)) }}</strong>
                            </li>
                            @endforeach
                        </ul>
                        @endforeach
                    </div>
                    @endforeach

                    <x-input-field type="number"
                        name="surcharge[{{ $product['id'] }}][{{ $t['position'] }}][{{ $t['technique'] }}]" label="Nadwyżka (%)"
                        min="0" step="0.1"
                        :value="$t['surcharge']"
                    />
                </div>

                <div class="images flex-right">
                    <img class="thumbnail"
                        src="{{ $t["images"][0] }}"
                        {{ Popper::pop("<img src='" . $t["images"][0] . "' />") }}
                    />
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
    @endif
</x-app.section>
@endforeach

<script>
$("input[name^=quantities_maker]").on("change keypress", function(e) {
    if (e.type === "keypress" && e.which !== 13) return;
    e.preventDefault()
    _appendQuantity($(this), $(this).val())
    $(this).val(null)
})
// init quantities
@if ($products)
quantities = {!! json_encode($products->mapWithKeys(fn($p) => [$p["id"] => $p["quantities"]])) !!}
Object.keys(quantities).forEach(product_id => {
    quantities[product_id].forEach(qty => _appendQuantity($(`input[data-product="${product_id}"]`), qty))
})
@endif

$(".product input[name^=surcharge]").on("change", function(e) {
    $(`input[name=global_surcharge]`).val(null)
})
// init global surcharge (if no products available, show default for user)
@if (!collect($products)->pluck("quantities")->flatten()->count())
    $("input[name=global_surcharge]").val("{{ $user->global_surcharge }}")
@endif
</script>
