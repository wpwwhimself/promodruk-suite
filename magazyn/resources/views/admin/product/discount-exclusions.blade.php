@extends("layouts.admin")
@section("title", "Produkty wykluczone z rabatowania")
@section("subtitle", "Zarządzanie produktami")

@section("content")

<x-magazyn-section title="Zarządzanie" icon="cog">
    <div class="flex right center middle">
        <x-multi-input-field :options="[]"
            name="family_id"
            label="Wyklucz nową rodzinę"
        />
    </div>
</x-magazyn-section>

<x-magazyn-section title="Lista wykluczonych produktów" :icon="model_icon('products')">
    <x-slot:buttons>
        <form method="GET" action="{{ route("product-discount-exclusions") }}" class="flex right center middle">
            <x-input-field type="text"
                name="search"
                label="Wyszukaj"
                :value="request()->get('search')"
                placeholder="SKU, nazwa, opis..."
            />

            <x-button action="submit" label="Filtruj" />
        </form>
    </x-slot:buttons>

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
</x-magazyn-section>

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
