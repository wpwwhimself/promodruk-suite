const loadCategories = (supplier_name) => {
    toggleLoader()
    fetch(`/api/suppliers/by-name/${supplier_name}`)
        .then(res => res.json())
        .then(data => {
            document.querySelector(`#id`).value = data.supplier.prefix
            document.querySelector("#categories-selector").replaceWith(fromHTML(data.categoriesSelector))
        })
        .finally(() => toggleLoader())
}
