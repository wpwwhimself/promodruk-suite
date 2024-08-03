@props([
    "category"
])

<a {{ $attributes->class(["animatable", "accent" => Route::currentRouteName() =="category-".$category->id]) }}
@if ($category->external_link)
    href="{{ $category->external_link }}" _target="blank"
@else
    href="{{ route('category-'.$category->id) }}"
@endif
>
    {{ $category->name }}
</a>
@if ($category->children->count() && $category->allChildren->map(fn($cat) => "category-$cat->id")->contains(Route::currentRouteName()))
    <ul>
        @foreach ($category->children as $child)
            <x-sidebar.category :category="$child" />
        @endforeach
    </ul>
@endif
