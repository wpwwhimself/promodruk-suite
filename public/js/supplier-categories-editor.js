const build = async () => {
    const form = document.querySelector("form")
    toggleLoader()
    await fetch(`/api/suppliers/prepare-categories`, {
        method: "POST",
        headers: {
            "Accept": "application/json",
            "X-CSRF-TOKEN": form.querySelector("input[name=_token]").value,
        },
        body: new FormData(form),
    })
        .then(res => res.text())
        .then(editor => {
            document.querySelector("#categories-editor").replaceWith(fromHTML(editor))
        })
        .finally(() => toggleLoader())
}

const addCategory = (btn) => {
    const newCatInput = document.createElement("input")
    newCatInput.type = "hidden"
    newCatInput.name = "categories[]"
    newCatInput.value = btn.closest("div").querySelector("[name='_category']").value
    btn.closest("div").append(newCatInput)

    build()
}
const deleteCategory = (btn) => {
    btn.remove()

    build()
}
