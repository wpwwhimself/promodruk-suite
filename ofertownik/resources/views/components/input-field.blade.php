@props([
    'type' => "text", 'name', 'label',
    "value" => null,
    "small" => false,
    "hint" => null,
    "clickToEdit" => false,
    "clickToSave" => false,

    "columnTypes" => null,
    "disabled" => false,
])

<div {{
    $attributes
        ->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "placeholder", "small"])))
        ->merge(["for" => $name])
        ->class(["input-small" => $small, "input-container"])
    }}>

    @if($type != "hidden")
    <label for="{{ $type == "radio" ? $name."-".$value : $name }}"
        @if ($attributes->has("required")) {{ Popper::pop("Pole jest wymagane") }} @endif
    >
        {{ $label }}
        @if ($attributes->has("required")) <span class="danger">*</span> @endif
        @if ($clickToSave) <br><span class="clicker clickable" style="display: block; text-align: right;" onclick="submitNearestForm(this)">[zapisz]</span> @endif
    </label>
    @endif

    <div>
        @if ($type == "TEXT")
        <textarea
            name="{{ $name }}"
            id="{{ $name }}"
            {{ $attributes->merge() }}
        >{{ html_entity_decode($value) }}</textarea>
        @elseif ($type == "dummy")
        <div class="flex-right">
            <pre>{{ $value ?? "—" }}</pre>
            @if ($clickToEdit) <span class="ghost" onclick="revealInput('{{ $name }}')">[edytuj]</span> @endif
        </div>
        {{-- <input type="hidden" name="{{ $name }}" value="{{ $value }}"> --}}

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
                            <input type="{{ $t }}" value="{{ $val[$i++] }}" onchange="JSONInputUpdate('{{ $name }}')" {{ $disabled ? "disabled" : "" }} />
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
            id="{{ $type == "radio" ? $name."-".$value : $name }}"
            {{ $attributes->merge([
                "checked" => $type == "checkbox" && $value,
                "value" => html_entity_decode($value),
            ]) }}
        />
        @endif

        @if ($hint)
        <span class="ghost">{{ $hint }}</span>
        @endif
    </div>
</div>
