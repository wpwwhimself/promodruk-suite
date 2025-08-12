@props([
    'type' => 'text',
    'name',
    'label' => null,
    'autofocus' => false,
    'required' => false,
    "disabled" => false,
    "value" => null,
    "small" => false,
    "hints" => null,
    "columnTypes" => [],
])

<div {{
    $attributes
        // ->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "placeholder", "small"])))
        ->merge(["for" => $name])
        ->class(["input-small" => $small, "input-container"])
    }}>

    @if($type != "hidden" && $label)
    <label for="{{ $name }}">{{ $label }}</label>
    @endif

    @if ($type == "TEXT")
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $autofocus ? "autofocus" : "" }}
        {{ $required ? "required" : "" }}
        {{ $disabled ? "disabled" : "" }}
        {{ $attributes->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "class"]))) }}
        {{-- onfocus="highlightInput(this)" onblur="clearHighlightInput(this)" --}}
    >{{ html_entity_decode($value) }}</textarea>
    @elseif ($type == "dummy")
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    <input type="text" disabled value="{{ $value }}">
    @elseif ($type == "JSON")
    <input type="hidden" name="{{ $name }}" value="{{ $value ? json_encode($value) : null }}">
    <table data-name="{{ $name }}" data-columns="{{ count($columnTypes) }}">
        <thead>
            <tr>
                @foreach (array_keys($columnTypes) as $key)
                <th>{{ $key }}</th>
                @endforeach
                <th></th>
            </tr>
        </thead>

        <tbody>
            @foreach ($value ?? [] as $key => $val)
            <tr>
                @php $i = 0; @endphp
                @switch (count($columnTypes))
                    @case (2)
                    {{-- key-value array --}}
                    @foreach ($columnTypes as $t)
                    <td class="rounded">
                        <input type="{{ $t }}" value="{{ ($i++ == 0) ? $key : $val }}" onchange="JSONInputUpdate('{{ $name }}')" {{ $disabled ? "disabled" : "" }} />
                    </td>
                    @endforeach
                    @break

                    @case (1)
                    {{-- simple array --}}
                    <td class="rounded">
                        <input type="{{ current($columnTypes) }}" value="{{ $val }}" onchange="JSONInputUpdate('{{ $name }}')" {{ $disabled ? "disabled" : "" }} />
                    </td>
                    @break

                    @default
                    {{-- array of arrays --}}
                    @foreach ($columnTypes as $t)
                    <td class="rounded">
                        <input type="{{ $t }}" {{ $t == "checkbox" && $val[$i] ? "checked" : "" }} value="{{ $val[$i++] }}" onchange="JSONInputUpdate('{{ $name }}')" {{ $disabled ? "disabled" : "" }} />
                    </td>
                    @endforeach
                @endswitch

                @if (!$disabled)
                <td><span icon="delete" class="button phantom interactive" onclick="JSONInputDeleteRow('{{ $name }}', this)">Usuń</span></td>
                @endif
            </tr>
            @endforeach
        </tbody>

        @unless ($disabled)
        <tfoot>
            <tr role="new-row">
                @foreach ($columnTypes as $t)
                <td class="rounded">
                    <input type="{{ $t }}" onchange="JSONInputUpdate('{{ $name }}')"
                        onkeydown="JSONInputWatchForConfirm('{{ $name }}', event);"
                        onblur="JSONInputAddRow('{{ $name }}', )"
                        value="{{ $t == "checkbox" ? "1" : "" }}"
                    />
                </td>
                @endforeach

                <td>
                    <span icon="plus" class="button accent background secondary interactive" onclick="JSONInputAddRow('{{ $name }}')">Dodaj</span>
                    <span icon="delete" class="button phantom interactive hidden" onclick="JSONInputDeleteRow('{{ $name }}', this)">Usuń</span>
                </td>
            </tr>
        </tfoot>
        @endunless
    </table>
    @else
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        @if ($type == "checkbox" && $value)
        checked
        @endif
        {{ $attributes->merge(["value" => ($type == "checkbox") ? "1" : html_entity_decode($value)]) }}
        {{ $autofocus ? "autofocus" : "" }}
        {{ $required ? "required" : "" }}
        {{ $disabled ? "disabled" : "" }}
        {{ $hints ? "autocomplete=off" : "" }}
        {{ $attributes->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "class"]))) }}
        {{-- onfocus="highlightInput(this)" onblur="clearHighlightInput(this)" --}}
    />
    @endif

    @if ($hints)
    <div class="hints flex-right wrap"></div>
    @endif
</div>
