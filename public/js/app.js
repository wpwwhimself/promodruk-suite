/**
 * Ukrywanie alertów
 */
const TOAST_TIMEOUT = 4000;

const toast = document.querySelector(".alert")
if(toast) {
    //appear
    setTimeout(() => {
        toast.classList.add("in");
    }, 1);

    //allow dismissal
    toast.addEventListener("click", (ev) => ev.target.classList.remove("in"));

    //disappear
    setTimeout(() => {
        toast.classList.remove("in");
    }, TOAST_TIMEOUT);
}

/**
 * Niebezpieczne przyciski
 */
document.querySelectorAll("button.danger, .button-like.danger")
    .forEach(btn => {
        const currentEvents = btn.onclick
        btn.onclick = undefined;
        btn.addEventListener("click", (ev) => {
            if (!confirm("Ostrożnie! Czy na pewno chcesz to zrobić?")) {
                ev.preventDefault();
            }
            currentEvents()
        })
    })

/**
 * Sticky nagłówek
 */
let lastOffset = 0;
window.addEventListener("scroll", (ev) => {
    const headerWrapper = document.querySelector("#header-wrapper")
    const visibleClass = "visible"

    let offset = window.scrollY

    if (offset > lastOffset) {
        headerWrapper.classList.remove(visibleClass)
    } else {
        headerWrapper.classList.add(visibleClass)
    }

    lastOffset = offset <= 0 ? 0 : offset
})

/**
 * Wyłączanie popupów poprzez kliknięcie tła
 */
const popups = document.querySelectorAll(".fullscreen-popup")
window.addEventListener("click", ({target}) => {
    const popup = target.closest(".fullscreen-popup")

    if (
        popup
        && !popup.classList.contains("hidden")
        && target.classList.contains("fullscreen-popup")
    ) {
        popup.classList.add("hidden")
    }
});
