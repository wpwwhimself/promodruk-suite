@extends("layouts.shipyard.admin")
@section("title", "Import braków")
@section("subtitle", "Import produktów")

@section("content")

<x-shipyard.app.card>
    <p>Import pozwala na dodanie do Ofertownika podobnych i jeszcze niepobranych z Magazynu produktów.</p>
</x-shipyard.app.card>

<x-shipyard.app.form :action="route('products-import-fetch')" method="post" enctype="multipart/form-data">
    <input type="hidden" name="missing_mode" value="1">

    <x-shipyard.app.card>
        <p>
            W Magazynie wykryto <strong class="accent primary">{{ $missing_families->count() }}</strong> produktów, których nie ma obecnie w Ofertowniku.
        </p>

        @if ($missing_families->count() > 0)
        <p>
            Wybierz jedną z poniższych kategorii, aby wyświetlić produkty w Magazynie odpowiadające tym kryteriom i jeszcze nie zaimportowane do Ofertownika.
            <span class="ghost">Uwaga: wyszukane zostaną produkty <strong>zawierające</strong> wskazany tekst w kategorii dostawcy.</span>
        </p>
        <x-shipyard.ui.input
            name="filter"
            icon="magnify"
            label="Szukaj"
            oninput="filterImportables();"
        />

        <table>
            <thead>
                <tr>
                    <th class="sortable">Dostawca ↕️</th>
                    <th class="sortable">Kategoria dostawcy ↕️</th>
                    <th class="sortable">Liczba produktów ↕️</th>
                    <th></th>
                </tr>
            </thead>
            <tbody role="importables">
                @foreach ($missing_families_groups ?? [] as $supp => $gg)
                @foreach ($gg ?? [] as $cat => $families)
                <tr data-q="{{ $supp }}_{{ $cat }}">
                    <td>{{ $supp }}</td>
                    <td>{{ $cat }}</td>
                    <td>{{ count($families) }}</td>
                    <td>
                        <x-shipyard.ui.button
                            label="Znajdź"
                            icon="magnify"
                            action="submit"
                            name="category"
                            :value="$cat"
                            class="primary"
                        />
                    </td>
                </tr>
            @endforeach
            @endforeach
            </tbody>
        </table>
        @endif
    </x-shipyard.app.card>

    <x-slot:actions>
        <x-shipyard.app.card>
            <x-shipyard.ui.button :action="route('products-import-mode')"
                label="Od nowa"
                icon="restart"
            />
            <x-shipyard.ui.button :action="route('admin.model.list', ['model' => 'products'])"
                label="Porzuć i wróć"
                icon="arrow-left"
            />
        </x-shipyard.app.card>
    </x-slot:actions>
</x-shipyard.app.form>

@endsection

@section ("prepends")

<script>
function filterImportables() {
    let [query] = Array.from(document.querySelectorAll("[name='filter']")).map(input => input.value);

    document.querySelectorAll("[role='importables'] tr").forEach(row => {
        const row_q = row.dataset.q.toLowerCase();

        let show = true;

        show &&= (query.length > 0) ? query.toLowerCase().split(";").some(q_string => row_q.includes(q_string)) : true;

        row.classList.toggle("hidden", !show);
    });
}
</script>

@endsection
