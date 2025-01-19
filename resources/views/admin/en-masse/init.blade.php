@extends("layouts.admin")
@section("title", "Operacje masowe")

@section("content")

<form action="{{ route('en-masse-execute') }}" method="post" class="flex-down">
    @csrf
    <input type="hidden" name="model" value="{{ $model }}">
    <input type="hidden" name="ids" value="{{ $ids }}">


    <x-tiling count="2" class="stretch-items">
        <x-tiling.item title="Elementy" icon="box">
            <ul>
                @foreach ($items as $i => $item)
                <li>
                    <img src="{{ collect($item->thumbnails)->first() }}" class="inline" {{ Popper::pop("<img src='" . collect($item->thumbnails)->first() . "' />") }}>
                    {{ $item->name }}
                </li>
                @if ($i > 20) ... Razem: {{ count($items) }} wariantów produktów @break @endif
                @endforeach
            </ul>
        </x-tiling.item>

        <x-tiling.item title="Operacje" icon="edit">
            @foreach ($operations as $op)
            <div class="framed padded">
                <x-input-field type="radio" name="operation" :value="$op['op']" :label="$op['name']" />
                <div class="options">
                @switch ($op["type"])
                    @case("radio")
                        @foreach ($op["options"] as $label => $value)
                        <x-input-field type="radio" name="option" :value="$value" :label="$label" />
                        @endforeach
                    @break
                @endswitch
                </div>
            </div>
            @endforeach
        </x-tiling.item>
    </x-tiling>

    <div class="flex-right center">
        <x-button action="submit" label="Wykonaj" class="danger" />
    </div>
</form>

<style>
.input-container + .options { display: none; }
.input-container:has(input[name="operation"]:checked) + .options { display: block; }
</style>

@endsection
