<div id="showcase" class="flex-down center rounded" onmouseenter="clickOnEmbed()">
    <h2 class="animatable">{{ getSetting("showcase_top_heading") }}</h2>
    <div class="flex-right center-both">
        <span>{{ getSetting("showcase_side_text") }}</span>

        <video src="{{ asset("storage/meta/showcase.mp4") }}"
            autoplay
            muted
        />
    </div>
</div>
