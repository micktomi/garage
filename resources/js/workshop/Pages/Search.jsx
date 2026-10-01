import React from "react";
import { Link } from "@inertiajs/react";
import {
    PageHeading,
    SearchFilter,
    Panel,
    DataTable,
    Plate,
    customerLink,
} from "../Components/ui";
export default function Search({ vehicles, customers, q }) {
    return (
        <>
            <PageHeading
                title="Αναζήτηση"
                description="Βρείτε πινακίδα, πελάτη ή τηλέφωνο και συνεχίστε στην κατάλληλη καρτέλα."
            />
            <SearchFilter url="/workshop/search" q={q} />
            <div className="ws-stack">
                <Panel title="Οχήματα">
                    <DataTable
                        data={vehicles}
                        columns={[
                            {
                                label: "Πινακίδα",
                                render: (v) => <Plate vehicle={v} />,
                            },
                            {
                                label: "Όχημα",
                                render: (v) =>
                                    `${v.make || ""} ${v.model || ""}`,
                            },
                            {
                                label: "Πελάτης",
                                render: (v) => (
                                    <>
                                        {customerLink(v.customer)}
                                        <small>{v.customer?.phone}</small>
                                    </>
                                ),
                            },
                            {
                                label: "Ανοικτή εργασία",
                                render: (v) =>
                                    v.work_orders?.[0] ? (
                                        <Link
                                            href={`/workshop/work-orders/${v.work_orders[0].id}`}
                                        >
                                            Εντολή #{v.work_orders[0].id}
                                        </Link>
                                    ) : (
                                        "—"
                                    ),
                            },
                            {
                                label: "Ενέργεια",
                                render: (v) => (
                                    <Link
                                        href={`/workshop/work-orders/create?customer_id=${v.customer_id}&vehicle_id=${v.id}`}
                                    >
                                        Νέα εντολή →
                                    </Link>
                                ),
                            },
                        ]}
                    />
                </Panel>
                <Panel title="Πελάτες">
                    <DataTable
                        data={customers}
                        columns={[
                            {
                                label: "Πελάτης",
                                render: (c) => customerLink(c),
                            },
                            {
                                label: "Τηλέφωνο",
                                render: (c) => c.phone || "—",
                            },
                            {
                                label: "Ενέργεια",
                                render: (c) => (
                                    <Link href={`/workshop/customers/${c.id}`}>
                                        Καρτέλα πελάτη →
                                    </Link>
                                ),
                            },
                        ]}
                    />
                </Panel>
            </div>
        </>
    );
}
