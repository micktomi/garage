import React from "react";
import { Link } from "@inertiajs/react";
import {
    Icon,
    PageHeading,
    Panel,
    DataTable,
    workOrderColumns,
    Plate,
    Button,
    date,
    time,
    customerLink,
    StatusBadge,
    EmptyState,
} from "../Components/ui";
export default function Dashboard(props) {
    const metrics = [
        [
            "Στο συνεργείο",
            props.vehiclesInShop,
            "Οχήματα με ενεργή παραμονή",
            "/workshop/work-orders",
            "info",
            "car",
        ],
        [
            "Ανοικτές εντολές",
            props.openWorkOrders,
            `${props.awaitingParts} σε αναμονή ανταλλακτικού`,
            "/workshop/work-orders",
            "warning",
            "work",
        ],
        [
            "Ραντεβού σήμερα",
            props.todayAppointments,
            "Η σημερινή ουρά υποδοχής",
            "/workshop/appointments",
            "success",
            "calendar",
        ],
        [
            "ΚΤΕΟ που έληξαν",
            props.expiredKteo,
            `${props.expiringKteo} λήγουν σε 30 ημέρες`,
            "/workshop/kteo",
            props.expiredKteo ? "danger" : "neutral",
            "check",
        ],
    ];
    return (
        <>
            <PageHeading
                title="Επισκόπηση συνεργείου"
                description={props.todayLabel}
            >
                <Button href="/workshop/registration-scan">
                    <Icon name="scan" />
                    <span className="ws-btn-label">Σάρωση άδειας</span>
                </Button>
            </PageHeading>
            <div className="ws-metrics">
                {metrics.map(([label, count, note, href, tone, icon], i) => (
                    <Link
                        className={`ws-panel ws-metric ws-metric-${tone}`}
                        href={href}
                        key={label}
                    >
                        <span className="ws-metric-top">
                            <span className="ws-metric-label">{label}</span>
                            <span className="ws-metric-icon">
                                <Icon name={icon} />
                            </span>
                        </span>
                        <strong className={i === 3 && count ? "ws-danger" : ""}>
                            {count}
                        </strong>
                        <small>{note}</small>
                    </Link>
                ))}
            </div>
            <div className="ws-stack">
                <Panel
                    title="Οχήματα στο συνεργείο"
                    action={
                        <Link href="/workshop/work-orders">
                            Όλες οι εντολές →
                        </Link>
                    }
                >
                    <DataTable
                        data={props.inShopWorkOrders}
                        columns={workOrderColumns}
                        empty={{
                            title: "Το συνεργείο είναι ελεύθερο",
                            description:
                                "Τα οχήματα εμφανίζονται εδώ με την καταχώριση νέας εντολής.",
                        }}
                    />
                </Panel>
                <div className="ws-columns">
                    <Panel
                        title="Σημερινά ραντεβού"
                        action={
                            <Link href="/workshop/appointments/create">
                                Προσθήκη →
                            </Link>
                        }
                    >
                        <DataTable
                            data={props.todayAppointmentRows}
                            columns={[
                                {
                                    label: "Ώρα",
                                    render: (a) => time(a.appointment_date),
                                },
                                {
                                    label: "Πελάτης",
                                    render: (a) => customerLink(a.customer),
                                },
                                {
                                    label: "Όχημα",
                                    render: (a) => (
                                        <Plate vehicle={a.vehicle} />
                                    ),
                                },
                                {
                                    label: "Κατάσταση",
                                    render: (a) => (
                                        <StatusBadge
                                            status={a.status}
                                            appointment
                                        />
                                    ),
                                },
                                {
                                    label: "Ενέργεια",
                                    render: (a) => (
                                        <Link
                                            href={`/workshop/appointments/${a.id}/edit`}
                                        >
                                            Άνοιγμα
                                        </Link>
                                    ),
                                },
                            ]}
                            empty={{
                                title: "Δεν υπάρχουν ραντεβού σήμερα",
                                description:
                                    "Προσθέστε το επόμενο προγραμματισμένο ραντεβού.",
                            }}
                        />
                    </Panel>
                    <Panel
                        title="Προτεραιότητα ΚΤΕΟ"
                        action={<Link href="/workshop/kteo">Όλα →</Link>}
                    >
                        <DataTable
                            data={[
                                ...(props.expiredKteoVehicles || []),
                                ...(props.expiringKteoVehicles || []),
                            ].slice(0, 5)}
                            columns={[
                                {
                                    label: "Όχημα",
                                    render: (v) => <Plate vehicle={v} />,
                                },
                                {
                                    label: "Λήξη",
                                    render: (v) => date(v.kteo_expires_at),
                                },
                                {
                                    label: "Πελάτης",
                                    render: (v) => customerLink(v.customer),
                                },
                            ]}
                            empty={{
                                title: "Δεν υπάρχουν επείγουσες λήξεις",
                                description:
                                    "Ο έλεγχος καλύπτει τις επόμενες 30 ημέρες.",
                            }}
                        />
                    </Panel>
                </div>
                <Panel title="Πρόσφατες ανοικτές εντολές">
                    <DataTable
                        data={props.recentWorkOrders}
                        columns={workOrderColumns}
                    />
                </Panel>
            </div>
        </>
    );
}
