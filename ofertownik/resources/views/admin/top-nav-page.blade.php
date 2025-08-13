@extends("layouts.admin")
@section("title", implode(" | ", [$page->name ?? "Nowa strona", "Edycja strony"]))

@section("content")

<form action="{{ route('update-top-nav-pages') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $page?->id }}" />

    <x-input-field type="text" label="Nazwa" name="name" :value="$page?->name" />
    <x-input-field type="number" label="Priorytet" name="ordering" :value="$page?->ordering" />
    <x-input-field type="checkbox" label="Pokaż w menu" name="show_in_top_nav" :value="$page?->show_in_top_nav" />
    @if ($page)
    <x-input-field type="dummy" label="Link" name="link" :value="route($page->slug)" />
    @endif
    <x-ckeditor name="content" label="Treść" :value="$page?->content" />

    <div class="flex-right center">
        <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
        <x-button action="submit" name="mode" value="saveAndNew" label="...i stwórz nową" icon="save" />
        @if ($page)
        <x-button action="submit" name="mode" value="delete" label="Usuń" icon="delete" class="danger" />
        @endif
    </div>
    <div class="flex-right center">
        <x-button :action="route('top-nav-pages')" label="Wróć" icon="arrow-left" />
    </div>
</form>

@endsection
