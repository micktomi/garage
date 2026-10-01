import React from "react";
import { Link } from "@inertiajs/react";
import {
    DataTable,
    PageHeading,
    Panel,
    Plate,
    SearchFilter,
    Button,
    customerLink,
    date,
} from "../../Components/ui";
export default function Index({ vehicles, q }) {
    return (
        <>
            <PageHeading
                title="Οχήματα"
                description="Στοιχεία πελατών, συντήρηση και ιστορικό οχημάτων."
            >
                <Button href="/workshop/registration-scan">
                    Σάρωση άδειας
                </Button>
                <Button href="/workshop/vehicles/create" primary>
                    Νέο όχημα
                </Button>
            </PageHeading>
            <SearchFilter url="/workshop/vehicles" q={q} />
            <Panel>
                <DataTable
                    data={vehicles}
                    columns={[
                        {
                            label: "Πινακίδα",
                            render: (v) => <Plate vehicle={v} />,
                        },
                        {
                            label: "Όχημα",
                            render: (v) => (
                                <>
                                    {v.make} {v.model}
                                    <small>{v.year || "—"}</small>
                                </>
                            ),
                        },
                        {
                            label: "Πελάτης",
                            render: (v) => customerLink(v.customer),
                        },
                        {
                            label: "Χιλιόμετρα",
                            numeric: true,
                            render: (v) => v.mileage ?? "—",
                        },
                        {
                            label: "ΚΤΕΟ",
                            render: (v) => date(v.kteo_expires_at),
                        },
                        {
                            label: "Ενέργεια",
                            render: (v) => (
                                <Link href={`/workshop/vehicles/${v.id}/edit`}>
                                    Καρτέλα οχήματος →
                                </Link>
                            ),
                        },
                    ]}
                />
            </Panel>
        </>
    );
}
