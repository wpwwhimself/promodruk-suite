@props([
    "items" => null,
    "editable" => true,
])

<div id="categories-editor">

<div class="flex-down">
    @forelse ($items ?? [] as $item)
    <span class="button" onclick="deleteCategory(this)">
        <input type="hidden" name="categories[]" value="{{ $item }}">
        {{ $item }}
    </span>
    @empty
    <span class="ghost">Brak zdefiniowanych kategorii</span>
    @endforelse
</div>

<div class="flex-right center middle">
    <x-input-field type="text" label="Dodaj kategorię" name="_category" />
    <span class="button" onclick="addCategory(this)">+</span>
</div>

</div>
