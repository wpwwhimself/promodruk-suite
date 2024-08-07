@extends("layouts.admin")
@section("title", implode(" | ", [$attribute->name ?? "Nowa cecha podstawowa", "Edycja cechy podstawowej"]))

@section("content")

<form action="{{ route('update-main-attributes') }}" method="post">
    @csrf
    <input type="hidden" name="id" value="{{ $attribute?->id }}">

    <x-input-field type="text" label="Nazwa" name="name" :value="$attribute?->name" />
    <x-input-field type="TEXT" label="Opis" name="description" :value="$attribute?->description" />

    <input type="hidden" name="color" value="{{ $attribute?->color }}">
    <x-multi-input-field :options="\App\Models\MainAttribute::COLOR_MODES"
        label="Kolor" name="color_mode"
        :value="$attribute?->color_mode"
        onchange="changeMode(event.target.value)"
    />

    <x-input-field type="color" label="Kolor podstawowy" name="color_set_1"
        class="hidden"
        :value="$attribute?->color != 'multi' ? Str::before($attribute?->color, ';') : ''"
        onchange="updateColor()"
    />
    <x-input-field type="color" label="Kolor drugorzędny" name="color_set_2"
        class="hidden"
        :value="$attribute?->color != 'multi' ? Str::after($attribute?->color, ';') : ''"
        onchange="updateColor()"
    />

    <script>
    const inputs = document.querySelectorAll("input[name^=color_set_]")
    const mainInput = document.querySelector("input[name=color]")

    const hideContainer = (el) => el.closest(".input-container").classList.add("hidden")
    const showContainer = (el) => el.closest(".input-container").classList.remove("hidden")

    const changeMode = (mode) => {
        switch (mode) {
            case "none":
            case "multi":
                inputs.forEach(i => hideContainer(i))
                break
            case "single":
                showContainer(inputs[0])
                hideContainer(inputs[1])
                break
            case "double":
                inputs.forEach(i => showContainer(i))
                break
        }
        updateColor()
    }

    const updateColor = () => {
        const mode = document.querySelector("select[name=color_mode]").value
        const vals = Array.from(inputs).map(i => i.value)

        mainInput.value =
            mode == "single" ? vals[0] :
            mode == "double" ? vals.join(";") :
            mode == "multi" ? "multi" :
            ""
    }

    changeMode("{{ $attribute?->color_mode }}")
    </script>

    <div class="flex-right center">
        <button type="submit" name="mode" value="save">Zapisz</button>
        @if ($attribute)
        <button type="submit" name="mode" value="delete" class="danger">Usuń</button>
        @endif
    </div>
    <div class="flex-right center">
        <a href="{{ route('attributes') }}">Wróć</a>
    </div>
</form>

<style>
.input-container.hidden {
    display: none;
}
</style>

@endsection
