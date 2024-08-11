@props([
    "category"
])

@php
$product = Route::currentRouteName() == "product" ? \App\Models\Product::find(Route::current()->parameters()["id"]) : null;
$is_part_of_current_tree = (
    $category->allChildren->map(fn($cat) => "category-$cat->id")->contains(Route::currentRouteName())
    || Route::currentRouteName() == "product" && (
        $product->categories->flatMap(fn ($cat) => $cat->tree)->pluck("id")->contains($category->id)
        || $product->categories->pluck("id")->contains($category->id)
    )
);
@endphp

<a {{ $attributes->class([
    "animatable",
    "active" => Route::currentRouteName() =="category-".$category->id,
    "accent" => $is_part_of_current_tree,
    "bold" => $category->depth == 0,
]) }}
@if ($category->external_link)
    href="{{ $category->external_link }}" _target="blank"
@else
    href="{{ route('category-'.$category->id) }}"
@endif
>
    @if ($category->depth > 0) <x-ik-chevron-right class="left" /> @else <x-ik-chevron-down class="right" /> @endif
    {{ $category->name }}
</a>
@if ($is_part_of_current_tree)
    <ul>
        @foreach ($category->children as $child)
            <x-sidebar.category :category="$child" />
        @endforeach
    </ul>
@endif
