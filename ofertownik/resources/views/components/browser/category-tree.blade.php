@props([
    "category" => null,
])

@php
$categories = [];
$cursor = $category;
for ($lvl = $category?->depth ?? -1; $lvl > -1; $lvl--) {
    $categories[$lvl + 1] = $cursor?->children
        ?? \App\Models\Category::visible()->whereNull("parent_id")->get();
    $cursor = $cursor?->parent;
}
ksort($categories);
@endphp

<x-browser.category-tree-lvl :category="$category" />
