<div id="showcase" class="flex-down center rounded max-width-wrapper" onmouseenter="clickOnEmbed()">
    <h2 class="animatable">{{ getSetting("showcase_top_heading") }}</h2>
    <div class="flex-right center-both">
        @if (getSetting("showcase_side_text"))
        <div>{!! getSetting("showcase_side_text") !!}</div>
        @endif

        <video src="{{ asset("storage/meta/showcase.mp4") }}"
            autoplay
            muted
            loop
        />
    </div>
</div>
