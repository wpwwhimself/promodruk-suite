<!DOCTYPE html>
<html lang="en">
<x-layout.head />
<body>
    <div id="main-wrapper" class="flex-down">
        <x-header />
        <x-top-nav :pages="\App\Models\TopNavPage::ordered()->get()->map(fn ($page) => [$page->name, $page->slug])" />
        <div id="sidebar-wrapper" class="grid">
            <x-sidebar />
            <main>

            @if (
                getSetting("welcome_text_visible") == 2
                || getSetting("welcome_text_visible") == 1 && Route::currentRouteName() == "home"
            )
            {!! \Illuminate\Mail\Markdown::parse(getSetting("welcome_text_content")) !!}
            @endif

            @yield("content")
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
