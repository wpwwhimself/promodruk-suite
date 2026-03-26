@extends("layouts.admin")
@section("title", "Produkty wykluczone z rabatowania")
@section("subtitle", "Zarządzanie produktami")

@section("content")

<x-shipyard.app.card>
    <p>
        Ten panel pozwala na masowe zarządzanie wykluczeniami z rabatowania produktów na potrzeby Kwazara.
    </p>
</x-shipyard.app.card>

<x-shipyard.app.section
    title="Reguły wykluczeń dla synchronizacji"
    :icon="model_icon('product-synchronizations')"
    :extended="false"
>
    <x-slot:actions>
        <p class="accent danger">Dostępność rabatowania produktów pochodzących od dostawców z synchronizacji jest aktualizowana na bieżąco. Zmiany w sekcji <i class="accent tertiary">Lista wykluczonych produktów</i> nie będą dla nich stałe.</p>
    </x-slot:actions>

    <x-shipyard.app.form
        :action="route('update-discount-exclusion-rules')"
        method="post"
    >
        <div class="grid" style="--col-count: 3;">
            @foreach (\App\Models\ProductSynchronization::ordered()->get() as $sync)
            <div>
                <h3 class="accent tertiary" style="text-align: center;">{{ $sync->supplier_name }}</h3>
                <x-shipyard.ui.input
                    type="JSON"
                    :column-types="[
                        'Pole' => 'text',
                        '...zawiera' => 'text',
                        'Wyklucz' => 'checkbox',
                    ]"
                    :name="$sync->supplier_name.'_rules'"
                    label="Reguły"
                    :value="$sync->discount_exclusion_rules"
                    icon="script"
                    role="technical"
                />
            </div>
            @endforeach
        </div>

        <x-slot:actions>
            <x-shipyard.ui.button action="submit" label="Zapisz" icon="check" class="primary" />
        </x-slot:actions>
    </x-shipyard.app.form>

    <x-shipyard.app.card
        title="Jak korzystać z reguł?"
        icon="tooltip-question"
    >
        <p>Dostępne pola:</p>
        <ul>
            <li>name - nazwa produktu</li>
            <li>description - opis</li>
            <li>original_sku - SKU produktu po stronie dostawcy</li>
            <li>original_category - kategoria produktu po stronie dostawcy</li>
            <li>* - zadziała dla każdego produktu</li>
        </ul>
        <p>
            Jeśli w wartości reguły znajduje się wiele fraz oddzielonych <code>;</code>, pole musi posiadać wszystkie te frazy, żeby zastosować regułę.
        </p>
        <p>
            Wartości mnożników należy podać z kropką zamiast przecinka. Wielkość liter w regułach nie ma znaczenia.
        </p>
    </x-shipyard.app.card>
</x-shipyard.app.section>

<x-shipyard.app.section
    title="Lista wykluczonych produktów"
    :icon="model_icon('products')"
>
    <x-slot:actions>
        <form method="GET" action="{{ route("product-discount-exclusions") }}" class="flex right center middle">
            <x-input-field type="text"
                name="search"
                label="Wyszukaj"
                :value="request()->get('search')"
                placeholder="SKU, nazwa, opis..."
            />

            <x-button action="submit" label="Filtruj" />
        </form>
        <div class="flex right center middle">
            <x-multi-input-field :options="[]"
                name="family_id"
                label="Wyklucz nową rodzinę"
            />
        </div>
    </x-slot:actions>

    <div class="grid" style="--col-count: 3">
        @forelse ($excluded_families as $family)
        <div>
            <x-product.family :family="$family" />
            <a href="{{ route("product-discount-exclusions-toggle", ['family_id' => $family->id]) }}" class="accent danger">Przywróć</a>
        </div>
        @empty
        <li class="ghost">Brak produktów wykluczonych z rabatowania</li>
        @endforelse
    </div>

    {{ $excluded_families->appends(["search" => request()->get("search")])->withQueryString()->links("components.shipyard.pagination.default") }}
</x-shipyard.app.section>

<script defer>
const productDropdown = document.querySelector("[name='family_id']");
const productSearchDropdown = new Choices(productDropdown, {
    singleModeForMultiSelect: true,
    itemSelectText: null,
    noResultsText: "Brak wyników",
    shouldSort: false,
    searchChoices: false,
});

let search_timeout = null;
productDropdown.addEventListener("search", async (e) => {
    clearTimeout(search_timeout);
    if (e.detail.value.length < 2) return;

    search_timeout = setTimeout(() => {
        productSearchDropdown.setChoices(function () {
            return fetch(`{{ route("products-families-for-discount-exclusions") }}?q=${e.detail.value}`)
                .then(res => res.json())
                .then(data => data.map(f => ({ value: f.id, label: `[${f.prefixed_id}] ${f.name}` })));
        }, undefined, undefined, true);
    }, 500);
});
productDropdown.addEventListener("change", (e) => {
    if (!e.target.value) return;
    window.location.href = `/admin/products/discount-exclusions/toggle/${e.target.value}`;
});
</script>

@endsection
