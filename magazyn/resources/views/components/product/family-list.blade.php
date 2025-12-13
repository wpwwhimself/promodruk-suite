@props([
    "families",
])

<div class="grid" style="--col-count: 3">
    @forelse ($families as $family)
    <div>
        <x-product.family :family="$family" />
    </div>
    @empty
    <span class="ghost">Brak produktów do wyświetlenia</span>
    @endforelse
</div>
