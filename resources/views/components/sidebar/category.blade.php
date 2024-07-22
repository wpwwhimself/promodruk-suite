@props([
    "category"
])

@if ($category->children->count())
    <li class="animatable">{{ $category->name }}</li>
    <ul>
        @foreach ($category->children as $child)
            <x-sidebar.category :category="$child" />
        @endforeach
    </ul>
@else
    <a class="animatable"
    @if ($category->external_link)
        href="{{ $category->external_link }}" _target="blank"
    @else
        href=""
    @endif
    >
        {{ $category->name }}
    </a>
@endif
