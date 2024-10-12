<div id="showcase" class="flex-down center rounded max-width-wrapper">
    <div class="flex-right center">
        @if (getSetting("showcase_side_text"))
        <div style="align-content: center;">{!! getSetting("showcase_side_text") !!}</div>
        @endif

        <video src="{{ asset("storage/meta/showcase.mp4") }}"
            autoplay
            muted
            loop
        />
    </div>
</div>
