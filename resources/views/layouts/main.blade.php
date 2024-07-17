<!DOCTYPE html>
<html lang="en">
<x-layout.head />
<body>
    <div id="main-wrapper" class="flex-down">
        <x-header />
        <x-top-nav :pages="\App\Models\TopNavPage::ordered()->get()->map(fn ($page) => [$page->name, $page->slug])" />
        <main>
        @yield("content")
        </main>
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
