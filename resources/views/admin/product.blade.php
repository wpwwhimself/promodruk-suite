@extends("layouts.admin")
@section("title", implode(" | ", [$product->name ?? "Nowy produkt", "Edycja produktu"]))

@section("content")

<form action="{{ route('update-products') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $product?->id }}" />

    <x-tiling>
        <x-tiling.item title="Ustawienia lokalne" icon="home">
            <x-input-field type="text" label="SKU" name="id" :value="$product?->id" />
            <x-input-field type="checkbox" label="Widoczny" name="visible" :value="$product?->visible ?? true" />
            <x-input-field type="TEXT" label="Dodatkowy opis [md]" name="extra_description" :value="$product?->extra_description" />
        </x-tiling.item>

        @if ($product)
        <x-tiling.item title="Kategorie" icon="inbox">
            <input type="hidden" name="categories" value="{{ $product->categories ? implode(",", $product->categories->pluck("id")->all()) : "" }}" />
            <ul class="categories">
                @foreach ($product->categories as $cat)
                <li cat-id="{{ $cat->id }}">
                    {{ $cat->breadcrumbs }}
                    <small class="clickable" onclick="deleteCategory(this)">(×)</small>
                </li>
                @endforeach
            </ul>

            <div class="flex-down">
                <select>
                    <option value="" select></option>
                    @foreach ($all_categories as $cat)
                    <option value="{{ $cat->id }}" {{ !$cat->visible ? 'disabled' : '' }}>{{ $cat->breadcrumbs }}</option>
                    @endforeach
                </select>
                <span class="button-like clickable" onclick="addCategory(this)">Dodaj</span>
            </div>

            <script>
            const addCategory = (btn) => {
                const new_category_id = btn.closest("div").querySelector("select").value

                // clear adder
                btn.closest("div").querySelector("select").value = "";

                if (document.querySelector("input[name=categories]").value.split(",").includes(new_category_id)) return

                // gather new variant data
                fetch(`/api/categories/${new_category_id}`)
                    .then(res => res.json())
                    .then(cat => {
                        document.querySelector(".categories")
                            .append(fromHTML(`<li cat-id="${cat.id}">
                                ${cat.breadcrumbs}
                                <small class="clickable" onclick="deleteCategory(this)">(×)</small>
                            </li>`))

                        let ids = document.querySelector("input[name=categories]").value.split(",")
                        ids.push(cat.id)
                        document.querySelector("input[name=categories]").value = ids.join(",")
                    })
            }
            const deleteCategory = (btn) => {
                let ids = document.querySelector("input[name=categories]").value.split(",")
                ids = ids.filter(id => id != btn.closest("li").getAttribute("cat-id"))
                document.querySelector("input[name=categories]").value = ids.join(",")

                btn.closest("li").remove()
            }
            </script>
        </x-tiling.item>
        @endif
    </x-tiling>

    <div class="flex-right center">
        <x-button action="submit" name="mode" value="save" label="Zapisz" icon="save" />
        @if ($product)
        <x-button action="submit" name="mode" value="delete" label="Usuń" icon="delete" class="danger" />
        @endif
    </div>
    <div class="flex-right center">
        <x-button :action="route('products')" label="Wróć" icon="arrow-left" />
    </div>
</form>

@endsection
