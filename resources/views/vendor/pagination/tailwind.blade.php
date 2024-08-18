<nav role="pagination" aria-label="{{ __('Pagination Navigation') }}" class="flex-right">
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
            onchange="((page) => {
                if(isNaN(page)) return
                window.location.href = `{!! $paginator->url(1) !!}`.replace(/page=[0-9]+/, `page=${page}`)
            })(event.target.value)"
        >
        <span style="align-self: center">z {{ $paginator->lastPage() }} stron</span>

        {{-- Next Page Link --}}
        <x-button :action="$paginator->hasMorePages() ? $paginator->nextPageUrl() : null" label="Następna" hide-label icon="arrow-right" />
    </div>
    @endif

    {{-- <div class="flex-right center middle">
        <label>Na stronie</label>
        @foreach ([25, 50, 100] as $count)
        <x-button :action="$paginator->url(1).'&perPage='.$count" :label="$count" :class="$paginator->perPage() == $count ? 'active' : null" />
        @endforeach
    </div> --}}

    <x-multi-input-field
        :options="$availableSorts"
        label="Sortuj" name="sortBy"
        :value="request('sortBy', 'price')"
        onchange="((sort_by) => {
            window.location.href = `{!! $paginator->url(1) !!}&sortBy=${sort_by}`
        })(event.target.value)"
    />

    @if (isset($availableFilters))
        @foreach ($availableFilters as [$name, $label, $options])
        {{-- @if ($label == "Kolor")
        <div class="input-container">
            <label for="filter">{{ $label }}</label>
            <div class="flex-right wrap">
                @forelse ($options as $color)
                <x-color-tag :color="collect($color)"
                    :link="collect(request('filters'))->get('color') == $color['name']
                        ? preg_replace('/&?filters\[color\]=[a-zA-ZąćęłóśźżĄĆĘŁÓŚŹŻ]+/', '', urldecode($paginator->url(1)))
                        : $paginator->url(1).'&filters[color]='.$color['name']
                    "
                    :active="collect(request('filters'))->get('color') == $color['name']"
                />
                @empty
                <p class="ghost">Brak kolorów</p>
                @endforelse
            </div>
        </div>
        @else --}}
        <x-multi-input-field
            :options="$options"
            :label="$label"
            :name="$name"
            :value="collect(request('filters'))->get($name)"
            onchange="((name, value) => {
                const re = new RegExp(`&?filters\\[${name}\\]=[a-zA-ZąćęłóśźżĄĆĘŁÓŚŹŻ]+`, `gi`)
                window.location.href = (!value)
                    ? `{!! urldecode($paginator->url(1)) !!}`.replace(new RegExp(`&?filters\\[${name}\\]=([a-zA-ZąćęłóśźżĄĆĘŁÓŚŹŻ]|\\s)+`, `gi`), '')
                    : `{!! $paginator->url(1) !!}&filters[${name}]=${value}`
            })(event.target.name, event.target.value)"
            :empty-option="$name == 'availability' ? false : 'wszystkie'"
        />
        {{-- @endif --}}
        @endforeach
    @endif
</nav>
