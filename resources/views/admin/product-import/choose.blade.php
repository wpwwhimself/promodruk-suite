@extends("layouts.admin")
@section("title", "Import produktów")

@section("content")

<form action="{{ route('products-import-import') }}" method="post">
    @csrf
    <input type="hidden" name="query_code" value="{{ $code }}">

    <x-tiling>
        <x-tiling.item title="Produkty" icon="box">
            <p>Wybierz produkty do zaimportowania</p>

            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nazwa</th>
                        <th><input type="checkbox" onchange="selectAll(this)" /></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td>{{ $product["id"] }}</td>
                        <td>
                            <img src="{{ collect($product["images"])->first() }}" alt="{{ $product["name"] }}" class="inline">
                            {{ $product["name"] }}
                        </td>
                        <td><input type="checkbox" name="ids[]" value="{{ $product["id"] }}" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <script>
            selectAll = (btn) => {
                document.querySelectorAll("input[name^=ids]")
                    .forEach(input => input.checked = btn.checked)
            }
            </script>
        </x-tiling.item>

        <x-tiling.item title="Kategorie" icon="list">
            <p>Wybierz kategorie, do których będą przypisane te produkty</p>

            <x-category-selector />
        </x-tiling.item>
    </x-tiling>


    <div class="flex-right center">
        <x-button action="submit" label="Zapisz" icon="save" />
        <x-button :action="route('products-import-init')" label="Wróć" icon="arrow-left" />
    </div>
</form>

@endsection
