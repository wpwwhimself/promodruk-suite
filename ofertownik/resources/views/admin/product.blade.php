@extends("layouts.admin")
@section("title", implode(" | ", [$product->family_name ?? "Nowy produkt", "Edycja produktu"]))

@section("content")

<form action="{{ route('update-products') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $product->product_family_id }}" />
    <input type="hidden" name="front_id" value="{{ $product->front_id }}">
    <input type="hidden" name="_family_prefixed_id" value="{{ $product->family_prefixed_id }}">

    <x-tiling count="2" class="stretch-tiles">
        <x-tiling.item title="Warianty" icon="copy">
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
                </span>
                @endforeach
            </div>

            <div class="flex-right center">
                <x-button :action="env('MAGAZYN_URL').'admin/products/edit-family/'.$product->family_prefixed_id" target="_blank" label="Edytuj w Magazynie" icon="box"
                    :disabled="!$product->is_synced_with_magazyn"
                />
            </div>
        </x-tiling.item>

        <x-tiling.item title="Ustawienia lokalne" icon="home">
            <x-multi-input-field label="Widoczny" name="visible" :value="$product?->visible ?? 2" :options="VISIBILITIES" />
            <x-input-field type="checkbox" name="hide_family_sku_on_listing" label="Ukryj SKU rodziny na listingu" :value="$product?->hide_family_sku_on_listing" />
            <x-ckeditor name="extra_description" label="Dodatkowy opis" :value="$product?->extra_description" />

            <h3>Tagi</h3>
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
                        <td>{{ $tag }}</td>
                        <td {{ Popper::pop($tag->details->start_date ?? "") }}>{{ $tag->details->start_date ? Carbon\Carbon::parse($tag->details->start_date)->diffForHumans() : "—" }}</td>
                        <td {{ Popper::pop($tag->details->end_date ?? "") }}>{{ $tag->details->end_date ? Carbon\Carbon::parse($tag->details->end_date)->diffForHumans() : "—" }}</td>
                        <td><input type="checkbox" disabled {{ $product->activeTag?->id == $tag->id ? "checked" : "" }} /></td>
                        <td>
                            <div class="flex-right middle">
                                <input type="checkbox" disabled {{ $tag->details->disabled ? "checked" : "" }} />
                                <x-button
                                    :action="route('product-tag-enable', ['product_family_id' => $product->product_family_id, 'tag_id' => $tag->id, 'enable' => $tag->details->disabled])"
                                    label="Zmień"
                                    icon="power"
                                />
                            </div>
                        </td>
                        <td>
                            <x-button action="submit" name="mode" value="delete_tag|{{ $tag->id }}" label="Usuń" icon="delete" class="danger" />
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="ghost">Brak utworzonych tagów</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td>
                            <select name="new_tag[id]">
                                <option value="">— Wybierz... —</option>
                                @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="new_tag[start_date]"></td>
                        <td><input type="date" name="new_tag[end_date]"></td>
                        <td></td>
                        <td></td>
                        <td><x-button action="submit" name="mode" value="save" label="Dodaj" icon="plus" /></td>
                    </tr>
                </tfoot>
            </table>
        </x-tiling.item>

        <x-tiling.item title="Kategorie" icon="inbox" style="overflow: visible;">
            <x-category-selector :selected-categories="$product->categories" />
        </x-tiling.item>

        <x-tiling.item title="Powiązane produkty" icon="link">
            <input type="hidden" name="related_product_ids" value="{{ $product->related_product_ids }}">

            <ul role="related_products_list">
            </ul>

            <div class="flex-right spread middle">
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
                <x-button
                    icon="plus" label="Dodaj"
                    class="hidden"
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
                    <x-button icon="delete" label="Usuń" action="none" onclick="rpModify('${family_id}', true)" />
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
        </x-tiling.item>
    </x-tiling>

    <div class="flex-right center">
        <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
        <x-button action="submit" name="mode" value="delete" label="Usuń" icon="delete" class="danger" />
    </div>
    <div class="flex-right center">
        <x-button :action="route('products')" label="Wróć" icon="arrow-left" />
    </div>
</form>

@endsection
