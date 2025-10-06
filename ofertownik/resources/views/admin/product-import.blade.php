@extends("layouts.admin")
@section("title", "Import produktów")

@section("content")

@if (empty($source) || empty($category) && empty($query))

<form action="{{ route('products-import-fetch') }}" method="post" class="flex-down center">
    @csrf

    @if (empty($source))

    <p>Wybierz dostawcę, od którego chcesz pobrać produkty.</p>
    <x-multi-input-field name="source" label="Dostawca" :options="$data" />

    @elseif (empty($category) && empty($query))

    <input type="hidden" name="source" value="{{ $source }}">
    <p>Wybierz kategorię, w której oryginalne produkty się znajdują.</p>
    <x-multi-input-field name="category[]" label="Kategoria" :options="$data" multiple />
    <p>Alternatywnie wpisz SKU produktów (rozdzielone średnikiem) do wyszukania.</p>
    <x-input-field type="TEXT" name="query" label="SKU" />

    <script>
    const categoryDropdown = document.querySelector("[name='category[]']")
    const categorySearchDropdown = new Choices(categoryDropdown, {
        itemSelectText: null,
        noResultsText: "Brak wyników",
        shouldSort: false,
        searchResultLimit: -1,
        fuseOptions: {
            ignoreLocation: true,
            treshold: 0,
        },
    });
    </script>

    @endif

    <div class="flex-right center">
        <x-button action="submit" label="Znajdź" icon="search" />
        @if (!empty($source))<x-button :action="route('products-import-init')" label="Od nowa" icon="back-left" /> @endif
        <x-button :action="route('products')" label="Porzuć i wróć" icon="arrow-left" />
    </div>
</form>

@else

<form action="{{ route('products-import-import') }}" method="post">
    @csrf

    <x-tiling count="2" class="stretch-tiles">
        <x-tiling.item title="Produkty" icon="box">
            <p>Wybierz produkty do zaimportowania</p>

            <h4>Filtry</h4>
            <x-input-field type="text" name="filter" label="Nazwa, SKU" oninput="filterImportables()" hint="Użyj ; do dodawania kolejnych wyszukiwań do tej samej listy" />
            <x-input-field type="number" min="0" step="0.01" name="filter" label="Minimalna cena" oninput="filterImportables()" />
            <x-input-field type="number" min="0" step="0.01" name="filter" label="Maksymalna cena" oninput="filterImportables()" />
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
        </x-tiling.item>

        <x-tiling.item title="Kategorie i widoczność" icon="list" style="overflow: visible;">
            <p>Wybierz kategorie, do których będą przypisane te produkty</p>

            <x-category-selector />

            <p>Czy pobrane produkty mają być widoczne?</p>

            <x-multi-input-field label="Widoczność" name="visible" :value="2" :options="VISIBILITIES" />
        </x-tiling.item>
    </x-tiling>

    <div class="flex-right center">
        <x-button action="submit" label="Zapisz" icon="save" />
        <x-button :action="route('products-import-init')" label="Od nowa" icon="back-left" />
        <x-button :action="route('products')" label="Porzuć i wróć" icon="arrow-left" />
    </div>
</form>

@endif


@endsection
