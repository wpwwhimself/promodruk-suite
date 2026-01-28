const objectMap = (obj, fn) => {
    if (!obj) return [];
    return Object.entries(obj).map(
        ([k, v]) => fn(v, k)
    )
}
