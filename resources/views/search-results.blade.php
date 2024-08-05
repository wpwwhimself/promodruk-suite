@extends("layouts.main")
@section("title", $query)
@section("subtitle", "Wyniki wyszukiwania")

@section("content")

{{ $results->links() }}

<x-tiling count="auto">
    @forelse ($results as $product)
    <x-tiling.item :title="$product->name"
        :subtitle="$product->id"
        :img="collect($product->thumbnails)->first()"
        :link="route('product', ['id' => $product->id])"
    >
        <x-slot:buttons>
        </x-slot:buttons>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak dopasowań</p>
    @endforelse
</x-tiling>

{{ $results->links() }}

@endsection
