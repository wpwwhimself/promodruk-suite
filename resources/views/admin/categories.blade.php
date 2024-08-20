@extends("layouts.admin")
@section("title", "Kategorie")

@section("content")


{{ $categories->appends(compact("perPage", "sortBy"))->links("vendor.pagination.tailwind", [
    "availableSorts" => [
        'nazwa rosnąco' => 'name',
        'nazwa malejąco' => '-name',
    ],
    "availableFilters" => [
        ["cat_parent_id", "Nadrzędna", $catsForFiltering],
    ]
]) }}

<x-tiling count="auto">
    @forelse ($categories as $category)
    <x-tiling.item
        :title="$category->name"
        :subtitle="$category->label"
        :img="$category->thumbnail_link"
        :ghost="!$category->visible"
    >
        @if ($category->description)
        {{ \Illuminate\Mail\Markdown::parse($category->description) }}
        @endif

        <x-slot:buttons>
            <x-button
                :action="route('categories-edit', ['id' => $category->id])"
                label="Edytuj"
                icon="tool"
            />
        </x-slot:buttons>
    </x-tiling.item>
    @empty
    <p class="ghost">Brak utworzonych kategorii</p>
    @endforelse
</x-tiling>

{{ $categories->appends(compact("perPage", "sortBy"))->links("vendor.pagination.bottom") }}

@endsection

@section("interactives")

<div class="flex-right center">
    <x-button :action="route('categories-edit')" label="Nowa" icon="add" />
</div>

@endsection
