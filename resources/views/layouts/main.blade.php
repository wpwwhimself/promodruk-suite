<!DOCTYPE html>
<html lang="en">
<x-layout.head />
<body class="flex-down center">
    <div id="header-wrapper" class="flex-down animatable">
        <x-header />
        <x-top-nav
            :pages="\App\Models\TopNavPage::ordered()->get()->map(fn ($page) => [$page->name, $page->slug])"
            with-all-products
        />
    </div>
    <div id="main-wrapper" class="flex-down">
        <div id="sidebar-wrapper" class="grid">
            <x-category-dropdown />

            <x-sidebar />
            <main>

            @if (
                getSetting("welcome_text_visible") == 2
                || getSetting("welcome_text_visible") == 1 && Route::currentRouteName() == "home"
            )
            {!! \Illuminate\Mail\Markdown::parse(getSetting("welcome_text_content")) !!}
            @endif

            @yield("before-title")

            <h1>
                @yield("title")
                <small class="ghost">@yield("subtitle")</small>
            </h1>

            @yield("interactives")
            @yield("content")
            @yield("interactives")
            </main>
        </div>
    </div>
    <x-footer />

    @foreach (["success", "error"] as $status)
    @if (session($status))
    <x-popup-alert :status="$status" />
    @endif
    @endforeach

    @bukScripts(true)
</body>
</html>
