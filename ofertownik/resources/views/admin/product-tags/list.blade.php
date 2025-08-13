@extends("layouts.admin")
@section("title", "Tagi produktów")

@section("content")

<p>
    Tagi produktu pozwalają na wyróżnienie produktów na listingu.
    Produkty oznaczone tagiem otrzymują wyróżnik na kafelku lub nawet utrzymują wysokie pozycje listingu.
</p>

<div class="flex-right center wrap">
    @forelse ($tags as $tag)
    <div class="flex-down center">
        <h2>
            {{ $tag->name }}
            @if ($tag->gives_priority_on_listing) <span @popper(Priorytet dla produktów)>📌</span> @endif
        </h2>
        <x-tiling count="auto" class="but-mobile-down small-tiles middle">
            <x-product-tile :product="$product" :tag="$tag" />
        </x-tiling>
        <x-button :action="route('product-tags-edit', ['id' => $tag->id])" label="Edytuj" icon="edit" />
    </div>
    @empty
    <p class="ghost">Brak utworzonych tagów</p>
    @endforelse
</div>


@endsection

@section("interactives")

<div class="flex-right center">
    <x-button :action="route('product-tags-edit')" label="Nowy" icon="add" />
</div>

@endsection
