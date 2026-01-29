@props([
    "category" => null,
])

@php
$bigMode = $category === null;
@endphp

@if ($category)
<x-breadcrumbs :category="$category" reactive />
<x-carousel :imgs="$category->banners" />
<h1>{{ $category->name }}</h1>
{!! $category->welcome_text !!}
@endif

<x-tiling :count="5 - (int) $bigMode" @class([
    "large-gap",
    "small-tiles" => !$bigMode,
])>
    @foreach ($category?->children
        ->merge($category?->related)
        ?? \App\Models\Category::visible()->ordered()->whereNull("parent_id")->get()
    as $cat)
    <x-tiling.item :title="$cat->name"
        :img="$cat->thumbnail_link"
        :link="$cat->external_link || $cat->children->count() === 0 ? $cat->link : null"
        :target="$cat->external_link ? '_blank' : '_self'"
        show-img-placeholder
        :onclick="$cat->external_link || $cat->children->count() === 0 ? null : 'getCategory('.$cat->id.')'"
    >
        {{ \Illuminate\Mail\Markdown::parse($cat->description ?? "") }}

        <x-slot:buttons>
            <x-button action="none" label="Szczegóły" icon="chevrons-right" @class([
                "small" => !$bigMode,
            ]) />
        </x-slot:buttons>
    </x-tiling.item>
    @endforeach
</x-tiling>
