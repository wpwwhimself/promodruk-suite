@extends("layouts.admin")
@section("title", "Cechy")

@section("content")

<x-magazyn-section title="Niestandardowe cechy">
    <x-slot:buttons>
        <x-button :action="route('alt-attributes-edit')" label="Dodaj" />
    </x-slot:buttons>

    <p>
        Lista cech podstawowych, na podstawie których mogą być określane warianty produktu.
        Stanowią alternatywę dla wariantowania po kolorze.
    </p>

    <div class="grid" style="--col-count: 3">
        @forelse ($altAttributes as $attribute)
        <x-attributes.alt.tile :attribute="$attribute" />
        @empty
        <p class="ghost">Brak cech niestandardowych</p>
        @endforelse
    </div>
</x-magazyn-section>

<x-magazyn-section title="Kolory">
    <x-slot:buttons>
        @foreach ([
            [route("primary-colors-list"), "Kolory nadrzędne", true],
            [route("main-attributes-prune"), "Usuń nieużywane", userIs("Administrator")],
        ] as [$route, $label, $conditions])
            @if ($conditions)
            <a class="button" href="{{ $route }}">{{ $label }}</a>
            @endif
        @endforeach
    </x-slot:buttons>

    <p>
        Poniżej znajdują się kolory dostawców zaimportowane w toku synchronizacji.
        Produkty przechowywane w systemie posiadają przypisaną nazwę koloru, odpowiadającą jednemu z poniższych.
        Jeśli ten kolor nie posiada koloru nadrzędnego, systemy pokażą jego oryginalną nazwę wraz z kafelkiem błędnego koloru:
    </p>
    <x-variant-tile />
    <p>
        W przeciwnym wypadku kafelek pokazywać będzie informacje o kolorze nadrzędnym.
    </p>

    <div>
        @php
            $data = (request("show") == "missing")
                ? $mainAttributes->whereNull("primary_color_id")
                : (request("show") == "filled"
                    ? $mainAttributes->whereNotNull("primary_color_id")
                    : $mainAttributes
                )
        @endphp

        <hr>

        <div class="flex-right middle">
            <p>Wyświetlam {{ $data->count() }} pozycji, z czego {{ $data->whereNotNull("primary_color_id")->count() }} posiada przypisany kolor nadrzędny</p>
            @foreach ([
                [route("attributes"), "Pokaż wszystkie", !empty(request("show"))],
                [route("attributes", ["show" => "missing"]), "Pokaż nieopisane", request("show") != "missing"],
                [route("attributes", ["show" => "filled"]), "Pokaż opisane", request("show") != "filled"],
            ] as [$route, $label, $conditions])
                @if ($conditions)
                <a class="button" href="{{ $route }}">{{ $label }}</a>
                @endif
            @endforeach
        </div>

        <search>
            <form class="flex-right center middle">
                <x-input-field type="text"
                    label="Szukaj koloru po nazwie lub kodzie produktu, który go posiada..."
                    name="main_attr_q" :value="request('main_attr_q')"
                />

                <x-button action="submit" label="Filtruj" />

                @if (request("main_attr_q"))
                <x-button :action="route('attributes')" label="Wyczyść" />
                @endif
            </form>
        </search>

        <div class="grid" style="--col-count: 4;">
            @forelse ($data as $attribute)
            <div>
                <div class="flex-right middle">
                    <span>{{ $attribute->id }}</span>
                    <x-variant-tile :color="$attribute->primaryColor" />
                    <a href="{{ route("main-attributes-edit", $attribute->id) }}">{{ $attribute->name }}</a>

                    @isset ($productExamples[$attribute->name])
                    <small class="ghost">
                        {{ $productExamples[$attribute->name]->reduce(fn ($carry, $exs) => $carry + $exs->count()) }} prod.,
                        {{ $productExamples[$attribute->name]->count() }} dost.
                    </small>
                    @endisset
                </div>
            </div>
            @empty
            <span class="ghost">Brak {{ empty(request("show")) ? "zdefiniowanych" : "" }} cech podstawowych</span>
            @endforelse
        </div>
    </div>
</x-magazyn-section>

@endsection
