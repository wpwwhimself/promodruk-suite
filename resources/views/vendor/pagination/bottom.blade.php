<nav role="pagination" aria-label="{{ __('Pagination Navigation') }}">
    <form class="flex-right" id="bottom-pagination" onsubmit="filtersCleanup(this);">

    <div>
        <p>
            Wyświetlanie
            @if ($paginator->firstItem())
                <span>{{ $paginator->firstItem() }}</span>
                -
                <span>{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            z
            <span>{{ $paginator->total() }}</span>
        </p>
    </div>

    @if ($paginator->hasPages())
    <div class="flex-right center">
        {{-- Previous Page Link --}}
        <x-button :action="$paginator->onFirstPage() ? null : $paginator->previousPageUrl()" label="Poprzednia" hide-label icon="arrow-left" />

            <input name="page"
            min="1" max="{{ $paginator->lastPage() }}"
            value="{{ $paginator->currentPage() }}"
            onchange="this.form.submit()"
        >
        <span style="align-self: center">z {{ $paginator->lastPage() }} stron</span>

        {{-- Next Page Link --}}
        <x-button :action="$paginator->hasMorePages() ? $paginator->nextPageUrl() : null" label="Następna" hide-label icon="arrow-right" />
    </div>
    @endif

    </form>
</nav>
