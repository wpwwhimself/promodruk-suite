<aside>
    <h2>Kategorie</h2>

    <ul>
    @foreach ($categories as $category)
        <x-sidebar.category :category="$category" />
    @endforeach
    </ul>
</aside>
