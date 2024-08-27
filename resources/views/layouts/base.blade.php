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
    const revealInput = (name) => {
        document.querySelector(`[name="${name}"]`).closest(".input-container").classList.remove("hidden")
        document.querySelector(`.input-container[for="${name}"]`).classList.add("hidden")
    }
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
            @yield("insides")
        </div>
    </div>
    <x-footer />

    @foreach (["success", "error"] as $status)
    @if (session($status))
    <x-popup-alert :status="$status" />
    @endif
    @endforeach

    @if (session("fullscreen-popup"))
    <x-fullscreen-popup :data="session('fullscreen-popup')" />
    @endif

    @bukScripts(true)

    <x-button class="refresh-page-btn"
        label="Odśwież treść" icon="refresh"
        action="none"
        onclick="location.reload(true)"
    />
</body>
</html>
