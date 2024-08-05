@props([
    "images",
    "thumbnails" => null,
])

<div class="photo-gallery">

<img src="{{ $images[0] }}" id="main-photo" onclick="openPhoto(event.target.src)">
<div class="list flex-right wrap">
    @foreach ($thumbnails ?? $images as $i => $img)
    <img onclick="switchPhoto(this)" src="{{ $img }}" data-large="{{ $images[$i] }}" />
    @endforeach
</div>

</div>

<script>
openPhoto = (url) => {
    window.open(url, "_blank")
}
switchPhoto = (img) => {
    document.getElementById("main-photo").src = img.dataset.large
}
</script>
