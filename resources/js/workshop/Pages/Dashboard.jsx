import React from "react";
import { Head, Link } from "@inertiajs/react";
import {
    EmptyState,
    Icon,
    Panel,
    Plate,
    StatusBadge,
    date,
    kteoStatus,
} from "../Components/ui";
import { StatusBoard } from "../Components/cards";
import { timeOfDay } from "../Components/dates";

const vehicleName = (vehicle) =>
    [vehicle?.make, vehicle?.model].filter(Boolean).join(" ");

export default function Dashboard(props) {
    const kteoVehicles = [
        ...(props.expiredKteoVehicles || []),
        ...(props.expiringKteoVehicles || []),
    ].slice(0, 5);
    const glance = [
        [props.vehiclesInShop, "οχήματα στο συνεργείο", "/workshop/work-orders"],
        [props.todayAppointments, "ραντεβού σήμερα", "/workshop/appointments"],
        [props.expiredKteo, "ΚΤΕΟ που έληξαν", "/workshop/kteo", props.expiredKteo > 0],
    ];
    return (
        <>
            <Head title="Συνεργείο σήμερα" />
            <div className="ws-dash-head">
                <div>
                    <div className="ws-dash-date">{props.todayLabel}</div>
                    <h1>Συνεργείο σήμερα</h1>
                </div>
                <nav className="ws-glance" aria-label="Συνοπτικά στοιχεία">
                    {glance.map(([count, label, href, late]) => (
                        <Link
                            key={label}
                            href={href}
                            className={late ? "is-late" : ""}
                        >
                            <b>{count}</b>
                            <span>{label}</span>
                        </Link>
                    ))}
                </nav>
            </div>
            <section className="ws-dash-section" aria-label="Πίνακας εργασιών">
                <div className="ws-section-title">
                    <h2>Πίνακας εργασιών</h2>
                    <Link href="/workshop/work-orders">
                        Όλες οι εντολές →
                    </Link>
                </div>
                <StatusBoard
                    orders={props.boardWorkOrders}
                    counts={props.boardCounts}
                    empty={{
                        title: "Το συνεργείο είναι ελεύθερο",
                        description:
                            "Τα οχήματα εμφανίζονται εδώ με την καταχώριση νέας εντολής.",
                    }}
                />
            </section>
            <div className="ws-duo">
                <Panel
                    title="Ραντεβού σήμερα"
                    action={
                        <Link href="/workshop/appointments/create">
                            Προσθήκη +
                        </Link>
                    }
                >
                    {props.todayAppointmentRows?.length ? (
                        <ul className="ws-agenda">
                            {props.todayAppointmentRows.map((a) => (
                                <li
                                    key={a.id}
                                    className="ws-agenda-row ws-agenda-appt"
                                >
                                    <span className="ws-agenda-time">
                                        {timeOfDay(a.appointment_date)}
                                    </span>
                                    <div className="ws-agenda-who">
                                        <Link
                                            href={`/workshop/appointments/${a.id}/edit`}
                                        >
                                            {a.customer?.full_name || "—"}
                                        </Link>
                                        <small>
                                            <Plate
                                                vehicle={a.vehicle}
                                                link={false}
                                            />
                                            {vehicleName(a.vehicle)}
                                        </small>
                                    </div>
                                    <StatusBadge
                                        status={a.status}
                                        appointment
                                    />
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <EmptyState
                            title="Δεν υπάρχουν ραντεβού σήμερα"
                            description="Προσθέστε το επόμενο προγραμματισμένο ραντεβού."
                        />
                    )}
                </Panel>
                <Panel
                    title="ΚΤΕΟ προς υπενθύμιση"
                    action={<Link href="/workshop/kteo">Όλα →</Link>}
                >
                    {kteoVehicles.length ? (
                        <ul className="ws-agenda">
                            {kteoVehicles.map((v) => {
                                const status = kteoStatus(v) || { tone: "calm", label: "—" };
                                return (
                                    <li
                                        key={v.id}
                                        className="ws-agenda-row ws-agenda-kteo"
                                    >
                                        <div className="ws-agenda-who">
                                            <strong>
                                                {v.customer?.full_name || "—"}
                                            </strong>
                                            <small>
                                                <Plate vehicle={v} />
                                                {date(v.kteo_expires_at)}
                                            </small>
                                        </div>
                                        <span
                                            className={`ws-badge ws-badge-${status.tone}`}
                                        >
                                            {status.label}
                                        </span>
                                        {v.customer?.phone ? (
                                            <a
                                                className="ws-icon-btn"
                                                href={`tel:${v.customer.phone}`}
                                                aria-label={`Κλήση ${v.customer.full_name}`}
                                            >
                                                <Icon name="phone" size={16} />
                                            </a>
                                        ) : (
                                            <span />
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    ) : (
                        <EmptyState
                            title="Δεν υπάρχουν επείγουσες λήξεις"
                            description="Ο έλεγχος καλύπτει τις επόμενες 30 ημέρες."
                        />
                    )}
                </Panel>
            </div>
        </>
    );
}
