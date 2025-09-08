<header>
    <div class="max-width-wrapper flex-right spread">
        <div class="flex-right middle">
            <x-logo />
        </div>

        <form action="{{ route('search') }}" method="post">
            @csrf
            <search class="flex-right middle">
                <input id="query" type="text" placeholder="Wyszukaj produkty..." name="query" value="{{ request('query') }}"
                    onfocus="toggleSearchHint(true)" onblur="toggleSearchHint(false)"
                />
                <x-button action="submit" label="" icon="search" />
            </search>
            <span role="search-hint" class="ghost hidden">
                Powyżej wpisz frazy do wyszukiwania w nazwie lub opisie produktu.
                Dodaj kolejne słowa po spacjach, aby doprecyzować wyszukiwanie.
            </span>
            <script>
            function toggleSearchHint(show = true)
            {
                document.querySelector("[role='search-hint']").classList.toggle("hidden", !show);
            }
            </script>
        </form>

        <div class="flex-right">
            @php $cart_count = count(session('cart', [])); @endphp
            <x-button :action="route('cart')"
                label="Koszyk zapytań" icon="cart" icon-right
                :badge="$cart_count"
                class="but-mobile-hide-label"
            />
        </div>
    </div>
</header>
