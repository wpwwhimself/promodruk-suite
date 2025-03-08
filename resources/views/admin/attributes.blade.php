@extends("layouts.admin")
@section("title", "Cechy")

@section("content")

<x-magazyn-section title="Cechy podstawowe">
    <x-slot:buttons>
        @foreach ([
            [route("main-attributes-edit"), "Dodaj nową", true],
            [route("main-attributes-prune"), "Usuń nieużywane", userIs("Administrator")],
            [route("attributes"), "Pokaż wszystkie", !empty(request("show"))],
            [route("attributes", ["show" => "missing"]), "Pokaż nieopisane", request("show") != "missing"],
            [route("attributes", ["show" => "filled"]), "Pokaż opisane", request("show") != "filled"],
        ] as [$route, $label, $conditions])
            @if ($conditions)
            <a class="button" href="{{ $route }}">{{ $label }}</a>
            @endif
        @endforeach
    </x-slot:buttons>

    <div class="grid" style="--col-count: 4">
        @php
            $data = (request("show") == "missing")
                ? $mainAttributes->where("color", "")
                : (request("show") == "filled"
                    ? $mainAttributes->where("color", "!=", "")
                    : $mainAttributes
                )
        @endphp
        @forelse ($data as $attribute)
        @unless ($attribute->is_final) @continue @endunless
        <div>
            <div class="flex-right middle">
                <span>{{ $attribute->front_id }}</span>
                <x-color-tag :color="$attribute->final_color" />
                <a href="{{ route("main-attributes-edit", $attribute->id) }}">{{ $attribute->name }}</a>

                @isset ($productExamples[$attribute->name])
                <small class="ghost">({{ $productExamples[$attribute->name]
                    ->map(fn ($exs, $source) => ($source ?: "własne") . ": " . $exs->count())
                    ->join(", ") }})</small>
                @endisset
            </div>

            @if ($attribute->related_colors)
            <ul>
                @foreach ($attribute->related_colors as $rclr)
                <li>
                    <a href="{{ route("main-attributes-edit", $rclr->id) }}">{{ $rclr->name }}</a>
                    @isset ($productExamples[$rclr->name])
                    <small class="ghost">({{ $productExamples[$rclr->name]
                        ->map(fn ($exs, $source) => ($source ?: "własne") . ": " . $exs->count())
                        ->join(", ") }})</small>
                    @endisset
                </li>
                @endforeach
            </ul>
            @endif
        </div>
        @empty
        <span class="ghost">Brak {{ empty(request("show")) ? "zdefiniowanych" : "" }} cech podstawowych</span>
        @endforelse
    </div>
</x-magazyn-section>

<x-magazyn-section title="Cechy dodatkowe">
    <x-slot:buttons>
        <a class="button" href="{{ route("attributes-edit") }}">Dodaj cechę</a>
    </x-slot:buttons>

    <ul>
        @forelse ($attributes as $attribute)
        <li>
            <a href="{{ route("attributes-edit", $attribute->id) }}">{{ $attribute->name }}</a>
            ({{ $attribute->variants()->count() }} wariantów)
        </li>
        @empty
        <li class="ghost">Brak zdefiniowanych cech</li>
        @endforelse
    </ul>
</x-magazyn-section>

@endsection
