@props([
    "title",
])

<div id="dialog" {{ $attributes->class([
    "flex-right",
    "center",
    "middle",
    "hidden",
]) }}>
    <x-app.section :title="$title">
        @if ($slot)
        <div class="contents padded">
            {{ $slot }}
        </div>
        @endif

        <div class="flex-right center">
            <span class="success button">OK</span>
            <span class="danger button" onclick="toggleDialog()">Anuluj</span>
        </div>
    </x-app.section>
</div>

<style>
#dialog {
    position: fixed;
    z-index: 9998;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: rgba(0, 0, 0, 0.75);

    & > section {
        & .title {
            text-align: center;
        }
    }
}
</style>

<script>
const toggleDialog = (title = "", contents = "", onok = undefined) => {
    document.querySelector("#dialog").classList.toggle("hidden")
    document.querySelector("#dialog .title").innerHTML = title
    document.querySelector("#dialog .contents").innerHTML = contents

    document.querySelector("#dialog .button.success").onclick = () => onok
    (onok)
        ? document.querySelector("#dialog .button.success").classList.remove("hidden")
        : document.querySelector("#dialog .button.success").classList.add("hidden")
}
</script>
