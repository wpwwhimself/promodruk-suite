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
