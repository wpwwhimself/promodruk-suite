<nav role="pagination" aria-label="{{ __('Pagination Navigation') }}">
    <form class="flex-right spread middle but-mobile-down" onsubmit="
        event.preventDefault()
        return;
    ">

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

    {{-- <div class="flex-right center middle">
        <label>Na stronie</label>
        @foreach ([25, 50, 100] as $count)
        <x-button :action="$paginator->url(1).'&perPage='.$count" :label="$count" :class="$paginator->perPage() == $count ? 'active' : null" />
        @endforeach
    </div> --}}

    @if (isset($availableFilters) || isset($availableSorts))
        <x-button action="none"
            label="Filtry i sortowanie" icon="filter-list" icon-set="iconoir"
            class="hidden but-mobile-show" role="filters-toggle"
            onclick="
                document.querySelector(`[role=filters-toggle]`).classList.toggle(`active`)
                document.querySelectorAll(`[role=filter]`).forEach(el => el.classList.toggle(`but-mobile-hide`))
            "
        />

        @isset($availableSorts)
        <x-multi-input-field
            :options="$availableSorts"
            label="Sortuj" name="sortBy"
            :value="request('sortBy', 'price')"
            onchange="this.form.submit();"
            role="filter" class="but-mobile-hide"
        />
        @endisset

        @isset($availableFilters)
        @foreach ($availableFilters as $f)
        @php
        $name = $f[0];
        $label = $f[1];
        $options = $f[2];
        $multi = $f[3] ?? false;
        @endphp

        @if ($multi)
        <div class="input-container but-mobile-hide" role="filter">
            <label>{{ $label }}</label>
            <x-button action="none"
                :label="request('filters.'.$name) ? 'Wybrano' : 'Wybierz'"
                onclick="toggleModal('filter-{{ $name }}')"
                :badge="request('filters.'.$name) ? count(explode('|', request('filters.'.$name, ''))) : null"
            />
        </div>
        <x-modal id="filter-{{ $name }}">
            <h2>Wybierz {{ Str::of($label)->lower() }}</h2>
            <input type="hidden" name="filters[{{ $name }}]" value="{{ request('filters.'.$name, '') }}">

            <div role="options">
                @if ($name == "color")
                    @foreach ($options as $color)
                    <div class="flex-right spread middle">
                        <div>
                            <x-color-tag :color="collect($color)" />
                            <label>{{ $color['name'] }}</label>
                        </div>
                        <input type="checkbox" value="{{ $color['name'] }}" onchange="updateFilterInput('{{ $name }}', this.value)" {{ in_array($color['name'], explode("|", request('filters.'.$name, ''))) ? "checked" : "" }}>
                    </div>
                    @endforeach
                @else
                    @foreach ($options as $value => $label)
                    <div class="flex-right spread middle">
                        <label>{{ $label }}</label>
                        <input type="checkbox" value="{{ $value }}" onchange="updateFilterInput('{{ $name }}', this.value)" {{ in_array($color['name'], explode("|", request('filters.'.$name, ''))) ? "checked" : "" }}>
                    </div>
                    @endforeach
                @endif
            </div>

            <x-button action="none" onclick="this.closest('form').submit()" label="Zapisz zmiany" icon="filter" />
        </x-modal>
        @else
        <x-multi-input-field
            :options="$options"
            :label="$label"
            name="filters[{{ $name }}]"
            :value="collect(request('filters'))->get($name)"
            onchange="this.form.submit();"
            :empty-option="
                $name == 'availability' ? false : (
                $name == 'cat_parent_id' ? 'główne' :
                'wszystkie'
            )"
            role="filter" class="but-mobile-hide"
        />
        @endif
        @endforeach
        @endisset
    @endif

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

    </form>
</nav>
