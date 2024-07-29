@props([
    "category"
])

<a class="animatable"
@if ($category->external_link)
    href="{{ $category->external_link }}" _target="blank"
@else
    href="{{ route('category-'.$category->id) }}"
@endif
>
    {{ $category->name }}
</a>
@if ($category->children->count())
    <ul>
        @foreach ($category->children as $child)
            <x-sidebar.category :category="$child" />
        @endforeach
    </ul>
@endif
