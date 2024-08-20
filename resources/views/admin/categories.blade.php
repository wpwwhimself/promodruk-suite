@extends("layouts.admin")
@section("title", "Kategorie")

@section("content")


{{ $categories->appends(compact("perPage", "sortBy"))->links("vendor.pagination.tailwind", [
    "availableSorts" => [
        'kolejność rosnąco' => 'ordering',
        'kolejność malejąco' => '-ordering',
        'nazwa rosnąco' => 'name',
        'nazwa malejąco' => '-name',
    ],
    "availableFilters" => [
        ["cat_parent_id", "Nadrzędna", $catsForFiltering],
    ]
]) }}

<x-listing>
    @forelse ($categories as $category)
    <x-listing.item
        :title="$category->name"
        :subtitle="$category->label"
        :img="$category->thumbnail_link"
        :ghost="!$category->visible"
        show-img-placeholder
        image-covering
    >
        <x-input-field type="dummy" label="Priorytet" name="ordering" :value="$category->ordering" />

        <x-slot:buttons>
            <x-button
                :action="route('categories-edit', ['id' => $category->id])"
                label="Edytuj"
                icon="tool"
            />
        </x-slot:buttons>
    </x-listing.item>
    @empty
    <p class="ghost">Brak utworzonych kategorii</p>
    @endforelse
</x-listing>

{{ $categories->appends(compact("perPage", "sortBy"))->links("vendor.pagination.bottom") }}

@endsection

@section("interactives")

<div class="flex-right center">
    <x-button :action="route('categories-edit')" label="Nowa" icon="add" />
</div>

@endsection
