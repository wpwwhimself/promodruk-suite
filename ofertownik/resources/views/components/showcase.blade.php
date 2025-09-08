<div id="showcase" class="flex-down center rounded max-width-wrapper">
    @switch (getSetting("showcase_mode"))
        @case ("film")
            @php
            $film_path = collect(Storage::disk('public')->files('meta/showcase/film'))
                ->filter(fn ($file) => Str::endsWith($file, '.mp4'))
                ->sort()
                ->map(fn ($file) => asset("storage/$file"))
                ->first();
            @endphp

            <div class="flex-right but-mobile-down center">
                @if (getSetting("showcase_side_text"))
                <div style="align-content: center;">{!! getSetting("showcase_side_text") !!}</div>
                @endif

                @if ($film_path)
                <video src="{{ $film_path }}"
                    autoplay
                    muted
                    loop
                />
                @endif
            </div>
            @break

        @case ("text")
            @if (getSetting("showcase_full_width_text"))
            <div style="align-content: center;">{!! getSetting("showcase_full_width_text") !!}</div>
            @endif
            @break

        @case ("carousel")
            @php
            $imgs = collect(Storage::disk("public")->files("meta/showcase/carousel"))
                ->filter(fn ($file) => Str::endsWith($file, [".jpg", ".jpeg", ".png"]))
                ->sort()
                ->map(fn ($file) => asset("storage/$file"))
                ->values();
            @endphp

            <x-carousel :imgs="$imgs" />
            @break
    @endswitch
</div>
