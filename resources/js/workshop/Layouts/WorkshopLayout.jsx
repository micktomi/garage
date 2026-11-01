import React, { useEffect, useRef, useState } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import { Button, Feedback, Icon } from "../Components/ui";
const groups = [
    [
        "Καθημερινά",
        [
            ["/workshop", "Αρχική", "home"],
            ["/workshop/work-orders", "Εντολές εργασίας", "work"],
            ["/workshop/appointments", "Ραντεβού", "calendar"],
            ["/workshop/customers", "Πελάτες", "people"],
            ["/workshop/vehicles", "Οχήματα", "car"],
        ],
    ],
    [
        "Υπενθυμίσεις και αποθήκη",
        [
            ["/workshop/kteo", "ΚΤΕΟ", "check"],
            ["/workshop/parts", "Ανταλλακτικά", "parts"],
        ],
    ],
    [
        "Εργαλεία",
        [
            ["/workshop/registration-scan", "Σάρωση άδειας", "scan"],
            ["/workshop/assistant", "AI Βοηθός", "assistant"],
        ],
    ],
];
const isCurrent = (currentPath, href) =>
    href === "/workshop" ? currentPath === href : currentPath.startsWith(href);
export default function WorkshopLayout({ children }) {
    const { auth } = usePage().props;
    const { url } = usePage();
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState("");
    const [requestError, setRequestError] = useState(false);
    const [offline, setOffline] = useState(!navigator.onLine);
    const [busy, setBusy] = useState(false);
    const menuButton = useRef();
    const closeButton = useRef();
    useEffect(() => {
        setOpen(false);
    }, [url]);
    useEffect(() => {
        if (!open) return;
        closeButton.current?.focus();
        const onKey = (e) => {
            if (e.key === "Escape") {
                setOpen(false);
                menuButton.current?.focus();
            }
        };
        document.addEventListener("keydown", onKey);
        return () => document.removeEventListener("keydown", onKey);
    }, [open]);
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
    const current = (href) =>
        isCurrent(currentPath, href) ? "page" : undefined;
    return (
        <>
            <a className="ws-skip" href="#workspace">
                Μετάβαση στο περιεχόμενο
            </a>
            {open && (
                <button
                    type="button"
                    className="ws-backdrop"
                    aria-label="Κλείσιμο μενού"
                    tabIndex={-1}
                    onClick={() => setOpen(false)}
                />
            )}
            <aside
                className="ws-sidebar"
                id="workshop-navigation"
                data-open={open}
                {...(open
                    ? { role: "dialog", "aria-label": "Μενού" }
                    : {})}
            >
                <div className="ws-sidebar-top">
                    <Link className="ws-brand" href="/workshop">
                        <span className="ws-brand-mark">
                            <Icon name="car" size={20} />
                        </span>
                        <span>
                            <strong>Garage Manager</strong>
                            <small>Χώρος συνεργείου</small>
                        </span>
                    </Link>
                    <button
                        type="button"
                        ref={closeButton}
                        className="ws-btn ws-sheet-close"
                        onClick={() => {
                            setOpen(false);
                            menuButton.current?.focus();
                        }}
                    >
                        <Icon name="close" size={16} />
                        Κλείσιμο
                    </button>
                </div>
                <div className="ws-sidebar-content">
                    {groups.map(([label, items]) => (
                        <React.Fragment key={label}>
                            <div className="ws-nav-label">{label}</div>
                            <nav className="ws-nav" aria-label={label}>
                                {items.map(([href, text, icon]) => (
                                    <Link
                                        key={href}
                                        href={href}
                                        aria-current={current(href)}
                                    >
                                        <Icon name={icon} />
                                        {text}
                                    </Link>
                                ))}
                            </nav>
                        </React.Fragment>
                    ))}
                </div>
                <div className="ws-sidebar-footer">
                    <div className="ws-nav">
                        <a href="/admin">
                            <Icon name="admin" />
                            Διαχείριση
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
                    <Link
                        className="ws-topbar-brand"
                        href="/workshop"
                        aria-label="Garage Manager, αρχική"
                    >
                        <Icon name="car" size={18} />
                    </Link>
                    <form
                        className="ws-topbar-search"
                        role="search"
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
                    <div className="ws-topbar-actions">
                        <Button href="/workshop/registration-scan">
                            <Icon name="scan" size={17} />
                            Σάρωση άδειας
                        </Button>
                        <Button href="/workshop/work-orders/create" primary>
                            <Icon name="plus" size={17} />
                            Νέα εντολή
                        </Button>
                    </div>
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
            <nav className="ws-tabbar" aria-label="Συντομεύσεις">
                <Link
                    className="ws-tab"
                    href="/workshop"
                    aria-current={current("/workshop")}
                >
                    <Icon name="home" size={22} />
                    Αρχική
                </Link>
                <Link
                    className="ws-tab"
                    href="/workshop/work-orders"
                    aria-current={current("/workshop/work-orders")}
                >
                    <Icon name="work" size={22} />
                    Εντολές
                </Link>
                <Link className="ws-tab-new" href="/workshop/work-orders/create">
                    <span>
                        <Icon name="plus" size={24} />
                    </span>
                    Νέα
                </Link>
                <Link
                    className="ws-tab"
                    href="/workshop/appointments"
                    aria-current={current("/workshop/appointments")}
                >
                    <Icon name="calendar" size={22} />
                    Ραντεβού
                </Link>
                <button
                    type="button"
                    ref={menuButton}
                    className="ws-tab"
                    aria-expanded={open}
                    aria-controls="workshop-navigation"
                    onClick={() => setOpen(!open)}
                >
                    <Icon name="more" size={22} />
                    Μενού
                </button>
            </nav>
        </>
    );
}
