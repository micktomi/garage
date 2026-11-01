// Date-only model casts serialize as UTC; preserve the garage calendar day.
export function inputDate(value) {
    if (!value) return "";
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
    return new Date(value).toLocaleDateString("sv-SE", {
        timeZone: "Europe/Athens",
    });
}

// Whole garage-calendar days from `from` to `to` (default now); negative when `from` is in the future.
export function daysBetween(from, to = new Date()) {
    if (!from || !to) return null;
    const day = (value) => {
        const [y, m, d] = inputDate(
            value instanceof Date ? value.toISOString() : value,
        )
            .split("-")
            .map(Number);
        return Date.UTC(y, m - 1, d);
    };
    return Math.round((day(to) - day(from)) / 86400000);
}

// KTEO expiry as a badge tone + label; `null` when the date is far enough away to stay quiet.
export function kteoStatus(vehicle, { quiet = false, now = new Date() } = {}) {
    const days = daysBetween(now, vehicle.kteo_expires_at);
    if (days === null) return null;
    if (days < 0)
        return { tone: "danger", label: `Έληξε πριν ${Math.abs(days)} ημ.` };
    if (days === 0) return { tone: "danger", label: "Λήγει σήμερα" };
    if (quiet && days > 30) return null;
    return {
        tone: days <= 7 ? "warning" : "calm",
        label: `Σε ${days} ${days === 1 ? "ημέρα" : "ημέρες"}`,
    };
}

// 24-hour garage clock time, e.g. "09:30".
export function timeOfDay(value) {
    if (!value) return "—";
    return new Intl.DateTimeFormat("el-GR", {
        timeZone: "Europe/Athens",
        hour: "2-digit",
        minute: "2-digit",
        hourCycle: "h23",
    }).format(new Date(value));
}
