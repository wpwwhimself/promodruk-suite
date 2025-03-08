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
            <x-input-field type="text" label="Nazwa" name="name" :value="$attribute?->name" />
            </div>
            <x-input-field type="TEXT" label="Opis" name="description" :value="$attribute?->description" />

            @if ($productExamples)
            <h3>Produkty z o tym kolorze</h3>
            <div class="grid" style="--col-count: 2">
                @foreach ($productExamples as $product)
                <div>
                    <x-product.tile :product="$product" />
                </div>
                @endforeach
            </div>
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
                    :value="$attribute?->color != 'multi' ? Str::before($attribute?->color, ';') : ''"
                    onchange="updateColor()"
                />
            </div>

            <div class="color-type-container">
                <x-input-field type="color" label="Kolor drugorzędny" name="color_set_2"
                    :value="$attribute?->color != 'multi' ? Str::after($attribute?->color, ';') : ''"
                    onchange="updateColor()"
                />
            </div>

            <div class="color-type-container grid" style="--col-count: 3;">
                @foreach ($primaryColors as $pcl)
                <div class="flex-right middle">
                    <input type="radio" id="color_set_3" name="color_set_3" value="{{ $pcl->id }}" {{ $attribute?->color == ("@".$pcl->id) ? "checked" : "" }} onchange="updateColor()" />
                    <label for="color_set_3">{{ $pcl->name }}</label>
                    <x-color-tag :color="$pcl" />
                </div>
                @endforeach
            </div>

            <script>
            const inputs = document.querySelectorAll("[name^=color_set_]")
            const mainInput = document.querySelector("input[name=color]")

            const hideContainer = (el) => el.closest(".color-type-container").classList.add("hidden")
            const showContainer = (el) => el.closest(".color-type-container").classList.remove("hidden")

            const changeMode = (mode) => {
                const whichToShow = {
                    "none": [0, 0, 0],
                    "multi": [0, 0, 0],
                    "single": [1, 0, 0],
                    "double": [1, 1, 0],
                    "related": [0, 0, 1],
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
                    mode == "related" ? `@${vals[2]}` :
                    mode == "single" ? vals[0] :
                    mode == "double" ? vals.join(";") :
                    mode == "multi" ? "multi" :
                    ""
            }

            changeMode("{{ $attribute?->color_mode }}")
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

<style>
.input-container.hidden {
    display: none;
}
</style>

@endsection
