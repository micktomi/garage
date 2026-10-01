const normalize = (value) => (value || "").trim().toLocaleLowerCase("el-GR");
export function compatibleModels(catalogue, make) {
    const key = Object.keys(catalogue).find(
        (value) => normalize(value) === normalize(make),
    );
    return key ? catalogue[key] : [];
}
export function modelAfterMakeChange(
    catalogue,
    previousMake,
    previousModel,
    make,
) {
    return normalize(make) === normalize(previousMake) ||
        compatibleModels(catalogue, make).some(
            (model) => normalize(model) === normalize(previousModel),
        )
        ? previousModel
        : "";
}
