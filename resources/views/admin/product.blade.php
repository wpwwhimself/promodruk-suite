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

        <x-tiling.item title="Kategorie" icon="inbox">
            <x-category-selector :selected-categories="$product->categories" />
        </x-tiling.item>

        <x-tiling.item title="Dane zewnętrzne" icon="link">
            <div class="flex-right center">
                <x-button :action="env('MAGAZYN_URL').'admin/products/'.$product->id" target="_blank" label="Edytuj w Magazynie" icon="box" />
            </div>
            <x-input-field type="text" label="Nazwa" name="name" :value="$product->name" disabled />
            <x-input-field type="text" label="SKU rodziny" name="product_family_id" :value="$product->product_family_id" disabled />
        </x-tiling.item>
    </x-tiling>

    <div class="flex-right center">
        <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
        <x-button action="submit" name="mode" value="delete" label="Usuń" icon="delete" class="danger" />
    </div>
    <div class="flex-right center">
        <x-button :action="route('products')" label="Wróć" icon="arrow-left" />
    </div>
</form>

@endsection
