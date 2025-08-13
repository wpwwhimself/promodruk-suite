/**
 * Ukrywanie alertów
 */
const TOAST_TIMEOUT = 4000;

const toast = document.querySelector(".toast")
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
document.querySelectorAll("button.danger, .button.danger, .button-like.danger")
    .forEach(btn => {
        btn.addEventListener("click", (ev) => {
            if (!confirm("Ostrożnie! Czy na pewno chcesz to zrobić?")) {
                ev.preventDefault();
            }
        })
    })

