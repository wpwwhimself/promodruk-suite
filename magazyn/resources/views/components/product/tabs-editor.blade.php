@props([
    "tabs" => null,
    "editable" => true,
])

<div id="tabs">

<input type="hidden" name="tabs" value="{{ $tabs ? json_encode($tabs) : '' }}">

<div class="flex-right wrap">
    @forelse ($tabs ?? [] as $i => $tab)
    <x-magazyn-section title="Zakładka {{ $i + 1 }}" class="tab">
        <x-slot:buttons>
        @if ($editable)
            <span class="button" onclick="newCell({{ $i }})">Dodaj nową komórkę</span>
            <div class="flex-right">
                <span class="button" onclick="deleteTab({{ $i }})">Usuń zakładkę</span>
            </div>
        @endif
        </x-slot:buttons>

        <x-input-field name="tabs_raw[{{ $i }}][name]"
            label="Nazwa"
            :value="$tab['name']"
            :disabled="!$editable"
            onchange="changeTabName({{ $i }}, event.target.value)"
        />
        <div class="flex-down separate-children">
            @foreach ($tab["cells"] as $j => $cell)
            <x-magazyn-section :title="gettype($j) == 'string' ? $j : ('Komórka ' . $j + 1)">
                <x-slot:buttons>
                    @if ($editable)
                    <span class="button" onclick="deleteCell({{ $i }}, {{ $j }})">Usuń komórkę</span>
                    @endif
                </x-slot:buttons>

                <div class="grid" style="--col-count: 2">
                    <x-input-field type="text" name="tabs_raw[{{ $i }}][cells][{{ $j }}][heading]"
                        label="Nagłówek"
                        :value="$cell['heading'] ?? ''"
                        :disabled="!$editable"
                        onchange="changeCellHeading({{ $i }}, {{ $j }}, event.target.value)"
                    />
                    <x-multi-input-field name="tabs_raw[{{ $i }}][cells][{{ $j }}][type]"
                        label="Typ komórki"
                        :value="$cell['type']"
                        :options="['tabela' => 'table', 'tekst' => 'text', 'przyciski' => 'tiles']"
                        :disabled="!$editable"
                        onchange="changeCellType({{ $i }}, {{ $j }}, event.target.value)"
                    />
                </div>

                @switch($cell["type"])
                    @case("table")
                    <table>
                        <thead>
                            <tr>
                                <th>Etykieta</th>
                                <th>Wartość</th>
                                @if ($editable) <th>Akcja</th> @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cell["content"] as $label => $value)
                            <tr>
                                <td>
                                    <x-input-field name="tabs_raw[{{ $i }}][cells][{{ $j }}][content][labels][]"
                                        :value="$label"
                                        :disabled="!$editable"
                                        onchange="updateTableRows({{ $i }}, {{ $j }})"
                                    />
                                </td>
                                <td>
                                    <x-input-field name="tabs_raw[{{ $i }}][cells][{{ $j }}][content][values][]"
                                        :value="$value"
                                        :disabled="!$editable"
                                        onchange="updateTableRows({{ $i }}, {{ $j }})"
                                    />
                                </td>
                                @if ($editable) <td class="clickable" onclick="deleteTableRow(this, {{ $i }}, {{ $j }})">Usuń</td> @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="ghost">Brak wierszy</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if ($editable)
                        <tfoot>
                            <tr>
                                <td class="clickable" onclick="addTableRow({{ $i }}, {{ $j }})">Dodaj wiersz</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                    @break

                    @case("text")
                    <x-ckeditor
                        name="tabs_raw[{{ $i }}][cells][{{ $j }}][content]"
                        :disabled="!$editable"
                        :value="$cell['content']"
                        onchange="updateCellContent({{ $i }}, {{ $j }}, event.target.value)"
                    />
                    @break

                    @case("tiles")
                    <table>
                        <thead>
                            <tr>
                                <th>Etykieta</th>
                                <th>URL</th>
                                @if ($editable) <th>Akcja</th> @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cell["content"] as $label => $value)
                            <tr>
                                <td>
                                    <x-input-field name="tabs_raw[{{ $i }}][cells][{{ $j }}][content][labels][]"
                                        :value="$label"
                                        :disabled="!$editable"
                                        onchange="updateTableRows({{ $i }}, {{ $j }})"
                                    />
                                </td>
                                <td>
                                    <x-input-field name="tabs_raw[{{ $i }}][cells][{{ $j }}][content][values][]"
                                        :value="$value"
                                        :disabled="!$editable"
                                        onchange="updateTableRows({{ $i }}, {{ $j }})"
                                    />
                                </td>
                                @if ($editable) <td class="clickable" onclick="deleteTableRow(this, {{ $i }}, {{ $j }})">Usuń</td> @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="ghost">Brak wierszy</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if ($editable)
                        <tfoot>
                            <tr>
                                <td class="clickable" onclick="addTableRow({{ $i }}, {{ $j }})">Dodaj wiersz</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                    @break
                @endswitch
            </x-magazyn-section>
            @endforeach
        </div>
    </x-magazyn-section>
    @empty
    <p class="ghost">Brak zakładek</p>
    @endforelse
</div>

</div>
