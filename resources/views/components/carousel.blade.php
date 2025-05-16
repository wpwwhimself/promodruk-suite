@props([
    "imgs" => null,
])

@if ($imgs)

<div class="carousel">
    <div class="glide">
        <div class="glide__track" data-glide-el="track">
            <ul class="glide__slides">
                @foreach ($imgs as $i => $img)
                <li class="glide__slide"><img src="{{ $img }}" alt="Baner {{ $i }}"></li>
                @endforeach
            </ul>
        </div>

        <div class="glide__bullets" data-glide-el="controls[nav]">
            @foreach ($imgs as $i => $img)
            <button class="glide__bullet" data-glide-dir="={{ $i }}"></button>
            @endforeach
        </div>
    </div>
</div>

<script>
new Glide('.glide', {
    type: "carousel",
    autoplay: 5e3,
    focusAt: "center",
}).mount()
</script>

@endif
