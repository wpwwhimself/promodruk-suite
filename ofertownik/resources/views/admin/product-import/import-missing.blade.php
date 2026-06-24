@extends("layouts.shipyard.admin")
@section("title", "Import braków")
@section("subtitle", "Import produktów")

@section("content")

<x-shipyard.app.card>
    <p>Import pozwala na dodanie do Ofertownika podobnych i jeszcze niepobranych z Magazynu produktów.</p>
</x-shipyard.app.card>

<x-shipyard.app.form :action="route('products-import-fetch')" method="post" target="_blank">
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

        <div class="flex right spread and-cover stick-top">
            <x-shipyard.ui.button
                label="Znajdź"
                icon="magnify"
                action="submit"
                class="primary"
            />
        </div>
        <div class="grid but-mobile-down" style="--col-count: 2;">
            <x-shipyard.ui.input type="select"
                name="filterSupplierType"
                icon="truck"
                label="Rodzaj dostawcy"
                :select-data="[
                    'options' => $supplier_type_filters,
                    'emptyOption' => 'Wszyscy ('.$missing_families->count().')',
                ]"
                onchange="filterImportables();"
            />
            <x-shipyard.ui.input
                name="filter"
                icon="magnify"
                label="Szukaj"
                oninput="filterImportables();"
            />
        </div>

        <table>
            <thead>
                <tr>
                    <th class="sortable">Dostawca</th>
                    <th class="sortable">Kategoria dostawcy</th>
                    <th class="sortable">Liczba produktów</th>
                    <th>
                        <x-shipyard.ui.input type="checkbox"
                            label=""
                            name="_select_all"
                            class="compact"
                            onchange="selectAllImportables(this.checked);"
                        />
                    </th>
                </tr>
            </thead>
            <tbody role="importables">
                @foreach ($missing_families_groups ?? [] as $supp => $gg)
                @foreach ($gg ?? [] as $cat => $families)
                @php
                $exemplar = $families->first();
                @endphp
                <tr data-q="{{ $supp }}_{{ $cat }}" data-custom="{{ $exemplar["is_custom"] ? 2 : 1 }}">
                    <td>{{ $supp }}</td>
                    <td>{{ $cat }}</td>
                    <td>{{ count($families) }}</td>
                    <td>
                        <x-shipyard.ui.input type="checkbox"
                            label=""
                            name="category[]"
                            :value="$cat"
                            class="compact"
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
    let supplierType = document.querySelector("[name='filterSupplierType']").value;

    document.querySelectorAll("[role='importables'] tr").forEach(row => {
        const row_q = row.dataset.q.toLowerCase();

        let show = true;

        show &&= (query.length > 0) ? query.toLowerCase().split(";").some(q_string => row_q.includes(q_string)) : true;
        show &&= (row.dataset.custom == supplierType || supplierType == 0);

        row.classList.toggle("hidden", !show);
    });
}

function selectAllImportables(checked) {
    document.querySelectorAll(`[role="importables"] input[name="category[]"]`).forEach(input => {
        input.checked = checked;
    });
}
</script>

@endsection
