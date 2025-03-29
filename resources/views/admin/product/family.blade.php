@extends("layouts.admin")
@section("title", implode(" | ", [$family->name ?? "Nowa rodzina produktów", "Edycja rodziny produktów"]))

@php
use App\Http\Controllers\AdminController;
@endphp

@section("content")

@if (!$isCustom) <span class="ghost">Produkt <strong>{{ $family?->name }}</strong> został zaimportowany od zewnętrznego dostawcy i części jego parametrów nie można edytować</span> @endif

<x-app.loader text="Przetwarzanie" />

<form action="{{ route('update-product-families') }}" method="post" class="flex-down" enctype="multipart/form-data">
    @csrf

    @if (!$isCustom) <input type="hidden" name="id" value="{{ $family?->id }}"> @endif

    <x-magazyn-section title="Rodzina">
        <x-slot:buttons>
            @if ($family && $isCustom)
            <x-button
                label="Kopiuj na nową rodzinę"
                :action="route('products-edit', ['copy_from' => $family->id])"
                target="_blank"
            />
            @endif
        </x-slot:buttons>

        <div class="grid" style="--col-count: 2">
            <x-multi-input-field
                label="Pochodzenie (dostawca)" name="source"
                :options="$suppliers"
                :value="$family?->source" empty-option="wybierz"
                :required="$isCustom"
                :disabled="!$isCustom"
                onchange="loadCategories(event.target.value)"
            />
            <script src="{{ asset("js/supplier-categories-selector.js") }}" defer></script>
            <x-input-field type="text" label="SKU" name="id" :value="$family?->id" :disabled="!$isCustom" />
        </div>
        <div class="grid" style="--col-count: 2">
            <x-input-field type="text" label="Nazwa" name="name" :value="$copyFrom->name ?? $family?->name" :disabled="!$isCustom" />
            <x-suppliers.categories-selector :items="$categories" :value="$copyFrom->original_category ?? $family?->original_category" :editable="$isCustom" />
        </div>
        <x-ckeditor label="Opis" name="description" :value="$copyFrom->description ?? $family?->description" :disabled="!$isCustom" />
    </x-magazyn-section>

    <div class="grid" style="--col-count: 2">
        @if ($family)

        <x-magazyn-section title="Warianty">
            <x-slot:buttons>
                @if ($isCustom)
                <x-button
                    label="Nowy"
                    :action="route('products-edit', ['copy_from' => $family->id])"
                />
                @endif
            </x-slot:buttons>

            <div class="grid" style="--col-count: 2">
                @forelse ($family->products as $product)
                <div>
                    <x-product.tile :product="$product" />
                </div>
                @empty
                <p class="ghost">Brak wariantów</p>
                @endforelse
            </div>
        </x-magazyn-section>

        <x-magazyn-section title="Zdjęcia">
            <input type="hidden" name="images" value="{{ $family->images ? $family->images->join(",") : "" }}">
            <table class="images">
                <thead>
                    <tr>
                        <th>Zdjęcie</th>
                        <th>Nazwa</th>
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                @if ($family->images)
                @foreach ($family->images as $img)
                    <tr attr-name="{{ $img }}">
                        <td><img class="inline" src="{{ url($img) }}" {{ Popper::pop("<img class='thumbnail' src='".url($img)."' />") }} /></td>
                        <td>{{ basename($img) }}</td>
                        <td>
                            @if (Str::startsWith($img, env("APP_URL")) && $isCustom)
                            <span class="clickable" onclick="deleteImage(this)">Usuń</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @endif
                </tbody>
                @if ($isCustom)
                <tfoot>
                    <tr>
                        <td colspan=3><x-input-field type="file" label="Dodaj zdjęcia" name="newImages[]" multiple onchange="submitForm()" /></td>
                    </tr>
                </tfoot>
                @endif
            </table>

            <script>
            const deleteImage = (btn) => {
                let ids = document.querySelector("input[name=images]").value.split(",")
                ids = ids.filter(id => id != btn.closest("tr").getAttribute("attr-name"))
                document.querySelector("input[name=images]").value = ids.join(",")

                btn.closest("tr").remove()
                submitForm()
            }
            </script>

            <h3>Miniatury</h3>

            <input type="hidden" name="thumbnails" value="{{ $family->thumbnails ? $family->thumbnails->join(",") : "" }}">
            <table class="thumbnails">
                <thead>
                    <tr>
                        <th>Zdjęcie</th>
                        <th>Nazwa</th>
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                @if ($family->thumbnails)
                @foreach ($family->thumbnails->filter(fn($img) => $img) as $img)
                    <tr attr-name="{{ $img }}">
                        <td><img class="inline" src="{{ url($img) }}" {{ Popper::pop("<img class='thumbnail' src='".url($img)."' />") }} /></td>
                        <td>{{ basename($img) }}</td>
                        <td>
                            @if (Str::startsWith($img, env("APP_URL")) && $isCustom)
                            <span class="clickable" onclick="deleteImage(this)">Usuń</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @endif
                </tbody>
                @if ($isCustom)
                <tfoot>
                    <tr>
                        <td colspan=3><x-input-field type="file" label="Dodaj zdjęcia" name="newThumbnails[]" multiple onchange="submitForm()" /></td>
                    </tr>
                </tfoot>
                @endif
            </table>

            <script>
            const deleteThumbnail = (btn) => {
                let ids = document.querySelector("input[name=thumbnails]").value.split(",")
                ids = ids.filter(id => id != btn.closest("tr").getAttribute("attr-name"))
                document.querySelector("input[name=thumbnails]").value = ids.join(",")

                btn.closest("tr").remove()
                submitForm()
            }
            </script>
        </x-magazyn-section>

        @endif
    </div>

    @if ($family)
    <x-magazyn-section title="Zakładki">
        <x-slot:buttons>
            @if ($isCustom) <span class="button" onclick="newTab()">Dodaj nową zakładkę</span> @endif
        </x-slot:buttons>

        <x-product.tabs-editor :tabs="$family->tabs" :editable="$isCustom" />
        <script src="{{ asset("js/tabs-editor.js") }}" defer></script>
    </x-magazyn-section>
    @endif

    <div class="section flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($family)
        <button type="submit" name="mode" value="delete" class="danger">Usuń</button>
        @endif
        <a class="button" href="{{ route('products') }}">Wróć</a>
    </div>
</form>

@endsection
