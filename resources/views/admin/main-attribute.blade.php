@extends("layouts.admin")
@section("title", implode(" | ", [$attribute->name ?? "Nowa cecha podstawowa", "Edycja cechy podstawowej"]))

@section("content")

<form action="{{ route('update-main-attributes') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="id" value="{{ $attribute?->id }}">

    <div class="grid" style="--col-count: 2">
        <section>
            <div class="grid" style="--col-count: 2">
                <x-input-field type="text" label="Wyświetlane ID" name="display_id" :value="$attribute?->display_id" :placeholder="$attribute?->id" />
                <x-input-field type="text" label="Nazwa oryginalna" name="name" :value="$attribute?->name" />
            </div>
            <x-input-field type="TEXT" label="Opis" name="description" :value="$attribute?->description" />

            @if ($productExamples->count())
            <h2>Produkty z o tym kolorze</h2>
            <div class="scrollable">
                @foreach ($productExamples as $source => $examples)
                <h3>{{ $source ?: "Produkty własne" }}</h3>
                <div class="grid" style="--col-count: 2">
                    @foreach ($examples as $i => $product)
                    @if ($i > 20)
                        <span>... i jeszcze {{ count($examples) - 20 }} pozycji</span>
                        @break
                    @endif
                    <x-attributes.product-highlight :product="$product" />
                    @endforeach
                </div>
                @endforeach
            </div>
            @else
            <p class="ghost">Brak produktów o tym kolorze</p>
            @endif
        </section>

        <section>
            <input type="hidden" name="color" value="{{ $attribute?->color }}">
            <x-multi-input-field :options="\App\Models\MainAttribute::COLOR_MODES"
                label="Kolor" name="color_mode"
                :value="$attribute?->color_mode"
                onchange="changeMode(event.target.value)"
            />

            <div class="color-type-container">
                <x-input-field type="color" label="Kolor podstawowy" name="color_set_1"
                    :value="$attribute?->color != 'multi' ? Str::of($attribute?->color)->matchAll('/(#[0-9a-f]{6})/')[0] ?? '' : ''"
                    onchange="updateColor()"
                />
            </div>

            <div class="color-type-container">
                <x-input-field type="color" label="Kolor drugorzędny" name="color_set_2"
                    :value="$attribute?->color != 'multi' ? Str::of($attribute?->color)->matchAll('/(#[0-9a-f]{6})/')[1] ?? '' : ''"
                    onchange="updateColor()"
                />
            </div>

            <div class="color-type-container">
                <x-input-field type="color" label="Kolor trzeciorzędny" name="color_set_3"
                    :value="$attribute?->color != 'multi' ? Str::of($attribute?->color)->matchAll('/(#[0-9a-f]{6})/')[2] ?? '' : ''"
                    onchange="updateColor()"
                />
            </div>

            <div class="color-type-container grid scrollable" style="--col-count: 3;">
                @if (Str::startsWith($attribute?->color, "@"))
                @php $pcl = $primaryColors->firstWhere("id", Str::after($attribute?->color, '@')) @endphp
                <h2 class="flex-right middle" style="grid-column: span 3;">
                    Ten kolor jest podrzędny do koloru:
                    {{ $pcl->name }}
                    <x-color-tag :color="$pcl" />
                </h2>
                @endif

                @foreach ($primaryColors as $pcl)
                <div class="flex-right middle">
                    <input type="radio" id="color_set_99" name="color_set_99" value="{{ $pcl->id }}" {{ $attribute?->color == ("@".$pcl->id) ? "checked" : "" }} onchange="updateColor()" />
                    <label for="color_set_99">{{ $pcl->name }}</label>
                    <x-color-tag :color="$pcl" />
                </div>
                @endforeach
            </div>

            @if ($attribute)
            <div class="flex-right center">
                <x-button :action="route('main-attributes-edit')" target="_blank" label="Utwórz nowy kolor" onclick="primeReload()" />
            </div>
            @endif

            <script>
            const inputs = document.querySelectorAll("[name^=color_set_]")
            const mainInput = document.querySelector("input[name=color]")

            const hideContainer = (el) => el.closest(".color-type-container").classList.add("hidden")
            const showContainer = (el) => el.closest(".color-type-container").classList.remove("hidden")

            const changeMode = (mode) => {
                const whichToShow = {
                    "none": [0, 0, 0, 0],
                    "multi": [0, 0, 0, 0],
                    "single": [1, 0, 0, 0],
                    "double": [1, 1, 0, 0],
                    "triple": [1, 1, 1, 0],
                    "related": [0, 0, 0, 1],
                }
                whichToShow[mode].forEach((on, i) => (on) ? showContainer(inputs[i]) : hideContainer(inputs[i]))
                updateColor()
            }

            const updateColor = () => {
                const mode = document.querySelector("select[name=color_mode]").value
                const vals = Array.from(inputs)
                    .filter(i =>
                        i.type == "color"
                        || i.type == "radio" && i.checked
                    )
                    .map(i => i.value)

                mainInput.value =
                    mode == "related" ? `@${vals[3]}` :
                    mode == "single" ? vals[0] :
                    mode == "double" ? [vals[0], vals[1]].join(";") :
                    mode == "triple" ? [vals[0], vals[1], vals[2]].join(";") :
                    mode == "multi" ? "multi" :
                    ""
            }

            changeMode("{{ $attribute?->color_mode ?? "none" }}")

            function primeReload() {
                document.querySelector("#loader").classList.remove("hidden")
                window.onfocus = function () { location.reload(true) }
            }
            </script>
        </section>
    </div>

    <div class="section flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($attribute)
        <button type="submit" name="mode" value="delete" class="danger">Usuń</button>
        @endif
        <a class="button" href="{{ route('attributes') }}">Wróć</a>
    </div>
</form>

<x-app.loader text="Poczekaj na wczytanie zmian" />

<style>
.input-container.hidden {
    display: none;
}
</style>

@endsection
