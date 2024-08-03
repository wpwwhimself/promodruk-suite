<header class="flex-right spread padded">
    <div class="flex-right center-both">
        <x-logo />

        <x-button action="none" label="Wszystkie produkty" icon="box" onclick="toggleCategoryDropdown(this)" />

        <search>
            <form action="{{ route('search') }}" method="post" class="flex-right middle">
                @csrf
                <label for="query">@svg("ik-search")</label>
                <input id="query" type="text" placeholder="Wyszukaj..." name="query" />
            </form>
        </search>
    </div>
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
