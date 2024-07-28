@extends("layouts.admin")
@section("title", implode(" | ", [$product->name ?? "Nowy produkt", "Edycja produktu"]))

@section("content")

<form action="{{ route('update-products') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $product?->id }}" />

    <x-tiling>
        <x-tiling.item title="Ustawienia lokalne" icon="home">
            <x-input-field type="text" label="SKU" name="id" :value="$product?->id" />
            <x-input-field type="checkbox" label="Widoczny" name="visible" :value="$product?->visible ?? true" />
            <x-input-field type="TEXT" label="Dodatkowy opis [md]" name="extra_description" :value="$product?->extra_description" />
        </x-tiling.item>

        @if ($product)
        <x-tiling.item title="Kategorie" icon="inbox">
        </x-tiling.item>
        @endif
    </x-tiling>

    <div class="flex-right center">
        <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
        @if ($product)
        <x-button action="submit" name="mode" value="delete" label="Usuń" icon="delete" class="danger" />
        @endif
    </div>
    <div class="flex-right center">
        <x-button :action="route('products')" label="Wróć" icon="arrow-left" />
    </div>
</form>

@endsection
