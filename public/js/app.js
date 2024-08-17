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
        btn.addEventListener("click", (ev) => {
            if (!confirm("Ostrożnie! Czy na pewno chcesz to zrobić?")) {
                ev.preventDefault();
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
    console.log("💄", offset, lastOffset, offset > lastOffset)

    if (offset > lastOffset) {
        headerWrapper.classList.remove(visibleClass)
    } else {
        headerWrapper.classList.add(visibleClass)
    }

    lastOffset = offset <= 0 ? 0 : offset
})

/**
 * @param {String} HTML representing a single element.
 * @param {Boolean} flag representing whether or not to trim input whitespace, defaults to true.
 * @return {Element | HTMLCollection | null}
 */
function fromHTML(html, trim = true) {
    // Process the HTML string.
    html = trim ? html.trim() : html;
    if (!html) return null;

    // Then set up a new template element.
    const template = document.createElement('template');
    template.innerHTML = html;
    const result = template.content.children;

    // Then return either an HTMLElement or HTMLCollection,
    // based on whether the input HTML had one or more roots.
    if (result.length === 1) return result[0];
    return result;
  }
