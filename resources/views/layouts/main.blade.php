<!DOCTYPE html>
<html lang="en">
<x-layout.head />
<body class="flex-down center">
    <script>
    // categories for listings
    const categories = {!! json_encode(
        \App\Models\Category::with("children.children")
            ->where("visible", true)
            ->orderBy("ordering")
            ->orderBy("name")
            ->get()
    ) !!}
    </script>

    <div id="header-wrapper" class="flex-down animatable">
        <x-header />
        <x-top-nav
            :pages="\App\Models\TopNavPage::ordered()->get()->map(fn ($page) => [$page->name, $page->slug])"
            with-all-products
        />
    </div>
    <div id="main-wrapper" class="max-width-wrapper">
        <div id="sidebar-wrapper" class="grid">
            <x-sidebar />
            <main>

            @yield("before-title")

            <h1>
                @yield("title")
                <small class="ghost">@yield("subtitle")</small>
            </h1>

            @if (
                getSetting("welcome_text_visible") == 2
                || getSetting("welcome_text_visible") == 1 && Route::currentRouteName() == "home"
            )
            {!! \Illuminate\Mail\Markdown::parse(getSetting("welcome_text_content")) !!}
            @endif

            @yield("interactives")

            <div id="content">
                @yield("content")
            </div>

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
