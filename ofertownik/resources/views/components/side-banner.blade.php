@if (userCanSeeWithSetting("side_banner_visible"))
<div id="side-banner">
    @if (setting("side_banner_heading"))
    <h2>{{ setting("side_banner_heading") }}</h2>
    @endif

    @switch (setting("side_banner_mode"))
        @case ("film")
            @php
            $film_path = collect(Storage::disk('public')->files('meta/showcase/film'))
                ->filter(fn ($file) => Str::endsWith($file, '.mp4'))
                ->sort()
                ->map(fn ($file) => asset("storage/$file"))
                ->first();
            @endphp

            <div class="flex-right but-mobile-down center">
                @if ($film_path)
                <video src="{{ $film_path }}"
                    autoplay
                    muted
                    loop
                />
                @endif
            </div>
            @break

        @case ("carousel")
            @php
            $imgs = collect(Storage::disk("public")->files("meta/showcase/side-carousel"))
                ->filter(fn ($file) => Str::endsWith($file, [".jpg", ".jpeg", ".png"]))
                ->sort()
                ->map(fn ($file) => asset("storage/$file"))
                ->values();
            @endphp

            <x-carousel :imgs="$imgs" />
            @break
    @endswitch
</div>
@endif
