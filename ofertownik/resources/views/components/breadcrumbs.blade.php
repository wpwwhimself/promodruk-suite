@props([
    "category",
    "product" => null,
])

@php
if (!($category instanceof \Illuminate\Database\Eloquent\Collection)) {
    $category = collect([$category]);
}
@endphp

<div class="breadcrumbs">
@foreach ($category as $cat)
<ul class="flex-right align wrap">
    <li><a href="{{ route('home') }}">Strona główna</a></li>
    <li>»</li>

    @foreach ($cat->tree as $level)
    <li>
        <a href="{{ route('category-'.$level->id) }}"
            @if ($level->depth + 1 == $cat->tree->count() && !$product) class="accent" @endif
        >
            {{ $level->name }}
        </a>
    </li>

    @if ($level->depth + 1 != $cat->tree->count()) <li>»</li> @endif
    @endforeach

    @if ($product)
    <li>»</li>
    <li><a href="{{ route('product', ['id' => $product->id]) }}" class="accent">{{ $product->name }}</a></li>
    @endif
</ul>
@endforeach
</div>
