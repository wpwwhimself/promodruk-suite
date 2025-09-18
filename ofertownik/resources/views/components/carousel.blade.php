@props([
    "imgs" => null,
])

@php $time = time(); @endphp

@if ($imgs)

<div class="carousel">
    @if (count($imgs) > 1)
    <div class="glide" data-id="{{ $time }}">
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
    @elseif (count($imgs) == 1)
    <img src="{{ collect($imgs)->first() }}" alt="Baner">
    @endif
</div>

<script>
if (document.querySelector(".glide[data-id='{{ $time }}']")) {
    new Glide(".glide[data-id='{{ $time }}']", {
        type: "carousel",
        autoplay: 5e3,
        focusAt: "center",
    }).mount();
}
</script>

@endif
