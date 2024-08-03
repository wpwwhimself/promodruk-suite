@extends("layouts.main")
@section("title", "Katalog produktów")

@section("content")

<x-tiling>
@foreach ($categories as $cat)
    <x-tiling.item :title="$cat->name"
        :img="$cat->thumbnail_link"
        :link="route('category-'.$cat->id)"
    >
        {{ \Illuminate\Mail\Markdown::parse($cat->description ?? "") }}
    </x-tiling.item>
@endforeach
</x-tiling>

@endsection
