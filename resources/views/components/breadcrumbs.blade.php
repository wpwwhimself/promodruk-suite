@props([
    "category"
])

@php
if (!($category instanceof \Illuminate\Database\Eloquent\Collection)) {
    $category = collect([$category]);
}
@endphp

<div class="breadcrumbs">
@foreach ($category as $cat)
<ul class="flex-right align">
    @foreach ($cat->tree as $level)
    <li>
        <a href="{{ route('category-'.$level->id) }}"
            @if ($level->depth + 1 == $cat->tree->count()) class="accent" @endif
        >
            {{ $level->name }}
        </a>
    </li>

    @if ($level->depth + 1 != $cat->tree->count()) <li>»</li> @endif
    @endforeach
</ul>
@endforeach
</div>
