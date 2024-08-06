@extends("layouts.main")
@section("title", $query)
@section("subtitle", "Wyniki wyszukiwania")

@section("content")

{{ $results->links() }}

<x-tiling count="auto">
    @forelse ($results as $product)
    <x-tiling.item :title="$product->name"
        :subtitle="$product->product_family_id"
        :img="collect($product->thumbnails)->first()"
        :link="route('product', ['id' => $product->id])"
    >
        <span class="flex-right wrap">
            @if ($product->family->count() > 1)
            @foreach ($product->family as $alt)
            <x-color-tag :color="collect($alt->color)" class="small" />
            @endforeach
            @endif
        </span>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak dopasowań</p>
    @endforelse
</x-tiling>

{{ $results->links() }}

@endsection
