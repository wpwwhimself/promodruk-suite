@props([
    "images",
    "thumbnails" => null,
])

<div class="photo-gallery flex-down center middle">

    <div id="main-photo-wrapper" class="flex-right center">
        <img src="{{ asset($images[0]) }}" id="main-photo" onclick="openPhoto(event.target.src)" data-index="0">
        <x-ik-chevron-left class="gallery-btn control" onclick="cyclePhoto(-1)" title="Poprzednie zdjęcie" />
        <x-ik-chevron-right class="gallery-btn control" onclick="cyclePhoto(1)" title="Następne zdjęcie" />
    </div>
    <div class="list flex-right center wrap">
        @foreach ($thumbnails ?? $images as $i => $img)
        <img onclick="switchPhoto(this)" src="{{ asset($img ?? $images[$i]) }}" data-large="{{ $images[$i] }}" data-index="{{ $i }}" />
        @endforeach
    </div>

    <x-button action="none" label="Zamknij" icon="close" onclick="closePhoto()" id="close-fullscreen-btn" style="display: none" />

    <div style="display: none">
        @foreach ($images as $i => $img)
        <img src="{{ asset($img) }}" />
        @endforeach
    </div>

</div>

<script>
const wrapper = document.querySelector(".photo-gallery")
const mainPhoto = document.getElementById("main-photo")
const controls = document.querySelectorAll(".control")
const closeFullscreenBtn = document.querySelector("#close-fullscreen-btn")
const images_count = document.querySelectorAll(".list img").length

openPhoto = (url) => {
    if (wrapper.classList.contains("fullscreen")) {
        window.open(url, "_blank")
        return
    }

    wrapper.classList.add("fullscreen")
    closeFullscreenBtn.style.display = "flex"
}
closePhoto = () => {
    wrapper.classList.remove("fullscreen")
    closeFullscreenBtn.style.display = "none"
}
switchPhoto = (img) => {
    mainPhoto.src = img.dataset.large
    mainPhoto.dataset.index = img.dataset.index
    updateControlsVisibility()
}
cyclePhoto = (direction) => {
    const new_index = parseInt(mainPhoto.dataset.index) + direction
    switchPhoto(document.querySelector(`.list img[data-index="${new_index}"]`))
}
updateControlsVisibility = () => {
    const current_index = mainPhoto.dataset.index
    Array.from(controls).forEach(c => c.style.display = "block")
    if (current_index == 0) controls[0].style.display = "none"
    if (current_index == images_count - 1) controls[1].style.display = "none"

    document.querySelectorAll(`.list img`).forEach(img => {
        if (img.dataset.index == current_index) img.classList.add("active")
        else img.classList.remove("active")
    })
}

updateControlsVisibility()
</script>
