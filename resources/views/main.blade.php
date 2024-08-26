@extends("layouts.main")
@section("title", "Kategorie główne")

@section("content")

<x-tiling count="4" class="large-gap">
@foreach ($categories as $cat)
    <x-tiling.item :title="$cat->name"
        :img="$cat->thumbnail_link"
        :link="$cat->external_link ?? route('category-'.$cat->id)"
        show-img-placeholder
        image-covering
    >
        {{ \Illuminate\Mail\Markdown::parse($cat->description ?? "") }}

        <x-slot:buttons>
            <x-button action="none" label="Szczegóły" icon="chevrons-right" />
        </x-slot:buttons>
    </x-tiling.item>
@endforeach
</x-tiling>

<style>
.tiling h3 {
    font-size: 1.5em;
}
</style>

@endsection
