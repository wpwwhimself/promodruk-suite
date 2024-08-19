@props([
    "tabs",
])

@if ($tabs)

<div class="tabs flex-down">
    <nav class="flex-right">
        @foreach ($tabs as $tab_name => $cells)
        <li class="padded button-like animatable"
            onclick="switchToTab('{{ $tab_name }}')"
            data-tab-name="{{ $tab_name }}"
        >
            {{ $tab_name }}
        </li>
        @endforeach
    </nav>

    @foreach ($tabs as $tab_name => $cells)
    <div class="content-box flex-down hidden" data-tab-name="{{ $tab_name }}">
        @foreach ($cells as $cell)
        @switch($cell["type"])
            @case("table")
                <table>
                    <tbody>
                        @foreach ($cell["content"] as $key => $value)
                        <tr>
                            <th>{{ $key }}</th>
                            <td>{{ $value }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @break
            @case("tiles")
                @foreach ($cell["content"] as $label => $link)
                <x-button :action="$link" target_blank :label="$label" icon="download" />
                @endforeach
                @break
            @default
            {{ $cell }}
        @endswitch
        @endforeach
    </div>
    @endforeach
</div>

<script>
const switchToTab = (tab_name) => {
    document.querySelectorAll(`.tabs > nav > li`).forEach(li => li.classList.remove("active"))
    document.querySelectorAll(`.tabs > .content-box`).forEach(box => box.classList.add("hidden"))

    document.querySelector(`.tabs > nav > li[data-tab-name="${tab_name}"]`).classList.add("active")
    document.querySelector(`.tabs > .content-box[data-tab-name="${tab_name}"]`).classList.remove("hidden")
}

// engage first tab
switchToTab(`{{ array_keys($tabs)[0] }}`)
</script>

@endif
