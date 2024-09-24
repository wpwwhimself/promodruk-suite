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

    <x-app.section
        title="Rabaty"
    >
        <div class="flex-right center middle">
            <x-input-field type="number"
                name="global_products_discount" label="Rabat na produkty (%)"
                min="0" step="0.1"
                :value="Auth::user()->global_products_discount"
            />
            <x-input-field type="number"
                name="global_markings_discount" label="Rabat na znakowania (%)"
                min="0" step="0.1"
                :value="Auth::user()->global_markings_discount"
            />
            <x-input-field type="number"
                name="global_surcharge" label="Nadwyżka (%)"
                min="0" step="0.1"
                :value="Auth::user()->global_surcharge"
            />
        </div>
    </x-app.section>

    @foreach ($products as $product)
    <x-app.section
        title="{{ $product['name'] }} ({{ $product['original_color_name'] }})"
        :subtitle="$product['id']"
        class="flex-down"
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
            <span>Wartość produktu netto:</span>
            <ul>
                @foreach ($product["quantities"] as $qty)
                <li>
                    {{ $qty }} szt:
                    <strong>{{ as_pln($product["price"] * $qty) }}</strong>
                    <small class="ghost">{{ as_pln($product["price"]) }}/szt.</small>
                </li>
                @endforeach
            </ul>

            @foreach ($product["markings"] as $position_name => $techniques)
            <h3>{{ $position_name }}</h3>

            <div class="flex-down">
                @foreach ($techniques as $t)
                <x-offer.position :marking="$t" :base-price-per-unit="$product['price']" />
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
        }
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

    const showQuantities = (section) => {
        section.querySelector(".quantities").parentElement.classList.toggle("hidden")
    }

    const deleteProductFromOffer = (section) => {
        section.remove()
        submitWithLoader()
    }
    </script>

</form>

@endsection
