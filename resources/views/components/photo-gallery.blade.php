@props([
    "images",
])

<div class="photo-gallery">

<img src="{{ $images[0] }}" id="main-photo" onclick="openPhoto(event.target.src)">
<div class="list flex-right wrap">
    @foreach ($images as $img)
    <img onclick="switchPhoto(this)" src="{{ $img }}" />
    @endforeach
</div>

</div>

<script>
openPhoto = (url) => {
    window.open(url, "_blank")
}
switchPhoto = (img) => {
    document.getElementById("main-photo").src = img.src
}
</script>
