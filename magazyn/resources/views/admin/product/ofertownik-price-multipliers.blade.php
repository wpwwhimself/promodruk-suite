@extends("layouts.shipyard.admin")
@section("title", "Produkty z mnożnikiem ceny")
@section("subtitle", "Zarządzanie produktami")

@section("content")

<x-shipyard.app.card>
    <p>
        Ten panel pozwala na masowe zarządzanie mnożnikami ceny produktu na potrzeby Ofertownika.
    </p>
</x-shipyard.app.card>

<x-shipyard.app.section
    title="Zmodyfikowane produkty"
    :icon="model_icon('product-families')"
    id="currently-modified-families"
>
    <x-shipyard.app.section
        title="Filtry"
        icon="filter"
        :extended="false"
    >
        <div class="grid but-mobile-down" style="--col-count: 3;">
            <x-shipyard.ui.input type="text"
                name="filter[id]"
                label="SKU"
                icon="barcode"
                onchange="updateCurrentlyModifiedFamiliesList()"
            />
            <x-shipyard.ui.input type="text"
                name="filter[name]"
                label="Nazwa"
                :icon="model_field_icon('product-families', 'name')"
                onchange="updateCurrentlyModifiedFamiliesList()"
            />
            <x-shipyard.ui.input type="select"
                name="filter[supplier]"
                label="Dostawca"
                :icon="model_icon('custom-suppliers')"
                :select-data="[
                    'optionsFromStatic' => [
                        \App\Models\CustomSupplier::class,
                        'allSuppliers',
                    ],
                    'emptyOption' => 'Wszyscy',
                ]"
                onchange="updateCurrentlyModifiedFamiliesList()"
            />
        </div>
    </x-shipyard.app.section>

    <x-shipyard.app.loader />

    <div role="list"></div>

    <input type="hidden" name="currently_modified_families" value="">

    <x-slot:actions>
        <x-shipyard.ui.button
            label="Dodaj nowe"
            icon="plus"
            action="none"
            onclick="openModal('add-ofertownik-price-multiplier', {})"
            class="tertiary"
        />
        <x-shipyard.ui.button
            label="Popraw przefiltrowane"
            pop="Produkty z poniższej listy (zgodnie z obecnymi filtrami, również te niewidoczne na obecnej stronie) otrzymają nowe mnożniki."
            icon="sync"
            action="none"
            onclick="openModal('set-ofertownik-price-multiplier-for-families', {
                families: document.querySelector('[name=currently_modified_families]').value,
            })"
            class="tertiary"
        />
    </x-slot:actions>

    <script>
    function updateCurrentlyModifiedFamiliesList() {
        const section = document.getElementById("currently-modified-families");
        const loader = section.querySelector(".loader");

        const filters = section.querySelectorAll("[name^='filter']");
        const filter_data = Array.from(filters)
            .filter(input => input.value)
            .map(input => [input.name, input.value]);

        loader.classList.remove("hidden");
        fetch(`/api/products/families-for-ofertownik-price-multipliers?` + new URLSearchParams(filter_data))
            .then(res => res.json())
            .then(({data, list}) => {
                section.querySelector("[role=list]").replaceWith(fromHTML(list));
                section.querySelector("[name=currently_modified_families]").value = data.map(f => f.id).join(",");
            })
            .catch(err => {
                section.querySelector("[role=list]").replaceWith(fromHTML(`<p class="accent error" role="list">Nie udało się pobrać listy produktów</p>`));
                section.querySelector("[name=currently_modified_families]").value = "";
                console.error(err);
            })
            .finally(() => {
                reapplyPopper();
                loader.classList.add("hidden");
            })
    }

    updateCurrentlyModifiedFamiliesList();
    </script>
</x-shipyard.app.section>

@endsection
