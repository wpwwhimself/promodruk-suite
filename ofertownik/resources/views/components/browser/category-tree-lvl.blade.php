@props([
    "category" => null,
    "lvl" => -1,
])

@php
$intermediate_to_category = $category?->tree[$lvl + 1] ?? null;
$categories = ($category?->depth ?? 0) != $lvl
    ? $intermediate_to_category->parent->children ?? \App\Models\Category::visible()->whereNull("parent_id")->get()
    : $category->children;
@endphp

<ul>
    @foreach ($categories as $ccat)
    <li @class([
        "animatable",
        "bold" => $ccat->depth == 0,
        "active" => $category?->id == $ccat->id,
    ])
        onclick="getCategory({{ $ccat->id }})"
    >
        @if ($ccat->depth > 0) <x-ik-chevron-right class="left" /> @endif
        {{ $ccat->name }}
        @if ($ccat->children->count() > 0) <x-ik-chevron-left class="right" /> @endif

    </li>
    @if ($ccat->id == $intermediate_to_category?->id)
    <x-browser.category-tree-lvl :category="$category" :lvl="$lvl + 1" />
    @endif
    @endforeach
</ul>
