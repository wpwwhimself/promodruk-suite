@props([
    "pages",
    "withAllProducts" => false,
])

<nav id="top-nav">
    <div class="max-width-wrapper flex-right">
        @if ($withAllProducts)
        <x-button action="none" label="Strona główna" icon="home-alt" class="home-btn" onclick="window.location.href = '{{ route('home') }}';" />
        <x-button action="none" label="Wszystkie produkty" icon="hamburger"
            onmouseenter="toggleCategoryDropdown('add')"
            onmouseleave="toggleCategoryDropdown('remove')"
            onclick="toggleCategoryDropdown()"
            class="all-products-btn"
        >
            <x-category-dropdown />
        </x-button>
        @endif

        @foreach ($pages as [$label, $route])
        <a href="{{ route($route) }}"
            class="{{ Route::currentRouteName() == $route ? "active" : "" }} padded animatable flex-right middle"
        >
            {{ $label }}
        </a>
        @endforeach
    </div>
</nav>

<script>
    const dropdown = document.getElementById("category-dropdown")
    const btn = document.querySelector(".all-products-btn")

    const toggleCategoryDropdown = (method = "toggle") => {
        btn.classList[method]("active")
        dropdown.classList[method]("visible")
    }

    window.onclick = (event) => {
        if (!event.target.matches("#category-dropdown li, .all-products-btn")) toggleCategoryDropdown('remove')
    }
</script>

<style>
@media screen and (min-width: 700px) {
    .home-btn {
        width: calc(12px * 17 - 1 * 1em);
        justify-content: left !important;
        padding-left: 0;
    }
    .all-products-btn {
        margin-right: 4.5em;
        padding-left: 0;
        position: relative;
    }
}
</style>
