import React, { useEffect, useId, useRef, useState } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { daysBetween, kteoStatus } from "./dates";
export { kteoStatus };

export const money = (value) =>
    new Intl.NumberFormat("el-GR", {
        style: "currency",
        currency: "EUR",
    }).format(Number(value || 0));
export const date = (value) =>
    value
        ? new Intl.DateTimeFormat("el-GR", {
              timeZone: "Europe/Athens",
              day: "2-digit",
              month: "2-digit",
              year: "numeric",
          }).format(new Date(value))
        : "—";
export const rows = (collection) =>
    Array.isArray(collection) ? collection : collection?.data || [];
export function Icon({ name = "grid", size = 18 }) {
    const paths = {
        home: "M3 11l9-8 9 8 M5 10v10h14V10 M10 20v-6h4v6",
        grid: "M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z",
        work: "M9 4h6v3H9z M7 5H5v16h14V5h-2 M9 12h6 M9 16h4",
        calendar: "M4 6h16v14H4z M4 10h16 M8 3v5 M16 3v5",
        people: "M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z M2.5 20c0-3.3 2.9-6 6.5-6s6.5 2.7 6.5 6 M16 4.5a3.2 3.2 0 0 1 0 6 M18 14.3c2 .7 3.5 2.6 3.5 5.7",
        car: "M4 17v-5l2-5h12l2 5v5z M4 17v2 M20 17v2 M7 14h2 M15 14h2 M4 12h16",
        check: "M12 3l8 3v6c0 4.5-3.4 8-8 9-4.6-1-8-4.5-8-9V6z M8.5 12l2.5 2.5 4.5-5",
        parts: "M3 7.5l9-4.5 9 4.5v9l-9 4.5-9-4.5z M3 7.5l9 4.5 9-4.5 M12 12v9",
        assistant:
            "M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z M18 16l.8 2.2L21 19l-2.2.8L18 22l-.8-2.2L15 19l2.2-.8z",
        scan: "M4 8V5a1 1 0 0 1 1-1h3 M16 4h3a1 1 0 0 1 1 1v3 M20 16v3a1 1 0 0 1-1 1h-3 M8 20H5a1 1 0 0 1-1-1v-3 M8 9h8v6H8z",
        arrow: "M5 12h14 M13 6l6 6-6 6",
        admin: "M4 7h10 M18 7h2 M4 17h2 M10 17h10 M16 5v4 M8 15v4",
        plus: "M12 5v14 M5 12h14",
        search: "M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14z M20 20l-4-4",
        phone: "M6 3h3l2 5-2 1.5a11 11 0 0 0 5.5 5.5L16 13l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2z",
        more: "M5 12h.01 M12 12h.01 M19 12h.01",
        open: "M7 17L17 7 M8 7h9v9",
        close: "M6 6l12 12 M18 6L6 18",
        sms: "M4 5h16v11H9l-5 4z M8 9h8 M8 12h5",
        copy: "M9 9h11v11H9z M5 15H4V4h11v1",
    };
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            <path d={paths[name] || paths.grid} />
        </svg>
    );
}
export function Button({
    href,
    primary,
    danger,
    busy,
    disabled,
    children,
    className = "",
    ...props
}) {
    const classes = `ws-btn ${primary ? "ws-btn-primary" : ""} ${danger ? "ws-btn-danger" : ""} ${className}`;
    if (href)
        return (
            <Link href={href} className={classes} {...props}>
                {children}
            </Link>
        );
    return (
        <button
            type="button"
            className={classes}
            disabled={busy || disabled}
            aria-busy={busy || undefined}
            {...props}
        >
            {children}
        </button>
    );
}
export function PageHeading({ title, description, children }) {
    return (
        <>
            <Head title={title} />
            <div className="ws-page-heading">
                <div>
                    <h1>{title}</h1>
                    {description && <p>{description}</p>}
                </div>
                <div className="ws-actions">{children}</div>
            </div>
        </>
    );
}
export function Panel({
    title,
    action,
    children,
    className = "",
    body = false,
}) {
    return (
        <section className={`ws-panel ${className}`}>
            {title && (
                <div className="ws-panel-heading">
                    <h2>{title}</h2>
                    {action}
                </div>
            )}
            {body ? <div className="ws-panel-body">{children}</div> : children}
        </section>
    );
}
export function Field({
    label,
    name,
    inputName,
    form,
    as = "input",
    children,
    hint,
    wide,
    ...props
}) {
    const generated = useId();
    const id = props.id || `${name || "field"}-${generated}`;
    const pageErrors = usePage().props.errors || {};
    const error = form?.errors?.[name] || pageErrors[inputName || name];
    const Tag = as;
    const value = form ? (form.data[name] ?? "") : props.value;
    return (
        <div className={`ws-field ${wide ? "ws-field-wide" : ""}`}>
            <label htmlFor={id}>{label}</label>
            <Tag
                id={id}
                name={inputName || name}
                className="ws-input"
                value={value}
                onChange={
                    form
                        ? (e) => form.setData(name, e.target.value)
                        : props.onChange
                }
                aria-invalid={!!error}
                aria-describedby={
                    error ? `${id}-error` : hint ? `${id}-hint` : undefined
                }
                {...props}
            >
                {children}
            </Tag>
            {error && (
                <span className="ws-field-error" id={`${id}-error`}>
                    {error}
                </span>
            )}
            {hint && (
                <span className="ws-field-hint" id={`${id}-hint`}>
                    {hint}
                </span>
            )}
        </div>
    );
}
export function Feedback({ errors, flashOnly = false }) {
    const { flash, errors: pageErrors } = usePage().props;
    const current = flashOnly ? {} : { ...pageErrors, ...errors };
    return (
        <>
            {!errors && flash?.success && (
                <div className="ws-feedback" role="status">
                    {flash.success}
                </div>
            )}
            {Object.keys(current).length > 0 && (
                <div className="ws-feedback ws-feedback-error" role="alert">
                    <strong>Η ενέργεια δεν ολοκληρώθηκε.</strong>
                    <ul>
                        {[...new Set(Object.values(current).flat())].map(
                            (error, i) => (
                                <li key={i}>{error}</li>
                            ),
                        )}
                    </ul>
                    {current.lock_version && (
                        <Button onClick={() => window.location.reload()}>
                            Ανανέωση δεδομένων
                        </Button>
                    )}
                </div>
            )}
        </>
    );
}
export function EmptyState({
    title = "Δεν υπάρχουν εγγραφές",
    description = "Αλλάξτε την αναζήτηση ή προσθέστε μια νέα εγγραφή.",
    action,
}) {
    return (
        <div className="ws-empty">
            <h3>{title}</h3>
            <p>{description}</p>
            {action}
        </div>
    );
}
// Work-order status values carry finer colour than the server's three tones:
// waiting for parts is its own signal and a completed order goes quiet.
export function statusTone(status, tone, appointment = false) {
    if (appointment) return tone || "neutral";
    if (status === "awaiting_parts") return "parts";
    if (status === "completed") return "calm";
    return tone || "neutral";
}
/* The one badge: a status, a deadline or a stock warning all use it. */
export function Badge({ tone = "neutral", mono = false, children }) {
    return (
        <span className={`ws-badge ws-badge-${tone} ${mono ? "ws-badge-mono" : ""}`}>
            {children}
        </span>
    );
}
export function StatusBadge({ status, appointment = false }) {
    const props = usePage().props;
    const choice = (
        appointment ? props.appointmentStatuses : props.workOrderStatuses
    )?.find((item) => item.value === status);
    return (
        <Badge tone={statusTone(status, choice?.tone, appointment)}>
            {choice?.label || status || "—"}
        </Badge>
    );
}
export const padId = (id) => `#${String(id).padStart(4, "0")}`;
/* Label/value facts for detail pages and asides. */
export function DetailList({ items }) {
    return (
        <dl className="ws-detail-list">
            {items.map(([label, value]) => (
                <div key={label}>
                    <dt>{label}</dt>
                    <dd>{value ?? "—"}</dd>
                </div>
            ))}
        </dl>
    );
}
export function Plate({ vehicle, link = true }) {
    if (!vehicle) return "—";
    const badge = (
        <span className="ws-plate ws-plate-gr">
            <i aria-hidden="true">GR</i>
            <b>{vehicle.plate_number}</b>
        </span>
    );
    return link ? (
        <Link
            href={`/workshop/vehicles/${vehicle.id}`}
            aria-label={`Όχημα ${vehicle.plate_number}`}
        >
            {badge}
        </Link>
    ) : (
        badge
    );
}
/* "8 εντολές": the count that replaces the pager when everything fits on one page. */
export function ListCount({ data, unit }) {
    if (!unit) return null;
    const total = Array.isArray(data) ? data.length : (data?.total ?? 0);
    if (!total) return null;
    const noun = Array.isArray(unit) ? unit[total === 1 ? 0 : 1] : unit;
    return (
        <p className="ws-list-count">
            {total} {noun}
        </p>
    );
}
export function DataTable({ columns, data, empty, caption }) {
    const items = rows(data);
    return (
        <>
            {items.length ? (
                <div className="ws-table-wrap">
                    <table className="ws-table">
                        {caption && (
                            <caption className="ws-sr-only">{caption}</caption>
                        )}
                        <thead>
                            <tr>
                                {columns.map((col) => (
                                    <th
                                        scope="col"
                                        key={col.label}
                                        className={col.numeric ? "numeric" : ""}
                                    >
                                        {col.label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, i) => (
                                <tr key={item.id || i}>
                                    {columns.map((col) => (
                                        <td
                                            key={col.label}
                                            data-label={col.label}
                                            className={
                                                col.numeric ? "numeric" : ""
                                            }
                                        >
                                            {col.render(item)}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <EmptyState {...empty} />
            )}
            <Pagination data={data} />
        </>
    );
}
export function Pagination({ data }) {
    if (!data || Array.isArray(data) || !(data.last_page > 1)) return null;
    return (
        <div className="ws-pager">
            <span>
                {data.total
                    ? `${data.from}–${data.to} από ${data.total}`
                    : "0 εγγραφές"}
            </span>
            <div className="ws-actions">
                {data.prev_page_url ? (
                    <Button href={data.prev_page_url} preserveScroll>
                        Προηγούμενη
                    </Button>
                ) : (
                    <Button disabled>Προηγούμενη</Button>
                )}
                <span>
                    Σελίδα {data.current_page} / {data.last_page}
                </span>
                {data.next_page_url ? (
                    <Button href={data.next_page_url} preserveScroll>
                        Επόμενη
                    </Button>
                ) : (
                    <Button disabled>Επόμενη</Button>
                )}
            </div>
        </div>
    );
}
/* The one filter row: optional search, then page-specific controls as children. */
export function FilterBar({ url, q = "", children, params = {}, search = true }) {
    const [query, setQuery] = useState(q);
    const ref = useRef();
    useEffect(() => setQuery(q), [q]);
    const run = (value) =>
        router.get(
            url,
            { ...params, q: value },
            { preserveState: true, preserveScroll: true },
        );
    return (
        <form
            className="ws-filter"
            noValidate
            onSubmit={(e) => {
                e.preventDefault();
                if (search) run(query);
            }}
        >
            {search && (
                <div className="ws-search">
                    <input
                        ref={ref}
                        className="ws-input"
                        aria-label="Αναζήτηση"
                        placeholder="Αναζήτηση…"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                    />
                    {query && (
                        <button
                            type="button"
                            aria-label="Καθαρισμός αναζήτησης"
                            onClick={() => {
                                setQuery("");
                                run("");
                                ref.current.focus();
                            }}
                        >
                            ×
                        </button>
                    )}
                </div>
            )}
            {children}
            {search && <Button type="submit">Αναζήτηση</Button>}
        </form>
    );
}
export function ConfirmDialog({
    open,
    title,
    children,
    onCancel,
    onConfirm,
    confirmLabel = "Συνέχεια",
    danger = false,
    busy = false,
}) {
    const ref = useRef();
    const cancel = useRef();
    const previous = useRef();
    const id = useId();
    useEffect(() => {
        if (open && !ref.current.open) {
            previous.current = document.activeElement;
            ref.current.showModal();
            cancel.current?.focus();
        }
        if (!open && ref.current.open) {
            ref.current.close();
            previous.current?.focus();
        }
    }, [open]);
    return (
        <dialog
            ref={ref}
            className="ws-dialog"
            aria-labelledby={id}
            onCancel={(e) => {
                e.preventDefault();
                if (!busy) onCancel();
            }}
        >
            <h2 id={id}>{title}</h2>
            <div>{children}</div>
            <div className="ws-actions">
                <button
                    ref={cancel}
                    type="button"
                    className="ws-btn"
                    onClick={onCancel}
                    disabled={busy}
                >
                    Ακύρωση
                </button>
                <Button
                    primary={!danger}
                    danger={danger}
                    onClick={onConfirm}
                    busy={busy}
                >
                    {confirmLabel}
                </Button>
            </div>
        </dialog>
    );
}
export function Form({ form, onSubmit, children, className = "" }) {
    const [destination, setDestination] = useState(null);
    const bypass = useRef(false);
    useEffect(() => {
        const unload = (e) => {
            if (form.isDirty && !form.processing) {
                e.preventDefault();
                e.returnValue = "";
            }
        };
        window.addEventListener("beforeunload", unload);
        const remove = router.on("before", (event) => {
            if (bypass.current) {
                bypass.current = false;
                return;
            }
            if (
                form.isDirty &&
                !form.processing &&
                event.detail.visit.method === "get"
            ) {
                event.preventDefault();
                setDestination(event.detail.visit.url.href);
            }
        });
        return () => {
            remove();
            window.removeEventListener("beforeunload", unload);
        };
    }, [form.isDirty, form.processing]);
    useEffect(() => {
        const name = Object.keys(form.errors)[0];
        if (name) document.getElementsByName(name)[0]?.focus();
    }, [form.errors]);
    return (
        <>
            <form
                noValidate
                className={`ws-form ws-panel ${className}`}
                onSubmit={(e) => {
                    e.preventDefault();
                    onSubmit();
                }}
            >
                {children}
            </form>
            <ConfirmDialog
                open={!!destination}
                title="Μη αποθηκευμένες αλλαγές"
                onCancel={() => setDestination(null)}
                confirmLabel="Έξοδος χωρίς αποθήκευση"
                onConfirm={() => {
                    bypass.current = true;
                    const target = destination;
                    setDestination(null);
                    router.visit(target);
                }}
            >
                Οι αλλαγές σας δεν έχουν αποθηκευτεί. Θέλετε να φύγετε από τη
                φόρμα;
            </ConfirmDialog>
        </>
    );
}
export function FormFooter({ form, cancel, label = "Αποθήκευση", children }) {
    return (
        <div className="ws-form-footer">
            <div>{children}</div>
            <div className="ws-actions">
                <Button href={cancel}>Ακύρωση</Button>
                <Button type="submit" primary busy={form.processing}>
                    {form.processing ? "Αποθήκευση…" : label}
                </Button>
            </div>
        </div>
    );
}
export const customerLink = (customer) =>
    customer ? (
        <Link
            className="ws-record-link"
            href={`/workshop/customers/${customer.id}`}
        >
            {customer.full_name}
        </Link>
    ) : (
        "—"
    );
export const vehicleName = (vehicle) =>
    [vehicle?.make, vehicle?.model].filter(Boolean).join(" ");
// Days an order has been with the garage; five or more is flagged as late.
export const orderAge = (order) =>
    daysBetween(order.checked_in_at || order.created_at);
export const LATE_AFTER_DAYS = 5;
export function Age({ days }) {
    if (days === null || days === undefined) return null;
    return (
        <span
            className={`ws-age ${days >= LATE_AFTER_DAYS ? "ws-age-late" : ""}`}
        >
            {days <= 0 ? "σήμερα" : `${days} ημ.`}
        </span>
    );
}
export function PhoneLink({ customer }) {
    if (!customer?.phone) return null;
    return (
        <a
            className="ws-icon-btn"
            href={`tel:${customer.phone}`}
            aria-label={`Κλήση ${customer.full_name}`}
        >
            <Icon name="phone" size={16} />
        </a>
    );
}

/* Shared card shell for list pages. Header: plate, title, status badge.
   Body: the facts that matter. Footer: metadata and the primary action.
   The title link covers the whole card; inner links and buttons sit above it. */
export function RecordCard({
    href,
    label,
    plate,
    title,
    subtitle,
    badge,
    children,
    meta,
    actions,
}) {
    return (
        <article className="ws-card">
            <header className={`ws-card-head ${plate ? "" : "ws-card-head-plain"}`}>
                {plate && <div className="ws-card-plate">{plate}</div>}
                <div className="ws-card-title">
                    <h3>
                        <Link
                            className="ws-card-link"
                            href={href}
                            aria-label={label}
                        >
                            {title}
                        </Link>
                    </h3>
                    {subtitle && <p>{subtitle}</p>}
                </div>
                {badge && <div className="ws-card-badge">{badge}</div>}
            </header>
            <div className="ws-card-body">{children}</div>
            <footer className="ws-card-foot">
                <div className="ws-card-meta">{meta}</div>
                <div className="ws-card-actions">{actions}</div>
            </footer>
        </article>
    );
}
export function CardPerson({ customer }) {
    return (
        <div className="ws-card-person">
            <Icon name="people" size={15} />
            {customerLink(customer)}
            {customer?.phone && <span>{customer.phone}</span>}
        </div>
    );
}
export function CardFacts({ items }) {
    const shown = items.filter(([, value]) => value);
    if (!shown.length) return null;
    return (
        <dl className="ws-card-facts">
            {shown.map(([label, value]) => (
                <div key={label}>
                    <dt>{label}</dt>
                    <dd>{value}</dd>
                </div>
            ))}
        </dl>
    );
}
/* A responsive grid of cards with the same empty state and pagination as DataTable. */
export function CardGrid({ data, render, empty, caption, unit }) {
    const items = rows(data);
    return (
        <div className="ws-card-wrap">
            <ListCount data={data} unit={unit} />
            {items.length ? (
                <ul className="ws-card-grid" aria-label={caption}>
                    {items.map((item) => (
                        <li key={item.id}>{render(item)}</li>
                    ))}
                </ul>
            ) : (
                <Panel>
                    <EmptyState {...empty} />
                </Panel>
            )}
            <Pagination data={data} />
        </div>
    );
}
