/**
 * Ukrywanie alertów
 */
const TOAST_TIMEOUT = 4000;

const alert = document.querySelector(".alert")
if(alert) {
    //appear
    setTimeout(() => {
        alert.classList.add("in");
    }, 1);

    //allow dismissal
    alert.addEventListener("click", (ev) => ev.target.classList.remove("in"));

    //disappear
    setTimeout(() => {
        alert.classList.remove("in");
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
            if (confirm("Ostrożnie! Czy na pewno chcesz to zrobić?")) {
                currentEvents()
            }
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
