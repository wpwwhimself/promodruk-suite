@extends("layouts.app")
@section("title", "Szczegóły oferty")

@section("content")

<x-app.loader text="Przeliczanie" />

<form action="{{ route('offers.prepare') }}" method="post"
    class="flex-down"
    onsubmit="submitWithLoader()"
>
    @csrf

    <section class="flex-right center middle sticky">
        <x-multi-input-field
            name="product"
            label="Dodaj produkt do listy"
            empty-option="Wybierz..."
            :options="[]"
        />

        <button type="submit">Przelicz wycenę</button>
    </section>

    @if ($products)
    <x-app.section
        title="Rabaty"
    >
        <div class="flex-right center middle">
            @foreach ([
                "Na produkty (%)" => "global_products_discount",
                "Na znakowania (%)" => "global_markings_discount",
            ] as $label => $name)
            <x-input-field type="number"
                :name="$name" :label="$label"
                min="0" step="0.1"
                :value="$discounts[$name] ?? Auth::user()->{$name}"
            />
            @endforeach

            <x-input-field type="number"
                name="global_surcharge" label="Nadwyżka (%)"
                min="0" step="0.1"
            />
        </div>
    </x-app.section>
    @endif

    @foreach ($products as $product)
    <x-app.section
        title="{{ $product['name'] }} ({{ $product['original_color_name'] }})"
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
                            <strong>{{ as_pln($product["price"] * $qty * (1 + $product["surcharge"] / 100)) }}</strong>
                            <small class="ghost">{{ as_pln($product["price"] * (1 + $product["surcharge"] / 100)) }}/szt.</small>
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
                <x-offer.position :marking="$t" :base-price-per-unit="$product['price']" :product-id="$product['id']" />
                @endforeach
            </div>
            @endforeach
        @endif

    </x-app.section>
    @endforeach

    <script defer>
    const form = document.forms[0]
    const submitWithLoader = () => {
        $("#loader").removeClass("hidden")
        form.submit()
    }

    $("select#product").select2({
        ajax: {
            url: "{{ env('MAGAZYN_API_URL') }}products/for-markings",
            data: (params) => ({
                q: params.term,
            }),
        },
        width: "20em",
    }).on("select2:select", function(e) {
        $(this).append(`<input type="hidden" name="product_ids[]" value="${e.params.data.id}">`);
        submitWithLoader()
    })

    const _appendQuantity = (input, quantity) => {
        input.closest("section").find(".quantities").append(`<div {{ Popper::pop("Usuń ilość") }} onclick="this.remove()">
            <input type="hidden" name="quantities[${input.attr("data-product")}][]" value="${quantity}">
            <span class="button">${quantity}</span>
        </div>`)
    }
    $("input[name^=quantities_maker]").on("change keypress", function(e) {
        if (e.type === "keypress" && e.which !== 13) return;
        e.preventDefault()
        _appendQuantity($(this), $(this).val())
        $(this).val(null)
    })
    // init quantities
    @if ($products)
    const quantities = {!! json_encode($products->mapWithKeys(fn($p) => [$p["id"] => $p["quantities"]])) !!}
    Object.keys(quantities).forEach(product_id => {
        quantities[product_id].forEach(qty => _appendQuantity($(`input[data-product="${product_id}"]`), qty))
    })
    @endif

    $(".product input[name^=surcharge]").on("change", function(e) {
        $(`input[name=global_surcharge]`).val(null)
    })
    // init global surcharge (if no products available, show default for user)
    @if (!collect($products)->pluck("quantities")->flatten()->count())
    $("input[name=global_surcharge]").val("{{ Auth::user()->global_surcharge }}")
    @endif

    const showQuantities = (section) => {
        section.querySelector(".quantities").parentElement.classList.toggle("hidden")
    }

    const deleteProductFromOffer = (section) => {
        section.remove()
        submitWithLoader()
    }
    </script>

</form>

<style>
input[type=number] {
    width: 4.5em;
}
</style>

@endsection
