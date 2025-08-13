@extends("layouts.base")

@section("insides")
<x-sidebar />
<div>
    @if (
        getSetting("welcome_text_visible") == 2
        || getSetting("welcome_text_visible") == 1 && Route::currentRouteName() == "home"
    )
    {!! \Illuminate\Mail\Markdown::parse(getSetting("welcome_text_content")) !!}
    @endif

    @yield("before-title")

    <main class="framed" style="padding-block: 1em;">
        <div class="grid but-mobile-down and-reversed" style="grid-template-columns: repeat(2, 1fr); --gap: 2em;">
            <div id="left-side" class="flex-down">
                @yield("left-side")
            </div>

            <div id="content">
                <h1>
                    @yield("title")
                    <small class="ghost">@yield("subtitle")</small>
                </h1>
                @yield("content")
            </div>
        </div>
    </main>

    @yield("bottom-side")
</div>
@endsection
