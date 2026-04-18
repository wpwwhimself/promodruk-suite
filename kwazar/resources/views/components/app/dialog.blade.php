@props([
    "title",
])

<div id="dialog" {{ $attributes->class(["modal", "hidden"]) }}>
    <x-shipyard.app.card :title="$title">
        @if ($slot)
        <div class="contents flex down">
            {{ $slot }}
        </div>
        @endif

        <div class="flex right center">
            <span class="button success">OK</span>
            <span class="button tertiary" onclick="toggleDialog()">Anuluj</span>
        </div>
    </x-shipyard.app.card>
</div>

<script>
const toggleDialog = (title = "", contents = "", onok = undefined) => {
    document.querySelector("#dialog").classList.toggle("hidden")
    document.querySelector("#dialog [role='card-title']").innerHTML = title
    document.querySelector("#dialog .contents .contents").innerHTML = contents

    document.querySelector("#dialog .button.success").onclick = () => onok
    (onok)
        ? document.querySelector("#dialog .button.success").classList.remove("hidden")
        : document.querySelector("#dialog .button.success").classList.add("hidden")
}
</script>
