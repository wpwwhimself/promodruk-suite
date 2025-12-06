@extends("layouts.shipyard.admin")
@section("title", "Zarządzanie przypisaniem produktów")
@section("subtitle", "Administracja")

@section("sidebar")

<x-shipyard.app.card title="Kategorie" :icon="model_icon('categories')" role="sidebar-categories">
    <x-shipyard.app.loader />
</x-shipyard.app.card>

@endsection

@section("content")

<x-shipyard.app.card>
    Ten panel pozwala na masowe przepisanie produktów z jednej kategorii do innej.
</x-shipyard.app.card>

@if (!$category->id)
<x-shipyard.app.card>
    <span class="accent secondary">Wybierz kategorię, aby wyświetlić produkty do niej przypisane.</span>
</x-shipyard.app.card>

@else
<x-shipyard.app.form
    :action="route('products-category-assignment-submit')"
    method="post"
>
    <input type="hidden" name="category_id" value="{{ $category->id }}">

    <x-shipyard.app.section
        :title="$category->name"
        :icon="model_icon('categories')"
    >
        <div class="grid" style="--col-count: 2">
            <x-shipyard.app.card title="Produkty" :icon="model_icon('products')">
                <x-shipyard.app.section
                    title="Filtry"
                    icon="filter"
                    :extended="false"
                >
                    <x-shipyard.ui.input type="text" name="filter" label="Nazwa, SKU" oninput="filterProducts()" hint="Użyj ; do dodawania kolejnych wyszukiwań do tej samej listy" />
                    <x-shipyard.ui.input type="number" min="0" step="0.01" name="filter" label="Minimalna cena" oninput="filterProducts()" />
                    <x-shipyard.ui.input type="number" min="0" step="0.01" name="filter" label="Maksymalna cena" oninput="filterProducts()" />
                    <x-shipyard.ui.input type="checkbox" name="filter" label="Pokazuj produkty bez ceny" oninput="filterProducts()" checked />

                    <script>
                    function filterProducts() {
                        let [query, price_min, price_max] = Array.from(document.querySelectorAll("[name='filter']")).map(input => input.value);
                        let price_nulls = document.querySelector(`[name='filter'][type='checkbox']`).checked;
                        price_min = (price_min == "") ? 0 : parseFloat(price_min);
                        price_max = (price_max == "") ? Infinity : parseFloat(price_max);

                        document.querySelectorAll("[role='products'] tr").forEach(row => {
                            const row_q = row.dataset.q.toLowerCase();
                            const row_price = parseFloat(row.dataset.price);

                            let show = true;

                            show &&= (query.length > 0) ? query.toLowerCase().split(";").some(q_string => row_q.includes(q_string)) : true;
                            show &&= (price_nulls && isNaN(row_price)) || row_price >= price_min;
                            show &&= (price_nulls && isNaN(row_price)) || row_price <= price_max;

                            row.classList.toggle("hidden", !show);
                        });
                    }
                    function reSortProducts(col_index = 2) {
                        const current_sort = document.querySelector("[role='products']").dataset.sort;

                        if (current_sort == col_index) {
                            // simple reverse
                            document.querySelectorAll("[role='products'] tr").forEach(row => {
                                row.parentNode.insertBefore(row, row.parentNode.firstChild);
                            });
                            return;
                        }

                        let data_to_sort = [];
                        document.querySelectorAll("[role='products'] tr").forEach(row => {
                            data_to_sort.push({
                                id: row.dataset.id,
                                val: row.children[col_index].textContent,
                            });
                        });

                        data_to_sort = data_to_sort.sort((a, b) => a.val > b.val ? -1 : 1);

                        data_to_sort.forEach(({ id, val }) => {
                            const row = document.querySelector(`[data-id="${id}"]`);
                            row.parentNode.insertBefore(row, row.parentNode.firstChild);
                        });

                        document.querySelector("[role='products']").dataset.sort = col_index;
                    }
                    </script>
                </x-shipyard.app.section>

                <table>
                    <thead>
                        <tr>
                            <th>
                                SKU
                                <span @popper(Odwróć kolejność) onclick="reSortProducts(0)">↕️</span>
                            </th>
                            <th>Nazwa</th>
                            <th>
                                Cena
                                <span @popper(Średnia cena wszystkich wariantów)>(?)</span>
                                <span @popper(Odwróć kolejność) onclick="reSortProducts(2)">↕️</span>
                            </th>
                            <th><input type="checkbox" onchange="selectAllVisible(this)" /></th>
                        </tr>
                    </thead>
                    <tbody role="products" data-sort="2">
                    @foreach ($category->products->groupBy("product_family_id")->sortBy(fn ($v) => $v->avg("price")) as $family_id => $variants)
                        @php
                        $variant = $variants->first();
                        $avg_price = ($variants->some(fn ($v) => $v->price !== null)) ? round($variants->avg("price"), 2) : null;
                        @endphp
                        <tr data-id="{{ $variant["family_prefixed_id"] }}" data-q="{{ $variant->family_prefixed_id }} {{ $variant["name"] }}" data-price="{{ $avg_price }}">
                            <td>
                                <a href="{{ route('products-edit', ['id' => $variant->family_prefixed_id]) }}" target="_blank">{{ $variant->family_prefixed_id }}</a>
                            </td>
                            <td>
                                <img src="{{ $variant->cover_image ?? $variant->thumbnails->first() }}" alt="{{ $variant["name"] }}" class="inline"
                                    {{ Popper::pop("<img class='thumbnail' src='" . ($variant->cover_image ?? $variant->thumbnails->first()) . "' />") }}
                                >
                                {{ $variant["name"] }}
                            </td>
                            <td>{{ $avg_price }}</td>
                            <td><input type="checkbox" name="ids[]" value="{{ $variant["product_family_id"] }}" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <script>
                selectAllVisible = (btn) => {
                    document.querySelectorAll("tr:not(.hidden) input[name^=ids]")
                        .forEach(input => input.checked = btn.checked)
                }
                </script>
            </x-shipyard.app.card>

            <x-shipyard.app.card title="Kategorie docelowe" :icon="model_icon('categories')">
                <x-category-selector />
            </x-shipyard.app.card>
        </div>
    </x-shipyard.app.section>

    <x-slot:actions>
        <x-shipyard.app.card>
            <x-shipyard.ui.button
                action="submit"
                name="mode"
                value="attach"
                label="Dodaj nowe przypisania do istniejących"
                icon="database-plus"
                class="primary"
            />
            <x-shipyard.ui.button
                action="submit"
                name="mode"
                value="sync"
                label="Zastąp istniejące przypisania nowymi"
                icon="database-sync"
                class="danger"
            />
        </x-shipyard.app.card>
    </x-slot:actions>
</x-shipyard.app.form>
@endif

<script>
// list categories
let categories;

const openSidebarCategory = (breadcrumbs_cat_ids) => {
    let cat = undefined;
    breadcrumbs_cat_ids?.forEach(breadcrumb_id => {
        cat_id = breadcrumb_id;
        cat = (cat === undefined)
            ? categories.find(c => c.id == breadcrumb_id)
            : cat.children.find(c => c.id == breadcrumb_id);
    });

    let level = (breadcrumbs_cat_ids ?? []).length + 1;
    let target
    let children
    let fn

    if (level != 1)
    {
        target = document.querySelector(`[role='sidebar-categories'] li[data-id="${cat_id}"]`)
        children = cat.children
    }
    else
    {
        target = document.querySelector(`[role='sidebar-categories'] .loader`)
        children = categories.filter(cat => cat.parent_id == null)
    }

    if (children.length == 0) {
        const target = `{{ route("products-category-assignment-manage") }}/${cat.id}`;
        if (window.location.href != target) {
            window.location.href = target;
        }
        return
    }

    document.querySelector("[role='sidebar-categories'] [role='loader']")?.remove();

    target.after(fromHTML(`<ul data-level="${level}">
        ${children.map(ccat => `<li class="${[
            "animatable",
            "interactive",
            ccat.depth == 0 && "bold",
            @if ($category->id) ccat.id == {{ $category->id }} && "active", @endif
        ].filter(Boolean).join(' ')}"
            data-id="${ccat.id}"
            data-link="${ccat.link}"
            onclick="tryOpen([${[...(breadcrumbs_cat_ids ?? []), ccat.id].join(', ')}])"
        >
            ${ccat.depth > 0 ? `<x-ik-chevron-right class="left" />` : ''}
            ${ccat.name}
            ${ccat.children.length > 0 ? `<x-ik-chevron-left class="right" />` : ''}
        </li>`).join("")}
    </ul>`))
}

function tryOpen(breadcrumbs_cat_ids) {
    if (document.querySelector(`[data-id="${breadcrumbs_cat_ids[breadcrumbs_cat_ids.length - 1]}"] + ul[data-level]`)) return;
    openSidebarCategory(breadcrumbs_cat_ids);
}

// init
const cat_loader = document.querySelector("[role='sidebar-categories'] .loader");

cat_loader.classList.remove("hidden");
fetch("/api/categories/for-front")
    .then(res => res.json())
    .then(data => {
        categories = data;

        // init categories
        openSidebarCategory(null);

        @if ($category)
        let breadcrumbs = [];
        {!! $category->tree->pluck("id")->toJson() !!}.forEach((cat_id, i, arr) => {
            // if (i == arr.length - 1) return // don't open last cat, it causes reloading loop if last cat is leaf
            breadcrumbs.push(cat_id);
            openSidebarCategory(breadcrumbs);
        })
        @endif
    })
    .finally(() => cat_loader.classList.add("hidden"));
</script>

@endsection
