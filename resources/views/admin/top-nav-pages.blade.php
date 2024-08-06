@extends("layouts.admin")
@section("title", "Strony górne")

@section("content")

<x-listing>
    @forelse ($pages as $page)
    <x-listing.item :title="$page->name">
        <x-slot:buttons>
            <x-button
                :action="route('top-nav-pages-edit', ['id' => $page->id])"
                label="Edytuj"
                icon="tool"
            />
        </x-slot:buttons>
    </x-listing.item>
    @empty
    <p class="ghost">Brak utworzonych stron</p>
    @endforelse
</x-listing>

@endsection

@section("interactives")

{{ $pages->links() }}

<div class="flex-right center">
    <x-button :action="route('top-nav-pages-edit')" label="Nowa" icon="add" />
</div>

@endsection
