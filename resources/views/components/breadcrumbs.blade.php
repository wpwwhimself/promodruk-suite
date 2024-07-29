@props([
    "category"
])

<ul class="breadcrumbs flex-right align">
    @foreach ($category->tree as $level)
    <li>
        <a href="{{ route('category-'.$level->id) }}"
            @if ($level->depth + 1 == $category->tree->count()) class="accent" @endif
        >
            {{ $level->name }}
        </a>
    </li>

    @if ($level->depth + 1 != $category->tree->count()) <li>»</li> @endif
    @endforeach
</ul>
