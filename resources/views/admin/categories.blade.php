@extends("layouts.admin")
@section("title", "Kategorie")

@section("content")

<x-listing>
    @forelse ($categories as $category)
    <x-listing.item
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
    </x-listing.item>
    @empty
    <p class="ghost">Brak utworzonych kategorii</p>
    @endforelse
</x-listing>

<div class="flex-right center">
    <x-button :action="route('categories-edit')" label="Nowa" icon="add" />
</div>

@endsection
