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
            <x-sidebar />
            <div>
                @if (
                    getSetting("welcome_text_visible") == 2
                    || getSetting("welcome_text_visible") == 1 && Route::currentRouteName() == "home"
                )
                {!! \Illuminate\Mail\Markdown::parse(getSetting("welcome_text_content")) !!}
                @endif

                @yield("before-title")

                <main class="framed">
                    <div class="grid" style="grid-template-columns: repeat(2, 50%);">
                        <div class="flex-down">
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
