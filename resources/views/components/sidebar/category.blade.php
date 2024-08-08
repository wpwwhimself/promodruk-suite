@props([
    "category"
])

<a {{ $attributes->class([
    "animatable",
    "active" => Route::currentRouteName() =="category-".$category->id,
    "accent" => $category->children->count() && $category->allChildren->map(fn($cat) => "category-$cat->id")->contains(Route::currentRouteName()),
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
@if ($category->children->count() && $category->allChildren->map(fn($cat) => "category-$cat->id")->contains(Route::currentRouteName()))
    <ul>
        @foreach ($category->children as $child)
            <x-sidebar.category :category="$child" />
        @endforeach
    </ul>
@endif
