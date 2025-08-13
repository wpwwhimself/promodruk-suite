const loadCategories = (supplier_id) => {
    toggleLoader()
    fetch(`/api/suppliers/${supplier_id}`)
        .then(res => res.json())
        .then(data => {
            document.querySelector("#categories-selector").replaceWith(fromHTML(data.categoriesSelector))
        })
        .finally(() => toggleLoader())
}
