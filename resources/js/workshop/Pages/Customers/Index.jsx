import React from "react";
import { Link } from "@inertiajs/react";
import {
    DataTable,
    PageHeading,
    Panel,
    SearchFilter,
    Button,
    customerLink,
} from "../../Components/ui";
export default function Index({ customers, q }) {
    return (
        <>
            <PageHeading
                title="Πελάτες"
                description="Επικοινωνία, οχήματα και ιστορικό εργασιών."
            >
                <Button href="/workshop/customers/create" primary>
                    Νέος πελάτης
                </Button>
            </PageHeading>
            <SearchFilter url="/workshop/customers" q={q} />
            <Panel>
                <DataTable
                    data={customers}
                    columns={[
                        { label: "Πελάτης", render: (c) => customerLink(c) },
                        {
                            label: "Τηλέφωνο",
                            render: (c) =>
                                c.phone ? (
                                    <a href={`tel:${c.phone}`}>{c.phone}</a>
                                ) : (
                                    "—"
                                ),
                        },
                        { label: "Email", render: (c) => c.email || "—" },
                        { label: "Οχήματα", render: (c) => c.vehicles_count },
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
        </>
    );
}
