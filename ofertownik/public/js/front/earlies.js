/**
 * Convert bytes into human readable format
 * @param {number} size size in bytes
 * @return {string} human readable size
 */
function getFileSize(size) {
    var i = size == 0 ? 0 : Math.floor(Math.log(size) / Math.log(1024));
    return +((size / Math.pow(1024, i)).toFixed(2)) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
}

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
