@props([
    "tag",
])

<div class="tag product-tag {{ $tag->type }}" style="--ribbon-color: {{ $tag->ribbon_color }}; --text-color: {{ $tag->ribbon_text_color }}; --text-size: {{ $tag->ribbon_text_size_pt }}pt;">
    <span class="ribbon">
        {{ $tag->ribbon_text }}
    </span>
</div>
