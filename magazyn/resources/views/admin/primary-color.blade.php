@extends("layouts.admin")
@section("title", implode(" | ", [$attribute->name ?? "Nowa cecha podstawowa", "Edycja cechy podstawowej"]))

@section("content")

<x-shipyard.app.form action="{{ route('primary-color-process') }}" method="post" class="flex down">
    <input type="hidden" name="id" value="{{ $attribute?->id }}">

    <div class="grid" style="--col-count: 2">
        <x-magazyn-section title="Podstawowe informacje" :icon="model_icon('primary-colors')">
            <x-input-field type="text" label="Nazwa" name="name" :value="$attribute?->name" />
            <x-input-field type="TEXT" label="Opis" name="description" :value="$attribute?->description" />
        </x-magazyn-section>

        <x-magazyn-section title="Ustawienia" icon="cog">
            <input type="hidden" name="color" value="{{ $attribute?->color }}">
            <x-multi-input-field :options="\App\Models\PrimaryColor::COLOR_MODES"
                label="Kolor" name="color_mode"
                :value="$attribute?->color_mode"
                onchange="changeMode(event.target.value)"
            />

            <div class="grid" style="--col-count: 3;">
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
            </div>

            <script>
            const inputs = document.querySelectorAll("[name^=color_set_]")
            const mainInput = document.querySelector("input[name=color]")

            const hideContainer = (el) => el.closest(".color-type-container").classList.add("hidden")
            const showContainer = (el) => el.closest(".color-type-container").classList.remove("hidden")

            const changeMode = (mode) => {
                const whichToShow = {
                    "multi": [0, 0, 0],
                    "single": [1, 0, 0],
                    "double": [1, 1, 0],
                    "triple": [1, 1, 1],
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

            changeMode("{{ $attribute?->color_mode ?? "single" }}")

            function primeReload() {
                document.querySelector("#loader").classList.remove("hidden")
                window.onfocus = function () { location.reload(true) }
            }
            </script>
        </x-magazyn-section>
    </div>

    <x-slot:actions>
        <x-shipyard.ui.button action="submit" name="mode" value="save" label="Zapisz" icon="check" class="primary" />
        @if ($attribute)
        <x-shipyard.ui.button action="submit" name="mode" value="delete" class="danger" label="Usuń" icon="delete" />
        @endif
        <x-shipyard.ui.button :action="route('primary-colors-list')" label="Wróć" icon="arrow-left" />
    </x-slot:actions>
</x-shipyard.app.form>

<style>
.input-container.hidden {
    display: none;
}
</style>

@endsection
