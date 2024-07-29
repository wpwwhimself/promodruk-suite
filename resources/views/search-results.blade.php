@extends("layouts.main")
@section("title", implode(" | ", [$query, "Wyszukiwanie"]))

@section("content")

<h2>Wyniki wyszukiwania: {{ $query }}</h2>

<x-tiling>
    @forelse ($results as $product)
    <x-tiling.item :title="$product->name"
        :subtitle="$product->id"
        :img="collect($product->images)->first()"
        :link="route('product', ['id' => $product->id])"
    >
        <x-slot:buttons>
        </x-slot:buttons>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak dopasowań</p>
    @endforelse
</x-tiling>

@endsection
