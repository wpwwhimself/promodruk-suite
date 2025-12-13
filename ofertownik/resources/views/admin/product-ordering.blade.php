@extends("layouts.shipyard.admin")
@section("title", "Zarządzanie kolejnością produktów")
@section("subtitle", "Administracja")

@section("sidebar")

<x-category.sidebar :category="$category">
    <x-slot:onclick>
    onclick="tryOpen([${[...(breadcrumbs_cat_ids ?? []), ccat.id].join(', ')}])"
    </x-slot:onclick>

    <x-slot:onclickdef>
    function tryOpen(breadcrumbs_cat_ids) {
        if (document.querySelector(`[data-id="${breadcrumbs_cat_ids[breadcrumbs_cat_ids.length - 1]}"] + ul[data-level]`)) return;
        openSidebarCategory(breadcrumbs_cat_ids);
    }
    </x-slot:onclickdef>

    <x-slot:onfinalclick>
    const target = `{{ route("products-ordering-manage") }}/${cat.id}`;
    if (window.location.href != target) {
        window.location.href = target;
    }
    return
    </x-slot:onfinalclick>
</x-category.sidebar>

@endsection

@section("content")

<x-shipyard.app.card>
    Ten panel pozwala na nadanie własnego priorytetu dla produktów wyświetlanych na listingu kategorii.
</x-shipyard.app.card>

@if (!$category->id)
<x-shipyard.app.card>
    <span class="accent secondary">Wybierz kategorię, aby wyświetlić produkty do niej przypisane.</span>
</x-shipyard.app.card>

@else

<x-shipyard.app.form
    :action="route('products-ordering-submit')"
    method="post"
>
    <input type="hidden" name="category_id" value="{{ $category->id }}">

    <x-shipyard.app.section
        :title="$category->name"
        :icon="model_icon('categories')"
    >
        <x-shipyard.app.section
            title="Filtry"
            icon="filter"
            :extended="false"
        >

            <x-shipyard.ui.input type="text" name="filter" label="Filtruj (nazwa, SKU)" oninput="filterProducts(event.target.value)" hint="Użyj ; do dodawania kolejnych wyszukiwań do tej samej listy" />

            <script>
            function filterProducts(query) {
                document.querySelectorAll("[role='products'] li").forEach(row => {
                    const row_q = row.dataset.q.toLowerCase();
                    const show = (query.length > 0)
                        ? query.toLowerCase().split(";").some(q_string => row_q.includes(q_string))
                        : true;
                    row.classList.toggle("hidden", !show);
                });
            }
            </script>
        </x-shipyard.app.section>

        <x-shipyard.app.card>
            <p>Możesz edytować wartości w kolumnie <strong>Priorytet</strong>.</p>
        </x-shipyard.app.card>

        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nazwa</th>
                    <th>Priorytet</th>
                </tr>
            </thead>
            <tbody role="products">
            @foreach ($category->products->groupBy("product_family_id") as $family_id => $variants)
                @php
                $variant = $variants->first();
                @endphp

                <tr data-q="{{ $variant->front_id }} {{ $variant->name }}">
                    <td>
                        <a href="{{ route('products-edit', ['id' => $variant->family_prefixed_id]) }}"
                            target="_blank"
                            class="accent secondary"
                        >
                            {{ $variant->front_id }}
                        </a>
                    </td>
                    <td>
                        <img src="{{ $variant->cover_image ?? $variant->thumbnails->first() }}" alt="{{ $variant->name }}" class="inline"
                            {{ Popper::pop("<img class='thumbnail' src='" . ($variant->cover_image ?? $variant->thumbnails->first()) . "' />") }}
                        >
                        {{ $variant->name }}
                    </td>
                    <td>
                        <input type="number"
                            name="ordering[{{ $family_id }}]"
                            value="{{ $variant->categoryData->ordering }}"
                            placeholder="—"
                        />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </x-shipyard.app.section>

    <x-slot:actions>
        <x-shipyard.app.card>
            <x-shipyard.ui.button
                action="submit"
                label="Zapisz dla tej kategorii"
                icon="database-plus"
                class="primary"
            />
        </x-shipyard.app.card>
    </x-slot:actions>
</x-shipyard.app.form>
@endif

@endsection
