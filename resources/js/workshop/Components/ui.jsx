import React, { useEffect, useId, useRef, useState } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";

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
export const time = (value) =>
    value
        ? new Intl.DateTimeFormat("el-GR", {
              timeZone: "Europe/Athens",
              hour: "2-digit",
              minute: "2-digit",
          }).format(new Date(value))
        : "—";
export const rows = (collection) =>
    Array.isArray(collection) ? collection : collection?.data || [];
export function Icon({ name = "grid", size = 18 }) {
    const paths = {
        grid: "M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z",
        work: "M4 6h16v15H4z M8 3h8v6H8z M8 13h8 M8 17h5",
        calendar: "M3 5h18v16H3z M7 3v4 M17 3v4 M3 10h18",
        people: "M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M13 7a4 4 0 1 1-8 0a4 4 0 0 1 8 0 M17 3a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-4",
        car: "M3 13l2-7h14l2 7 M3 13h18v6H3z M6 19v2 M18 19v2 M6 16h2 M16 16h2",
        check: "M4 12l5 5L20 6",
        parts: "M3 7l9-4 9 4-9 4z M3 7v10l9 4 9-4V7 M12 11v10",
        assistant:
            "M12 3v3 M12 18v3 M3 12h3 M18 12h3 M8 8l4-2 4 2 2 4-2 4-4 2-4-2-2-4z",
        scan: "M3 8V3h5 M16 3h5v5 M21 16v5h-5 M8 21H3v-5 M7 8h10v8H7z",
        arrow: "M5 12h14 M13 6l6 6-6 6",
        admin: "M12 3l9 5v6c0 4-9 7-9 7s-9-3-9-7V8z",
        plus: "M12 5v14 M5 12h14",
    };
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
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
export function StatusBadge({ status, appointment = false }) {
    const props = usePage().props;
    const choice = (
        appointment ? props.appointmentStatuses : props.workOrderStatuses
    )?.find((item) => item.value === status);
    return (
        <span className={`ws-badge ws-badge-${choice?.tone || "neutral"}`}>
            {choice?.label || status || "—"}
        </span>
    );
}
export function Plate({ vehicle, link = true }) {
    if (!vehicle) return "—";
    const badge = <span className="ws-plate">{vehicle.plate_number}</span>;
    return link ? (
        <Link
            href={`/workshop/vehicles/${vehicle.id}/edit`}
            aria-label={`Όχημα ${vehicle.plate_number}`}
        >
            {badge}
        </Link>
    ) : (
        badge
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
    if (!data || Array.isArray(data)) return null;
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
export function SearchFilter({ url, q = "", children, params = {} }) {
    const [query, setQuery] = useState(q);
    const ref = useRef();
    useEffect(() => setQuery(q), [q]);
    const search = (value) =>
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
                search(query);
            }}
        >
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
                            search("");
                            ref.current.focus();
                        }}
                    >
                        ×
                    </button>
                )}
            </div>
            {children}
            <Button type="submit">Αναζήτηση</Button>
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
export const workOrderColumns = [
    {
        label: "Εντολή",
        render: (o) => (
            <Link
                className="ws-record-link"
                href={`/workshop/work-orders/${o.id}`}
            >
                #{String(o.id).padStart(4, "0")}
                <small>{date(o.created_at)}</small>
            </Link>
        ),
    },
    {
        label: "Όχημα",
        render: (o) => (
            <>
                <Plate vehicle={o.vehicle} />
                <small>
                    {[o.vehicle?.make, o.vehicle?.model]
                        .filter(Boolean)
                        .join(" ")}
                </small>
            </>
        ),
    },
    {
        label: "Πελάτης",
        render: (o) => (
            <>
                {customerLink(o.customer)}
                <small>{o.customer?.phone}</small>
            </>
        ),
    },
    {
        label: "Εργασία",
        render: (o) => (
            <Link href={`/workshop/work-orders/${o.id}`}>
                {o.problem_description}
            </Link>
        ),
    },
    { label: "Κατάσταση", render: (o) => <StatusBadge status={o.status} /> },
    { label: "Σύνολο", numeric: true, render: (o) => money(o.total_cost) },
];
