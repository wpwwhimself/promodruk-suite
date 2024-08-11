@props([
    "pages",
    "withAllProducts" => false,
])

<nav id="top-nav" class="flex-right">
    @if ($withAllProducts)
    <x-button action="none" label="Wszystkie produkty" icon="hamburger" onclick="toggleCategoryDropdown(this)" />
    @endif

    @foreach ($pages as [$label, $route])
    <a href="{{ route($route) }}"
        class="{{ Route::currentRouteName() == $route ? "active" : "" }} padded animatable flex-right middle"
    >
        {{ $label }}
    </a>
    @endforeach
</nav>

<script>
    const toggleCategoryDropdown = (btn) => {
        btn.classList.toggle("active")
        document.getElementById("category-dropdown").classList.toggle("visible")
    }
</script>
