import React from "react";
import {
    PageHeading,
    Panel,
    Button,
    DataTable,
    Plate,
    date,
    StatusBadge,
    money,
} from "../../Components/ui";
import { Link } from "@inertiajs/react";
export default function Show({ customer, workOrders }) {
    return (
        <>
            <PageHeading
                title={customer.full_name}
                description="Καρτέλα πελάτη"
            >
                <Button
                    href={`/workshop/vehicles/create?customer_id=${customer.id}`}
                >
                    Προσθήκη οχήματος
                </Button>
                <Button
                    href={`/workshop/customers/${customer.id}/edit`}
                    primary
                >
                    Επεξεργασία πελάτη
                </Button>
            </PageHeading>
            <div className="ws-stack">
                <Panel title="Στοιχεία επικοινωνίας" body>
                    <dl className="ws-detail-list">
                        <div>
                            <dt>Τηλέφωνο</dt>
                            <dd>
                                {customer.phone ? (
                                    <a href={`tel:${customer.phone}`}>
                                        {customer.phone}
                                    </a>
                                ) : (
                                    "—"
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd>
                                {customer.email ? (
                                    <a href={`mailto:${customer.email}`}>
                                        {customer.email}
                                    </a>
                                ) : (
                                    "—"
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt>Διεύθυνση</dt>
                            <dd>{customer.address || "—"}</dd>
                        </div>
                        <div>
                            <dt>Σημειώσεις</dt>
                            <dd>{customer.notes || "—"}</dd>
                        </div>
                        {customer.afm && (
                            <div>
                                <dt>ΑΦΜ</dt>
                                <dd>{customer.afm}</dd>
                            </div>
                        )}
                    </dl>
                </Panel>
                <Panel title="Οχήματα">
                    <DataTable
                        data={customer.vehicles}
                        columns={[
                            {
                                label: "Πινακίδα",
                                render: (v) => <Plate vehicle={v} />,
                            },
                            {
                                label: "Μάρκα / μοντέλο",
                                render: (v) =>
                                    `${v.make || ""} ${v.model || ""}`,
                            },
                            {
                                label: "ΚΤΕΟ",
                                render: (v) => date(v.kteo_expires_at),
                            },
                            {
                                label: "Ενέργεια",
                                render: (v) => (
                                    <Link
                                        href={`/workshop/work-orders/create?customer_id=${customer.id}&vehicle_id=${v.id}`}
                                    >
                                        Νέα εντολή →
                                    </Link>
                                ),
                            },
                        ]}
                        empty={{
                            title: "Δεν υπάρχει όχημα για τον πελάτη",
                            description:
                                "Προσθέστε το πρώτο όχημα για να καταχωρίσετε εργασία.",
                            action: (
                                <Button
                                    href={`/workshop/vehicles/create?customer_id=${customer.id}`}
                                >
                                    Προσθήκη οχήματος
                                </Button>
                            ),
                        }}
                    />
                </Panel>
                <Panel title="Ιστορικό εργασιών">
                    <DataTable
                        data={workOrders}
                        columns={[
                            {
                                label: "Εντολή",
                                render: (o) => (
                                    <Link
                                        href={`/workshop/work-orders/${o.id}`}
                                    >
                                        #{o.id}
                                    </Link>
                                ),
                            },
                            {
                                label: "Όχημα",
                                render: (o) => <Plate vehicle={o.vehicle} />,
                            },
                            {
                                label: "Εργασία",
                                render: (o) => o.problem_description,
                            },
                            {
                                label: "Κατάσταση",
                                render: (o) => (
                                    <StatusBadge status={o.status} />
                                ),
                            },
                            {
                                label: "Σύνολο",
                                numeric: true,
                                render: (o) => money(o.total_cost),
                            },
                        ]}
                    />
                </Panel>
            </div>
        </>
    );
}
