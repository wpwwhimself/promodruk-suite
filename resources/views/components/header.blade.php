<header class="flex-right spread padded">
    <div class="flex-right center-both">
        <x-logo />

        <x-button action="none" label="Wszystkie produkty" icon="box" onclick="toggleCategoryDropdown(this)" />
    </div>

    <search>
        <form action="{{ route('search') }}" method="post" class="flex-right middle">
            @csrf
            <input id="query" type="text" placeholder="Wyszukaj produkty..." name="query" />
            <x-button action="submit" label="" icon="search" />
        </form>
    </search>

    <div class="flex-right">
        <x-button :action="route('cart')" :label="count(session('cart', [])) ?: ''" icon="cart" />
    </div>
</header>

<script>
const toggleCategoryDropdown = (btn) => {
    btn.classList.toggle("active")
    document.getElementById("category-dropdown").classList.toggle("visible")
}
</script>
