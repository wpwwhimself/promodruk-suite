import './bootstrap';

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
