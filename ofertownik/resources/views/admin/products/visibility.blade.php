@extends("shipyard::layouts.admin")
@section("title", "Zarządzanie widocznością")
@section("subtitle", "Produkty")

@section("content")
<x-shipyard::app.card
    title="Zarządzanie widocznością"
    subtitle="Ten panel pozwala masowo zmienić widoczność wielu produktów"
    icon="eye"
>
    <x-slot:actions>
        <x-shipyard::ui.button
            icon="book"
            label="Dokumentacja"
            action="/docs/listing-produktow#dh-10"
            target="_blank"
        />
    </x-slot:actions>
</x-shipyard::app.card>

<x-shipyard::app.form :action="route('products-visibility-process')" method="POST">
    <x-shipyard::app.section
        title="Wyszukaj produkty"
        :icon="model_icon('products')"
        :extended="true"
    >
        <div class="grid but-mobile-down" style="--col-count: 3;">
            <x-shipyard::ui.input type="select"
                name="query_mode"
                icon="shape-plus"
                label="Wyszukaj po"
                :select-data="[
                    'options' => [
                        ['label' => 'SKU (od początku)', 'value' => 'sku_start'],
                        ['label' => 'SKU (gdziekolwiek)', 'value' => 'sku'],
                        ['label' => 'nazwie', 'value' => 'name'],
                    ],
                ]"
            />

            <x-shipyard::ui.input type="text"
                name="query_query"
                icon="magnify"
                label="Fraza"
            />

            <x-shipyard::ui.button
                icon="magnify"
                label="Szukaj"
                class="primary"
                action="none"
                onclick="searchProducts()"
            />
        </div>
    </x-shipyard::app.section>

    <x-shipyard::app.card id="results" class="hidden"></x-shipyard::app.card>
    <x-shipyard::app.card id="last-step" class="hidden">
        <div class="flex right center middle">
            <x-shipyard::ui.input type="select"
                name="visibility"
                label="Zmień widoczność wszystkich powyższych produktów na"
                icon="eye"
                :select-data="[
                    'optionsFromConst' => [
                        \App\Models\Product::class,
                        'VISIBILITIES',
                    ],
                ]"
            />
            <x-shipyard::ui.button
                icon="check"
                label="Zmień widoczność"
                class="danger"
                action="submit"
            />
        </div>
    </x-shipyard::app.card>
</x-shipyard::app.form>
@endsection

@section("prepends")
<script>
function searchProducts() {
    const query_mode = document.querySelector("#query_mode").value;
    const query_query = document.querySelector("#query_query").value;

    if (query_query.length < 2) {
        popToast("error", "Fraza jest zbyt krótka (min. 2 znaki)");
        return;
    }

    const results_container = document.querySelector(`#results`);
    const last_step_container = document.querySelector(`#last-step`);
    results_container.classList.remove("hidden");
    results_container.querySelector(".loader").classList.remove("hidden");
    fetchPublic(`/api/products/by-query?` + new URLSearchParams({
        mode: query_mode,
        q: query_query,
    }))
        .then(res => res.json())
        .then(({html}) => {
            results_container.querySelector(".contents").innerHTML = html;
            last_step_container.classList.remove("hidden");
        })
        .catch(err => {
            results_container.querySelector(".contents").innerHTML = `<span class="accent error">Błąd zapytania</span>`;
            last_step_container.classList.add("hidden");
        })
        .finally(() => {
            results_container.querySelector(".loader").classList.add("hidden");
        })
}
</script>
@endsection
