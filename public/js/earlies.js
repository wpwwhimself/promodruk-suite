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

const objectMap = (obj, fn) => {
    if (!obj) return [];
    return Object.entries(obj).map(
        ([k, v]) => fn(v, k)
    )
}

/**
 * submit a form
 */
const submitForm = () => {
    document.querySelector("form button[type=submit][value=save]").click()
}

/**
 * form hints
 */
function hints(input_id) {
    const input = document.getElementById(input_id)
    if (!input.value) {
        hintUse(input_id, "")
        return
    }

    const hints = window.hints[input_id]
        .filter(hint => hint.toLowerCase().includes(input.value.toLowerCase()))
        .map(hint => `<span class="button" onclick="hintUse('${input_id}', '${hint}')">${hint}</span>`)

    document.querySelector(`[for=${input_id}] .hints`).innerHTML = hints.join("")
}

function hintUse(input_id, hint) {
    document.getElementById(input_id).value = hint
    document.querySelector(`[for=${input_id}] .hints`).innerHTML = ""
}

// #region file storage functions
function copyToClipboard(text) {
    navigator.clipboard.writeText(text)
    alert("Skopiowano do schowka.")
}

function browseFiles(url) {
    window.open(url, "_blank")
}

function selectFile(url, input_id) {
    if (window.opener) {
        window.opener.document.getElementById(input_id).value = url
        window.close()
    }
}
// #endregion
