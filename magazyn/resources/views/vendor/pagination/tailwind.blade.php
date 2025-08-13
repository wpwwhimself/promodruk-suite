@if ($paginator->hasPages())
<nav role="pagination" aria-label="{{ __('Pagination Navigation') }}" id="bottom-pagination">
    <p style="text-align: center;">
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

    @if ($paginator->hasPages())
    <div class="flex-right center middle">
        {{-- Previous Page Link --}}
        @unless ($paginator->onFirstPage())
        <a class="button" href="{{ $paginator->previousPageUrl() }}">←</a>
        @endunless

            <input name="page"
            min="1" max="{{ $paginator->lastPage() }}"
            value="{{ $paginator->currentPage() }}"
            onchange="((page) => {
                if(isNaN(page)) return
                window.location.href = `{!! $paginator->url(1) !!}`.replace(/page=[0-9]+/, `page=${page}`)
            })(event.target.value)"
        >
        <span>z {{ $paginator->lastPage() }} stron</span>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
        <a class="button" href="{{ $paginator->nextPageUrl() }}">→</a>
        @endif
    </div>
    @endif
</nav>

@endif
