@props([
    "text" => "Ładowanie"
])

<div id="loader" {{ $attributes->class([
    "flex-right",
    "center",
    "middle",
    "hidden",
]) }}
    onclick="this.classList.add('hidden')"
>
    <h2>{{ $text }}...</h2>
</div>

<style>
#loader {
    position: fixed;
    z-index: 9999;
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
const toggleLoader = (text = "Ładowanie") => {
    document.querySelector("#loader").classList.toggle("hidden")
    document.querySelector("#loader h2").innerHTML = text + "..."
}
</script>
