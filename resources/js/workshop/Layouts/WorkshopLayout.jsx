import React, { useEffect, useState } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import { Button, Feedback, Icon } from "../Components/ui";
const navigation = [
    ["/workshop", "Dashboard", "grid"],
    ["/workshop/work-orders", "Εντολές εργασίας", "work"],
    ["/workshop/appointments", "Ραντεβού", "calendar"],
    ["/workshop/customers", "Πελάτες", "people"],
    ["/workshop/vehicles", "Οχήματα", "car"],
    ["/workshop/kteo", "ΚΤΕΟ", "check"],
    ["/workshop/parts", "Ανταλλακτικά", "parts"],
    ["/workshop/assistant", "AI Βοηθός", "assistant"],
];
export default function WorkshopLayout({ children }) {
    const { auth } = usePage().props;
    const { url } = usePage();
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState("");
    const [requestError, setRequestError] = useState(false);
    const [offline, setOffline] = useState(!navigator.onLine);
    const [busy, setBusy] = useState(false);
    useEffect(() => {
        setOpen(false);
    }, [url]);
    useEffect(() => {
        const online = () => setOffline(false),
            off = () => setOffline(true);
        window.addEventListener("online", online);
        window.addEventListener("offline", off);
        const a = router.on("start", () => {
                setBusy(true);
                setRequestError(false);
            }),
            b = router.on("finish", () => setBusy(false));
        const c = router.on("exception", (event) => {
            event.preventDefault();
            setRequestError(true);
            setBusy(false);
        });
        return () => {
            window.removeEventListener("online", online);
            window.removeEventListener("offline", off);
            a();
            b();
            c();
        };
    }, []);
    const currentPath = url.split("?")[0];
    return (
        <>
            <a className="ws-skip" href="#workspace">
                Μετάβαση στο περιεχόμενο
            </a>
            <aside className="ws-sidebar">
                <div className="ws-sidebar-top">
                    <Link className="ws-brand" href="/workshop">
                        <span className="ws-brand-mark">
                            <Icon name="car" size={22} />
                        </span>
                        <span>
                            <strong>Garage Manager</strong>
                            <small>Χώρος συνεργείου</small>
                        </span>
                    </Link>
                    <Button
                        className="ws-mobile-toggle"
                        aria-expanded={open}
                        aria-controls="workshop-navigation"
                        onClick={() => setOpen(!open)}
                    >
                        Μενού
                    </Button>
                </div>
                <div
                    className="ws-sidebar-content"
                    id="workshop-navigation"
                    data-open={open}
                >
                    <div className="ws-nav-label">Καθημερινή λειτουργία</div>
                    <nav className="ws-nav" aria-label="Κύρια πλοήγηση">
                        {navigation.map(([href, label, icon]) => (
                            <Link
                                key={href}
                                href={href}
                                aria-current={
                                    (
                                        href === "/workshop"
                                            ? currentPath === href
                                            : currentPath.startsWith(href)
                                    )
                                        ? "page"
                                        : undefined
                                }
                            >
                                <Icon name={icon} />
                                {label}
                            </Link>
                        ))}
                    </nav>
                    <nav
                        className="ws-nav ws-scan-link"
                        aria-label="Καταχώριση άδειας"
                    >
                        <Link
                            href="/workshop/registration-scan"
                            aria-current={
                                currentPath.startsWith(
                                    "/workshop/registration-scan",
                                )
                                    ? "page"
                                    : undefined
                            }
                        >
                            <Icon name="scan" />
                            Σάρωση άδειας
                        </Link>
                    </nav>
                </div>
                <div className="ws-sidebar-footer">
                    <div className="ws-nav">
                        <a href="/admin">
                            <Icon name="admin" />
                            Διαχείριση Filament
                        </a>
                    </div>
                    <div className="ws-account">
                        <span className="ws-avatar">
                            {auth.user?.name?.slice(0, 1) || "Σ"}
                        </span>
                        <div>
                            {auth.user?.name}
                            <small>
                                {auth.user?.role === "owner"
                                    ? "Ιδιοκτήτης"
                                    : "Προσωπικό"}
                            </small>
                        </div>
                    </div>
                </div>
            </aside>
            <div className="ws-main">
                <header className="ws-topbar">
                    <form
                        className="ws-topbar-search"
                        noValidate
                        onSubmit={(e) => {
                            e.preventDefault();
                            router.get("/workshop/search", { q: query });
                        }}
                    >
                        <input
                            aria-label="Αναζήτηση πινακίδας, πελάτη ή τηλεφώνου"
                            placeholder="Πινακίδα, πελάτης ή τηλέφωνο…"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                        />
                        {query && (
                            <button
                                type="button"
                                aria-label="Καθαρισμός αναζήτησης"
                                onClick={() => setQuery("")}
                            >
                                ×
                            </button>
                        )}
                    </form>
                    <span className="ws-topbar-date">
                        {new Intl.DateTimeFormat("el-GR", {
                            weekday: "long",
                            day: "numeric",
                            month: "long",
                            timeZone: "Europe/Athens",
                        }).format(new Date())}
                    </span>
                    <Button href="/workshop/work-orders/create" primary>
                        <Icon name="plus" />
                        <span className="ws-btn-label">Νέα εντολή</span>
                    </Button>
                </header>
                <main id="workspace" className="ws-content" aria-busy={busy}>
                    {offline && (
                        <div
                            className="ws-feedback ws-feedback-error"
                            role="alert"
                        >
                            Δεν υπάρχει σύνδεση δικτύου. Οι αλλαγές δεν
                            αποστέλλονται· διατηρήστε τη φόρμα ανοιχτή και
                            δοκιμάστε ξανά όταν επανέλθει η σύνδεση.
                        </div>
                    )}
                    {requestError && (
                        <div
                            className="ws-feedback ws-feedback-error"
                            role="alert"
                        >
                            Η σύνδεση με τον server διακόπηκε. Διατηρήστε τη
                            φόρμα ανοιχτή και δοκιμάστε ξανά.
                        </div>
                    )}
                    <Feedback flashOnly />
                    {children}
                </main>
            </div>
        </>
    );
}
