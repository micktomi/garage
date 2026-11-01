import React from "react";
import { Button, CardGrid, FilterBar, PageHeading } from "../../Components/ui";
import { CustomerCard } from "../../Components/cards";
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
            <FilterBar url="/workshop/customers" q={q} />
            <CardGrid
                data={customers}
                caption="Πελάτες"
                unit={["πελάτης", "πελάτες"]}
                render={(customer) => <CustomerCard customer={customer} />}
                empty={{
                    title: "Δεν βρέθηκαν πελάτες",
                    description:
                        "Αλλάξτε την αναζήτηση ή καταχωρίστε νέο πελάτη.",
                }}
            />
        </>
    );
}
