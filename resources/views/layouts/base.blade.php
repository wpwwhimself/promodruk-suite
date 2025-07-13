<!DOCTYPE html>
<html lang="en">
<x-layout.head />
<body class="flex-down center">
    <script>
    // categories for listings
    let categories;
    fetch("/api/categories/for-front")
        .then(res => res.json())
        .then(data => {
            categories = data;

            // init categories
            openSidebarCategory(null, 1);
            openCategory(null, 1);

            // open current category
            @php
            $category = \App\Models\Category::find(Str::afterLast(Route::currentRouteName(), "-"))
                ?? \App\Models\Product::where("front_id", Route::current()?->id)->first()?->categories->first();
            @endphp
            @if ($category)
            {!! $category->tree->pluck("id")->toJson() !!}.forEach((cat_id, i, arr) => {
                // if (i == arr.length - 1) return // don't open last cat, it causes reloading loop if last cat is leaf
                openSidebarCategory(cat_id, i + 2)
            })
            @endif

            primeSidebarCategories();
        })
    const revealInput = (name) => {
        document.querySelector(`[name="${name}"]`).classList.remove("hidden")
        document.querySelector(`[name="${name}"]`).closest(".input-container").classList.remove("hidden")
        document.querySelector(`.hidden-save`)?.classList.remove("hidden")
        document.querySelector(`.input-container[for="${name}"]`).classList.add("hidden")
    }
    const submitNearestForm = (element) => {
        element.closest("form").submit()
    }
    </script>

    <div id="header-wrapper" class="flex-down animatable">
        <x-header />
        <x-top-nav
            :pages="\App\Models\TopNavPage::ordered()
                ->where('show_in_top_nav', true)
                ->get()
                ->map(fn ($page) => [$page->name, $page->slug])"
            with-all-products
        />
    </div>

    @yield("before-main")

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
</body>
</html>
