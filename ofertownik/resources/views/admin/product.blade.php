@extends("layouts.shipyard.admin")
@section("title", $product->family_name)
@section("subtitle", "Edycja produktu")

@section("content")

<x-shipyard.app.form :action="route('update-products')" method="post" class="flex down">
    <input type="hidden" name="id" value="{{ $product->product_family_id }}" />
    <input type="hidden" name="front_id" value="{{ $product->front_id }}">
    <input type="hidden" name="_family_prefixed_id" value="{{ $product->family_prefixed_id }}">

    <div class="grid but-mobile-down" style="--col-count: 2;">
        <x-shipyard.app.card title="Ustawienia lokalne" icon="home">
            <x-shipyard.ui.field-input :model="$product" field-name="visible" />
            <x-shipyard.ui.field-input :model="$product" field-name="hide_family_sku_on_listing" />
            <x-shipyard.ui.field-input :model="$product" field-name="show_price" />
            <x-shipyard.ui.field-input :model="$product" field-name="extra_description" />
            <x-category-selector :selected-categories="$product->categories" />

            <x-shipyard.app.h lvl="3" :icon="model_icon('product-tags')">Tagi</x-shipyard.app.h>
            <table>
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Widoczny od</th>
                        <th>Widoczny do</th>
                        <td>Działa</td>
                        <th>Zawieszony</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($product->tags ?? [] as $tag)
                    <tr>
                        <td>{{ $tag->option_label }}</td>
                        <td {{ Popper::pop($tag->details->start_date ?? "") }}>{{ $tag->details->start_date ? Carbon\Carbon::parse($tag->details->start_date)->diffForHumans() : "—" }}</td>
                        <td {{ Popper::pop($tag->details->end_date ?? "") }}>{{ $tag->details->end_date ? Carbon\Carbon::parse($tag->details->end_date)->diffForHumans() : "—" }}</td>
                        <td>
                            @if ($product->activeTag?->id == $tag->id)
                            <span class="accent success">
                                <x-shipyard.app.icon name="check" />
                            </span>
                            @else
                            <span class="accent error">
                                <x-shipyard.app.icon name="close" />
                            </span>
                            @endif
                        </td>
                        <td>
                            @if ($tag->details->disabled)
                            <span class="accent danger">
                                <x-shipyard.app.icon name="tag-off" />
                            </span>
                            @else
                            <span class="ghost">
                                <x-shipyard.app.icon name="tag" />
                            </span>
                            @endif
                        </td>
                        <td>
                            <x-shipyard.ui.button
                                icon="pencil"
                                pop="Edytuj tag"
                                action="none"
                                onclick="openModal('update-tag-for-products', {
                                    product_family_id: '{{ $product->product_family_id }}',
                                    details_id: '{{ $tag->details->id }}',
                                    tag_id: '{{ $tag->id }}',
                                    start_date: '{{ $tag->details->start_date }}',
                                    end_date: '{{ $tag->details->end_date }}',
                                    disabled: '{{ $tag->details->disabled }}',
                                });"
                                class="tertiary"
                            />
                            <x-shipyard.ui.button
                                action="submit"
                                name="mode"
                                value="delete_tag|{{ $tag->id }}"
                                pop="Usuń tag"
                                icon="delete"
                                class="danger"
                            />
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="ghost">Brak tagów dla tych produktów</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="flex right spread and-cover">
                <x-shipyard.ui.button
                    icon="plus"
                    label="Dodaj tag"
                    action="none"
                    onclick="openModal('update-tag-for-products', {
                        product_family_id: '{{ $product->product_family_id }}',
                    });"
                    class="tertiary"
                />
            </div>
        </x-shipyard.app.card>

        <div class="flex down">
            <x-shipyard.app.card title="Warianty" :icon="model_icon('products')">
                <div class="grid" style="grid-template-columns: 1fr 1fr">
                    @foreach ($family as $variant)
                    <span>
                        <img src="{{ $variant->cover_image ?? $variant->thumbnails->first() }}" alt="{{ $variant->name }}" class="inline"
                            {{ Popper::pop("<img src='" . ($variant->cover_image ?? $variant->thumbnails->first()) . "' />") }}
                        >
                        <a href="{{ route('product', ['id' => $variant->front_id]) }}" target="_blank">{{ $variant->front_id }}</a>
                        <x-variant-tile :variant="collect($variant->color)" :pop="$variant->color['name']" />

                        @if (count($variant->sizes ?? []) > 1)
                        <x-size-tag :size="collect($variant->sizes)->first()" /> - <x-size-tag :size="collect($variant->sizes)->last()" />
                        @elseif (count($variant->sizes ?? []) == 1)
                        <x-size-tag :size="collect($variant->sizes)->first()" />
                        @endif

                        @if (!$variant->is_synced_with_magazyn)
                        <x-product.not-synced-badge />
                        @endif
                    </span>
                    @endforeach
                </div>

                <div class="flex right spread and-cover">
                    <x-shipyard.ui.button
                        icon="open-in-new"
                        label="Edytuj w Magazynie"
                        :action="env('MAGAZYN_URL').'admin/products/edit-family/'.$product->family_prefixed_id"
                        target="_blank"
                        :disabled="$family->every(fn ($v) => !$v->is_synced_with_magazyn)"
                    />
                </div>
            </x-shipyard.app.card>

            <x-shipyard.app.card title="Powiązane produkty" icon="link">
                <input type="hidden" name="related_product_ids" value="{{ $product->related_product_ids }}">

                <ul role="related_products_list">
                </ul>

                <div class="flex right spread middle">
                    <select name="related_product_search">
                        <option value="">Wyszukaj (nazwa/SKU)</option>
                        @foreach ($potential_related_products as $prp)
                        <option value="{{ $prp['id'] }}"
                            data-name="{{ $prp['name'] }}"
                            data-thumbnail="{{ $prp['thumbnail'] }}"
                        >
                            {{ $prp['text'] }}
                        </option>
                        @endforeach
                    </select>
                    <img class="thumbnail hidden" role="related_product_search_thumbnail">
                    <x-shipyard.ui.button
                        icon="plus" label="Dodaj"
                        class="hidden tertiary"
                        action="none"
                        role="related_product_search_confirm"
                    />
                </div>

                <script defer>
                const rpSearch = document.querySelector("[name='related_product_search']");
                const rpSearchThumbnail = document.querySelector("[role='related_product_search_thumbnail']");
                const rpSearchConfirm = document.querySelector("[role='related_product_search_confirm']");
                const rpList = document.querySelector("[role='related_products_list']");

                const rpSearchDropdown = new Choices(rpSearch, {
                    itemSelectText: null,
                    noResultsText: "Brak wyników",
                    shouldSort: false,
                    removeItemButton: true,
                });

                rpSearch.addEventListener("change", function (ev) {
                    rpSearchThumbnail.src = this.selectedOptions[0].dataset.thumbnail;
                    rpSearchThumbnail.classList.toggle("hidden", !ev.target.value);
                    rpSearchConfirm.classList.toggle("hidden", !ev.target.value);
                });
                rpSearchConfirm.addEventListener("click", function () {
                    rpModify(rpSearch.value);
                    rpSearchDropdown.removeActiveItems();
                    rpSearchThumbnail.classList.add("hidden");
                    rpSearchConfirm.classList.add("hidden");
                });

                function rpListAdd(family_id) {
                    const option = rpSearch.querySelector(`option[value='${family_id}']`);
                    rpList.append(fromHTML(`<li data-id="${family_id}" class="flex-right middle">
                        <img src="${option.dataset.thumbnail}" alt="${option.dataset.name}" class="inline" />
                        ${option.textContent}
                        <x-shipyard.ui.button class="tertiary" icon="delete" label="Usuń" action="none" onclick="rpModify('${family_id}', true)" />
                    </li>`));
                }
                function rpListRemove(family_id) {
                    rpList.querySelector(`li[data-id="${family_id}"]`).remove();
                }
                function rpModify(family_id, remove = false) {
                    let current_values = document.querySelector("[name='related_product_ids']").value.split(";").filter(Boolean);

                    if (remove) {
                        current_values = current_values.filter(id => id != family_id);
                        rpListRemove(family_id);
                    } else {
                        current_values.push(family_id);
                        rpListAdd(family_id);
                    }

                    document.querySelector("[name='related_product_ids']").value = current_values.join(";");
                }

                // init
                document.querySelector("[name='related_product_ids']").value.split(";").filter(Boolean).forEach(rpListAdd);
                </script>
            </x-shipyard.app.card>
        </div>
    </div>

    <x-slot:actions>
        <div class="card">
            <x-shipyard.ui.button
                icon="content-save"
                label="Zapisz"
                class="primary"
                action="submit"
                name="mode"
                value="save"
            />
            <x-shipyard.ui.button
                icon="delete"
                label="Usuń"
                class="danger"
                action="submit"
                name="mode"
                value="delete"
            />
            <x-shipyard.ui.button
                icon="arrow-left"
                label="Wróć"
                :action="route('admin.model.list', ['model' => 'products'])"
            />
        </div>
    </x-slot:actions>
</x-shipyard.app.form>

@endsection
