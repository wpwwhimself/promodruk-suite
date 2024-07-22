@props([
    "category"
])

<ul class="breadcrumbs flex-right align">
    @foreach ($category->tree as $level)
    <li>{{ $level->name }}</li>

    @if ($level->depth + 1 != $category->tree->count())
    <li>»</li>
    @endif
    @endforeach
</ul>
