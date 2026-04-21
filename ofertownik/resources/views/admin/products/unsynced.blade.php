@extends("layouts.shipyard.admin")
@section("title", "Status synchronizacji produktów")

@section("content")

<x-shipyard.app.card>
    <p>
        Poniższe produkty nie mają swoich odpowiedników w Magazynie (nie są synchronizowane).
        Możesz je usunąć z systemu za pomocą przycisków poniżej.
    </p>
</x-shipyard.app.card>

<x-shipyard.app.form :action="route('products-unsynced-delete')" method="post">
    <x-shipyard.app.card>
        @if ($unsynced->isEmpty())
        <p>Brak niepowiązanych produktów – wszystko jest aktualne.</p>
        <div class="flex right center">
            <x-shipyard.ui.button :action="route('admin.model.list', ['model' => 'products'])" label="Wróć" icon="arrow-left" />
        </div>

        @else
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nazwa</th>
                    <th>% brak <span @popper(Ile wariantów tego produktu nie jest zsynchronizowanych)>(?)</span></th>
                    <th><input type="checkbox" onchange="selectAllVisible(this)" /></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($unsynced as $product)
                <tr>
                    <td>
                        <a href="{{ route('products-edit', ['id' => $product->family_prefixed_id]) }}" target="_blank">{{ $product->front_id }}</a>
                    </td>
                    <td>
                        <img src="{{ $product->cover_image ?? $product->thumbnails->first() }}" alt="{{ $product->name }}" class="inline"
                            {{ Popper::pop("<img class='thumbnail' src='" . ($product->cover_image ?? $product->thumbnails->first()) . "' />") }}
                        >
                        {{ $product->name }}
                    </td>
                    <td>
                        {{ $product->family->filter(fn ($v) => !$v->is_synced_with_magazyn)->count() }}
                        /
                        {{ $product->family->count() }}
                    </td>
                    <td><input type="checkbox" name="ids[]" value="{{ $product->id }}" /></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $unsynced->links("components.shipyard.pagination.default") }}

        @endif
    </x-shipyard.app.card>

    <x-slot:actions>
        <x-shipyard.app.card>
            <x-shipyard.ui.button action="submit"
                label="Usuń zaznaczone"
                class="danger"
                icon="delete"
            />
        </x-shipyard.app.card>
    </x-slot:actions>
</x-shipyard.app.form>

<script>
selectAllVisible = (btn) => {
    document.querySelectorAll("tr:not(.hidden) input[name^=ids]")
        .forEach(input => input.checked = btn.checked)
}
</script>

@endsection
