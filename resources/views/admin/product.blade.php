@extends("layouts.admin")
@section("title", implode(" | ", [$product->name ?? "Nowy produkt", "Edycja produktu"]))

@section("content")

<form action="{{ route('update-products') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $product?->id }}" />

    <x-tiling count="1" class="stretch-tiles">
        <x-tiling.item title="Ustawienia lokalne" icon="home">
            <x-input-field type="text" label="SKU" name="id" :value="$product?->id" />
            <x-multi-input-field label="Widoczny" name="visible" :value="$product?->visible ?? 2" :options="\App\Models\Product::VISIBILITIES" />
            <x-ckeditor name="extra_description" label="Dodatkowy opis" :value="$product?->extra_description" />
        </x-tiling.item>

        <x-tiling.item title="Kategorie" icon="inbox">
            <x-category-selector :selected-categories="$product->categories" />
        </x-tiling.item>

        <x-tiling.item title="Dane zewnętrzne" icon="link">
            <div class="flex-right center">
                <x-button :action="env('MAGAZYN_URL').'admin/products/edit/'.$product->id" target="_blank" label="Edytuj w Magazynie" icon="box" />
            </div>
            <x-input-field type="text" label="Nazwa" name="name" :value="$product->name" disabled />
            <x-input-field type="text" label="SKU rodziny" name="product_family_id" :value="$product->product_family_id" disabled />
            <div class="flex-right center"><img src="{{ collect($product->thumbnails)->first() }}" alt="{{ $product->name }}" class="thumbnail"></div>
        </x-tiling.item>

        <x-tiling.item title="Powiązane produkty" icon="link">
            <p class="ghost">
                Wpisz SKU produktów, które mają być wyświetlane wspólnie z tym produktem.
                Pozycje rozdziel średnikiem.
            </p>

            <x-input-field type="text"
                name="related_product_ids"
                label="SKU powiązanych produktów"
                :value="$product->related_product_ids"
            />

            <ul>
                @forelse ($product->related as $product)
                <li>{{ $product->name }}</li>
                @empty
                <span class="ghost">Brak powiązanych produktów</span>
                @endforelse
            </ul>
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
