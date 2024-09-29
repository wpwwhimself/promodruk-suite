@props([
    "title",
    "showAccept" => false,
])

<div id="dialog" {{ $attributes->class([
    "flex-right",
    "center",
    "middle",
    "hidden",
]) }}>
    <x-app.section :title="$title">
        @if ($slot)
        <div class="contents">
            {{ $slot }}
        </div>
        @endif

        <div class="flex-right center">
            @if ($showAccept)
            <span class="button">OK</span>
            @endif
            <span class="button" onclick="toggleDialog()">Anuluj</span>
        </div>
    </x-app.section>
</div>

<style>
#dialog {
    position: fixed;
    z-index: 9998;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: rgba(0, 0, 0, 0.75);

    & > h2 {
        text-align: center;
        background-color: rgba(0, 0, 0, 0.5);
        padding-block: 0.5em;
        width: 100%;
    }
}
</style>

<script>
const toggleDialog = () => document.getElementById("dialog").classList.toggle("hidden")
</script>
