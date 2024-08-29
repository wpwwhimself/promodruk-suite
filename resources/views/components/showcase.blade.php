<div id="showcase" class="flex-down center rounded" onmouseenter="clickOnEmbed()">
    <h2 class="animatable">{{ getSetting("showcase_top_heading") }}</h2>
    <div class="flex-right center-both">
        <span>{{ getSetting("showcase_side_text") }}</span>

        @php
        $content = getSetting("showcase_content");
        if (!Str::startsWith($content, "<iframe")) {
            $content = '<iframe width="560" height="315" src="https://www.youtube.com/embed/'
                . Str::between($content, "v=", "&")
                . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
        }
        @endphp
        {!! $content !!}
    </div>
</div>

<script>
const clickOnEmbed = () => {
    const embed = document.querySelector("#showcase iframe");
    embed.click()
}
</script>
