@extends("layouts.main")
@section("title", "Kategorie główne")

@section("before-main")
@if (userCanSeeWithSetting("showcase_visible"))
<x-showcase />
@endif
@endsection

@section("content")

<script>
function getCategory(category_id = "") {
    const loader = document.querySelector(`#category-browser [role="loader"]`);
    const container = document.querySelector("#category-browser .contents");
    const sidebarOuter = document.querySelector(`[role="sidebar-categories"]`);
    const sidebarLoader = sidebarOuter.querySelector(`[role="loader"]`);
    const sidebarContainer = sidebarOuter.querySelector(`[role="list"]`);

    loader.classList.remove("hidden");
    container.classList.add("ghost");

    fetch(`/api/front/category/${category_id}`, {
        headers: {
            whoami: "{{ Auth::id() }}",
        },
    })
        .then(res => res.json())
        .then(({data, tiles, sidebar}) => {
            // fill tiles
            container.innerHTML = tiles;

            // update sidebar
            sidebarLoader.classList.add("hidden");
            sidebarContainer.innerHTML = sidebar;

            // misc
            document.querySelector(`#showcase`).classList.toggle("hidden", category_id != "");
            window.scrollTo({top: 0, behavior: "smooth"});
            document.title = [data?.name ?? "Kategorie główne", "{{ setting('app_name') }}"].join(" | ");
            window.history.pushState({tiles: tiles, sidebar: sidebar}, null, data ? `/kategorie/${data?.slug}` : "/");

            reapplyPopper();
        })
        .catch(err => {
            console.log(err);
        })
        .finally(() => {
            loader.classList.add("hidden");
            container.classList.remove("ghost");
        });
}
</script>

<div id="category-browser">
    <x-loader />
    <div class="contents"></div>
</div>

<script defer>
// init
getCategory({{ $category?->id }});
// ensure navigation works normally
window.addEventListener("popstate", event => {
    console.log(event.state);
    const {tiles, sidebar} = event.state;
    if (tiles && sidebar) {
        document.querySelector(`#category-browser .contents`).innerHTML = tiles;
        document.querySelector(`[role='sidebar-categories'] [role="list"]`).innerHTML = sidebar;
    }
});
</script>

{{-- ♻️ Ukrycie tytułu strony ♻️ --}}
<script>
document.querySelector("main > div:has(h1)").remove();
</script>
{{-- ♻️ Ukrycie tytułu strony ♻️ --}}

@endsection
