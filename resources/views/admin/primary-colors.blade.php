@extends("layouts.admin")
@section("title", "Kolory nadrzędne")

@section("content")

<x-magazyn-section title="Lista kolorów nadrzędnych">
    <x-slot:buttons>
        @foreach ([
            [route("attributes"), "Wszystkie kolory", true],
            [route("primary-color-edit"), "Dodaj nowy", true],
        ] as [$route, $label, $conditions])
            @if ($conditions)
            <a class="button" href="{{ $route }}">{{ $label }}</a>
            @endif
        @endforeach
    </x-slot:buttons>

    <p>
        To jest lista kolorów nadrzędnych.
        W odróżnieniu od listy <em>Cech podstawowych</em>, są one definiowane ręcznie
        i pozwalają na zebranie wielu definicji kolorów dostawców w pojedyncze, zdefiniowane kolory.
        Jeżeli produkt posiada kolor powiązany z jednym z poniższych kolorów, kafelek z kolorem będzie wyświetlał informacje o kolorze nadrzędnym.
    </p>

    <div>
        <p>Wyświetlam {{ $data->count() }} pozycji</p>

        <div class="grid" style="--col-count: 4">
            @forelse ($data as $attribute)
            <div>
                <div class="flex-right middle">
                    <span>{{ $attribute->id }}</span>
                    <x-color-tag :color="$attribute" />
                    <a href="{{ route("primary-color-edit", $attribute->id) }}">{{ $attribute->name }}</a>

                    {{-- @isset ($productExamples[$attribute->name])
                    <small class="ghost">({{ $productExamples[$attribute->name]
                        ->map(fn ($exs, $source) => ($source ?: "własne") . ": " . $exs->count())
                        ->join(", ") }})</small>
                    @endisset --}}
                </div>
            </div>
            @empty
            <span class="ghost">Brak {{ empty(request("show")) ? "zdefiniowanych" : "" }} kolorów nadrzędnych</span>
            @endforelse
        </div>
    </div>
</x-magazyn-section>

@endsection
