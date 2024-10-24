let tabs = JSON.parse(document.querySelector(`input[name=tabs]`).value || "[]")

const buildTabs = async () => {
    document.querySelector(`input[name=tabs]`).value = JSON.stringify(tabs)

    const form = document.querySelector("form")
    toggleLoader()
    await fetch(`/api/products/prepare-tabs`, {
        method: "POST",
        headers: {
            "Accept": "application/json",
            "X-CSRF-TOKEN": form.querySelector("input[name=_token]").value,
        },
        body: new FormData(form),
    })
        .then(res => res.text())
        .then(editor => {
            document.querySelector("#tabs").replaceWith(fromHTML(editor))
        })
        .finally(() => toggleLoader())
}

const newTab = () => {
    tabs = [...tabs ?? [], {
        name: "",
        cells: [],
    }]
    buildTabs()
}
const deleteTab = (index) => {
    tabs = tabs.filter((tab, i) => i != index)
    buildTabs()
}
const changeTabName = (index, new_name) => {
    tabs[index].name = new_name
    buildTabs()
}

const newCell = (tab_index) => {
    tabs[tab_index].cells = [...tabs[tab_index].cells ?? [], {
        type: "text",
        content: "",
    }]
    buildTabs()
}
const changeCellHeading = (tab_index, cell_index, new_value) => {
    tabs[tab_index].cells[cell_index]["heading"] = new_value
    buildTabs()
}
const changeCellType = (tab_index, cell_index, new_type) => {
    tabs[tab_index].cells[cell_index] = {
        ...tabs[tab_index].cells[cell_index],
        type: new_type,
        content: (new_type == "text") ? "" : [],
    }
    buildTabs()
}
const deleteCell = (tab_index, cell_index) => {
    tabs[tab_index].cells = tabs[tab_index].cells.filter((cell, i) => i != cell_index)
    buildTabs()
}

const updateCellContent = (tab_index, cell_index, new_content) => {
    tabs[tab_index].cells[cell_index].content = new_content
    buildTabs()
}

const addTableRow = (tab_index, cell_index) => {
    tabs[tab_index].cells[cell_index].content = {
        ...tabs[tab_index].cells[cell_index].content ?? [],
        nowy: "",
    }
    buildTabs()
}
const updateTableRows = (tab_index, cell_index) => {
    const labels = Array.from(document.querySelectorAll(`input[name^="tabs_raw[${tab_index}][cells][${cell_index}][content][labels]"]`)).map(field => field.value)
    const values = Array.from(document.querySelectorAll(`input[name^="tabs_raw[${tab_index}][cells][${cell_index}][content][values]"]`)).map(field => field.value)

    tabs[tab_index].cells[cell_index].content = {}
    labels.forEach((label, i) => {
        tabs[tab_index].cells[cell_index].content[label] = values[i]
    })
    buildTabs()
}
const deleteTableRow = (btn, tab_index, cell_index) => {
    btn.closest("tr").remove()
    updateTableRows(tab_index, cell_index)
}
