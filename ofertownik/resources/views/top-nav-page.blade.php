@extends("layouts.main")
@section("title", $page->name)

@section("content")

{{ \Illuminate\Mail\Markdown::parse($page->content) }}

<script>
// 🧩 compatibility package with sidebar category browsing 🧩 //
const sidebarOuter = document.querySelector(`[role="sidebar-categories"]`);
const sidebarLoader = sidebarOuter.querySelector(`[role="loader"]`);
const sidebarContainer = sidebarOuter.querySelector(`[role="list"]`);

fetch(`/api/front/category/`)
    .then(res => res.json())
    .then(({data, tiles, sidebar}) => {
        // update sidebar
        sidebarLoader.classList.add("hidden");
        sidebarContainer.innerHTML = sidebar;
    })
    .catch(err => {
        console.log(err);
    });

function getCategory(category_id) {
    window.location.href = `/kategorie/id/${category_id}`;
}
// 🧩 compatibility package with sidebar category browsing 🧩 //
</script>

@endsection
