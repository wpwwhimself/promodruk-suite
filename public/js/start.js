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

/**
 * Convert bytes into human readable format
 * @param {number} size size in bytes
 * @return {string} human readable size
 */
function getFileSize(size) {
    var i = size == 0 ? 0 : Math.floor(Math.log(size) / Math.log(1024));
    return +((size / Math.pow(1024, i)).toFixed(2)) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
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

function initFileReplace(file_name) {
    // just trigger file upload and mention to keep the same name
    const fileInput = document.querySelector("input#files")
    const fileNameInput = document.querySelector("input[name='force_file_name']")

    fileInput.addEventListener("change", () => {
        if (!fileInput.files.length) {
            fileNameInput.value = undefined
            return
        }

        fileNameInput.value = file_name
        fileInput.form.submit()
    })

    fileInput.click()
}
// #endregion

/**
 * modal control
 */
function toggleModal(modal_id) {
    const modal = document.getElementById(modal_id);
    modal.classList.toggle('hidden');
}

/**
 * multi filters
 */
function updateFilterInput(name, value, nested = undefined) {
    const filterInput = document.querySelector(nested
        ? `input[name="filters[${nested}][${name}]"]`
        : `input[name="filters[${name}]"]`
    );
    const values = filterInput.value.split("|").filter(Boolean);

    if (values.includes(value)) values.splice(values.indexOf(value), 1);
    else values.push(value);

    filterInput.value = values.join("|");
    filterInput.dispatchEvent(new Event('change'));
}

// #region JSON inputs
function JSONInputUpdate(input_name) {
    const input = document.querySelector(`input[name="${input_name}"]`)
    const table = document.querySelector(`table[data-name="${input_name}"]`)
    const cols = parseInt(table.getAttribute("data-columns"))
    let newValue = cols == 2 ? {} : []

    table.querySelectorAll("tbody tr").forEach((row, row_no) => {
        switch (cols) {
            case 2:
                const inputs = row.querySelectorAll("input")
                newValue[inputs[0].value] = inputs[1].value
                break

            case 1:
                newValue[row_no] = row.querySelector("input").value
                break

            default:
                newValue[row_no] = Array.from(row.querySelectorAll("input")).map(input => input.value || null)
        }
    })

    input.value = JSON.stringify(newValue)
}

function JSONInputAddRow(input_name) {
    const table = document.querySelector(`table[data-name="${input_name}"]`)
    const newRow = table.querySelector(`tr[role="new-row"]`)

    if (Array.from(newRow.querySelectorAll("input")).map(input => !input.value).some(Boolean)) return

    newRow.querySelector("input").value.split(/,\s*/).forEach((v, i) => {
        let clonedRow = newRow.cloneNode(true)
        clonedRow.removeAttribute("role")
        clonedRow.querySelector("td:last-child .button:first-child").remove()
        clonedRow.querySelector("td:last-child .button:last-child").classList.remove("hidden")
        clonedRow.querySelector("input").value = v
        table.querySelector("tbody").appendChild(clonedRow)
    })

    newRow.querySelectorAll("input").forEach(input => input.value = "")
    JSONInputUpdate(input_name)
}

function JSONInputDeleteRow(input_name, btn) {
    btn.closest("tr").remove()
    JSONInputUpdate(input_name)
}

function JSONInputAutofill(input_name, ev, filled_value = null) {
    const input = ev.target.closest("td").querySelector(`input`)
    const hintBox = ev.target.closest("td").querySelector(`[role="autofill-hint"]`)
    let hints = ""

    if (filled_value) {
        input.value = filled_value
        JSONInputUpdate(input_name)
    }
    if (!hintBox) return

    if (ev.target.value && !filled_value) {
        hints = window.autofill[input_name]
            .filter(v => v.toLowerCase().startsWith(ev.target.value.toLowerCase()))
            .map(v => `<span class="interactive"
                    onclick="JSONInputAutofill('${input_name}', event, '${v}')"
                >
                    ${v}?
                </span>`)
            .join("")
    }

    hintBox.innerHTML = hints
}

function JSONInputWatchForConfirm(input_name, ev) {
    if (ev.key == "Enter" || ev.key == ",") {
        ev.stopPropagation()
        ev.preventDefault()
        JSONInputAutofill(input_name, ev, ev.target.value)
        JSONInputAddRow(input_name, ev.target.closest("tr").querySelector(".button"))
    }
}
// #endregion
