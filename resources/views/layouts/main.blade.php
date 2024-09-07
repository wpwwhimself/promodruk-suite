@extends("layouts.base")

@section("insides")
<x-sidebar />
<main style="padding-inline: 0;">
    @yield("before-title")

    <h1>
        @yield("title")
        <small class="ghost">@yield("subtitle")</small>
    </h1>

    @if (
        getSetting("welcome_text_visible") == 2
        || getSetting("welcome_text_visible") == 1 && Route::currentRouteName() == "home"
    )
    {!! \Illuminate\Mail\Markdown::parse(getSetting("welcome_text_content") ?? "") !!}
    @endif

    @yield("interactives")

    <div id="content">
        @yield("content")
    </div>

    @yield("interactives")
</main>
@endsection
