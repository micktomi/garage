// Date-only model casts serialize as UTC; preserve the garage calendar day.
export function inputDate(value) {
    if (!value) return "";
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
    return new Date(value).toLocaleDateString("sv-SE", {
        timeZone: "Europe/Athens",
    });
}
