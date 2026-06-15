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
            Wybierz jedną z poniższych kategorii, aby wyświetlić szczegóły.
        </p>

        <table>
            <thead>
                <tr>
                    <th class="sortable">Kategoria</th>
                    <th class="sortable">Liczba produktów</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($missing_families_groups ?? [] as $cat => $families)
                <tr>
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
