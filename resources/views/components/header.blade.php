<header class="flex-right spread padded">
    <div class="flex-right center-both">
        <x-logo />
        <div>Wszystkie produkty</div>

        <search>
            <form action="{{ route('search') }}" method="post" class="flex-right middle">
                @csrf
                <label for="query">@svg("ik-search")</label>
                <input id="query" type="text" placeholder="Wyszukaj..." name="query" />
            </form>
        </search>
    </div>
    <div class="flex-right">
        <x-button :action="route('cart')" label="Koszyk" icon="cart" :hide-label="true" />
    </div>
</header>
