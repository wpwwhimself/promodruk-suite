<header class="flex-right spread padded">
    <x-logo />

    <search>
        <form action="{{ route('search') }}" method="post" class="flex-right middle">
            @csrf
            <input id="query" type="text" placeholder="Wyszukaj produkty..." name="query" />
            <x-button action="submit" label="" icon="search" />
        </form>
    </search>

    <div class="flex-right">
        @php $cart_count = count(session('cart', [])); @endphp
        <x-button :action="route('cart')" :label="'Koszyk zapytań' . ($cart_count ? ' ('.$cart_count.')' : '')" icon="cart" icon-right />
    </div>
</header>
