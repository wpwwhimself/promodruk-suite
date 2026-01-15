@extends("layouts.shipyard.admin")
@section("title", "Import produktów")

@section("content")

@if (empty($source) || empty($category) && empty($query))

<x-shipyard.app.form :action="route('products-import-fetch')" method="post" enctype="multipart/form-data">
    <x-shipyard.app.card id="step-1-supplier"
        title="Dostawcę"
        subtitle="Wybierz dostawcę, od którego chcesz pobrać produkty"
        icon="truck"
    >
        <x-shipyard.ui.input type="select"
            name="source"
            label="Dostawca"
            icon="truck"
            :select-data="[
                'options' => $availableSuppliers,
                'emptyOption' => 'wybierz...',
            ]"
            onchange="showStep2()"
        />
    </x-shipyard.app.card>

    <script>
    function showStep2() {
        const source = document.querySelector("[name='source']").value;
        const step2container = document.querySelector("#step-2-details");
        const loader = step2container.querySelector(".loader");
        const categoriesContainer = step2container.querySelector("[role=magazyn-categories]");

        step2container.classList.remove("hidden");
        loader.classList.remove("hidden");
        categoriesContainer.innerHTML = "";
        fetch(`{{ route("products-import-fetch") }}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
            },
            body: JSON.stringify({
                asComponent: true,
                source: source,
            }),
        })
            .then(res => res.json())
            .then(({data, html}) => {
                categoriesContainer.innerHTML = html;
                reinitSelect();
            })
            .catch(err => console.error(err))
            .finally(() => {
                loader.classList.add("hidden");
            });
    }
    </script>

    <x-shipyard.app.card id="step-2-details" class="hidden"
        title="Szczegóły"
        subtitle="Podaj więcej informacji o produktach"
        icon="details"
    >
        <p>Wybierz kategorię, w której oryginalne produkty się znajdują.</p>
        <x-shipyard.app.loader horizontal />
        <div role="magazyn-categories"></div>
        <p>Alternatywnie wpisz SKU produktów (rozdzielone średnikiem) do wyszukania.</p>
        <x-shipyard.ui.input type="TEXT"
            name="query"
            label="SKU"
            icon="barcode"
        />
        <p>Możesz też przekazać SKU w formie pliku.</p>
        <x-shipyard.ui.input type="file"
            name="import_from_file"
            label="Dodaj plik"
            icon="file"
            accept=".csv, .txt"
            hint="Obsługiwane pliki CSV lub TXT. Plik powinien zawierać listę SKU, każde w nowej linii."
        />
    </x-shipyard.app.card>

    <x-slot:actions>
        <x-shipyard.app.card>
            <x-shipyard.ui.button action="submit"
                label="Znajdź"
                icon="magnify"
                class="primary"
            />
            <x-shipyard.ui.button :action="route('products')"
                label="Porzuć i wróć"
                icon="arrow-left"
            />
        </x-shipyard.app.card>
    </x-slot:actions>
</x-shipyard.app.form>

@else

<x-shipyard.app.form :action="route('products-import-import')" method="post">
    <div class="grid but-mobile-down" style="--col-count: 2;">
        <x-shipyard.app.section
            title="Produkty"
            subtitle="Wybierz produkty do zaimportowania"
            :icon="model_icon('products')"
        >
            <x-shipyard.app.section title="Filtry" icon="filter" :extended="false">
                <x-shipyard.ui.input type="text" name="filter" label="Nazwa, SKU" oninput="filterImportables()" hint="Użyj ; do dodawania kolejnych wyszukiwań do tej samej listy" />
                <x-shipyard.ui.input type="number" min="0" step="0.01" name="filter" label="Minimalna cena" oninput="filterImportables()" />
                <x-shipyard.ui.input type="number" min="0" step="0.01" name="filter" label="Maksymalna cena" oninput="filterImportables()" />
                <script>
                function filterImportables() {
                    let [query, price_min, price_max] = Array.from(document.querySelectorAll("[name='filter']")).map(input => input.value);
                    price_min = (price_min == "") ? 0 : parseFloat(price_min);
                    price_max = (price_max == "") ? Infinity : parseFloat(price_max);

                    document.querySelectorAll("[role='importables'] tr").forEach(row => {
                        const row_q = row.dataset.q.toLowerCase();
                        const row_price = parseFloat(row.dataset.price);

                        let show = true;

                        show &&= (query.length > 0) ? query.toLowerCase().split(";").some(q_string => row_q.includes(q_string)) : true;
                        show &&= row_price >= price_min;
                        show &&= row_price <= price_max;

                        row.classList.toggle("hidden", !show);
                    });
                }
                function reSortImportables() {
                    document.querySelectorAll("[role='importables'] tr").forEach(row => {
                        row.parentNode.insertBefore(row, row.parentNode.firstChild);
                    });
                }
                </script>
            </x-shipyard.app.section>

            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nazwa</th>
                        <th>Kategoria</th>
                        <th>
                            Cena
                            <span @popper(Średnia cena wszystkich wariantów)>(?)</span>
                            <span @popper(Odwróć kolejność) onclick="reSortImportables()">↕️</span>
                        </th>
                        <th><input type="checkbox" onchange="selectAllVisible(this)" /></th>
                    </tr>
                </thead>
                <tbody role="importables">
                @foreach ($data as $product)
                    @php
                    $exemplar = collect($product["products"])->random();
                    $avg_price = round(collect($product["products"])->avg("price"), 2);
                    @endphp
                    <tr data-q="{{ $product["prefixed_id"] }} {{ $product["name"] }}" data-price="{{ $avg_price }}">
                        <td>{{ $product["prefixed_id"] }}</td>
                        <td>
                            <img src="{{ current($exemplar["combined_images"])[2] }}" alt="{{ $product["name"] }}" class="inline"
                                {{ Popper::pop("<img class='thumbnail' src='" . current($exemplar["combined_images"])[2] . "' />") }}
                            >
                            {{ $product["name"] }}
                        </td>
                        <td>{{ $product["original_category"] }}</td>
                        <td>{{ $avg_price }}</td>
                        <td><input type="checkbox" name="ids[]" value="{{ $product["id"] }}" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <script>
            selectAllVisible = (btn) => {
                document.querySelectorAll("tr:not(.hidden) input[name^=ids]")
                    .forEach(input => input.checked = btn.checked)
            }
            </script>
        </x-shipyard.app.section>

        <x-shipyard.app.section
            title="Kategorie i widoczność"
            subtitle="Wybierz kategorie, do których będą przypisane te produkty"
            :icon="model_icon('categories')"
        >
            <x-category-selector />
            <x-shipyard.ui.field-input :model="new \App\Models\Product()" field-name="visible" />
        </x-shipyard.app.section>
    </div>

    <x-slot:actions>
        <x-shipyard.app.card>
            <x-shipyard.ui.button action="submit"
                label="Zapisz"
                icon="check"
                class="primary"
            />
            <x-shipyard.ui.button :action="route('products-import-init')"
                label="Od nowa"
                icon="restart"
            />
            <x-shipyard.ui.button :action="route('products')"
                label="Porzuć i wróć"
                icon="arrow-left"
            />
        </x-shipyard.app.card>
    </x-slot:actions>
</x-shipyard.app.form>

@endif


@endsection
