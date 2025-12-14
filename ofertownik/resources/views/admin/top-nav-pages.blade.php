@extends("layouts.admin")
@section("title", "Strony")

@section("content")

{{ $pages->appends(compact("perPage", "sortBy"))->links("vendor.pagination.top", [
    "availableSorts" => [
        'nazwa rosnąco' => 'name',
        'nazwa malejąco' => '-name',
    ],
]) }}

<p>
    Poniższa lista zawiera zdefiniowane strony informacyjne, jakie są dostępne dla klientów.
    Ikona oka oznacza, że link do strony jest widoczny na górnym pasku Ofertownika.
</p>

<x-tiling count="auto">
    @forelse ($pages as $page)
    <x-tiling.item :title="$page->name"
        :subtitle="route('top-nav.show', ['slug' => $page->slug])"
        :icon="$page->show_in_top_nav ? 'eye' : null"
    >
        <x-slot:buttons>
            <x-button
                :action="route('top-nav-pages-edit', ['id' => $page->id])"
                label="Edytuj"
                icon="tool"
            />
        </x-slot:buttons>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak utworzonych stron</p>
    @endforelse
</x-tiling>

{{ $pages->appends(compact("perPage", "sortBy"))->links("vendor.pagination.bottom") }}

@endsection

@section("interactives")

<div class="flex-right center">
    <x-button :action="route('top-nav-pages-edit')" label="Nowa" icon="add" />
</div>

@endsection
