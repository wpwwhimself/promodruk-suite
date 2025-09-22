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
    <x-multi-input-field name="category" label="Kategoria" :options="$data" empty-option="brak" />
    <p>Alternatywnie wpisz SKU produktów (rozdzielone średnikiem) do wyszukania.</p>
    <x-input-field type="TEXT" name="query" label="SKU" />

    <script>
    const categoryDropdown = document.querySelector("[name='category']")
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
    <input type="hidden" name="source" value="{{ $source }}">
    <input type="hidden" name="category" value="{{ $category }}">

    <x-tiling class="stretch-tiles">
        <x-tiling.item title="Produkty" icon="box">
            <p>Wybierz produkty do zaimportowania</p>

            <x-input-field type="text" name="filter" label="Filtruj (nazwa, SKU)" oninput="filterImportables(event.target.value)" hint="Użyj ; do dodawania kolejnych wyszukiwań do tej samej listy" />
            <script>
            function filterImportables(query) {
                document.querySelectorAll("[role='importables'] tr").forEach(row => {
                    const row_q = row.dataset.q.toLowerCase();
                    const show = (query.length > 0)
                        ? query.toLowerCase().split(";").some(q_string => row_q.includes(q_string))
                        : true;
                    row.classList.toggle("hidden", !show);
                });
            }
            </script>

            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nazwa</th>
                        <th><input type="checkbox" onchange="selectAllVisible(this)" /></th>
                    </tr>
                </thead>
                <tbody role="importables">
                @foreach ($data as $product)
                    <tr data-q="{{ $product["prefixed_id"] }} {{ $product["name"] }}">
                        <td>{{ $product["prefixed_id"] }}</td>
                        <td>
                            <img src="{{ collect($product["thumbnails"])->first() }}" alt="{{ $product["name"] }}" class="inline"
                                {{ Popper::pop("<img src='" . collect($product["thumbnails"])->first() . "' />") }}
                            >
                            {{ $product["name"] }}
                        </td>
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

        <x-tiling.item title="Kategorie" icon="list" style="overflow: visible;">
            <p>Wybierz kategorie, do których będą przypisane te produkty</p>

            <x-category-selector />
        </x-tiling.item>

        <x-tiling.item title="Widoczność" icon="eye">
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
