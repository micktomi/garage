import React from "react";
import {
    Button,
    CardGrid,
    PageHeading,
    FilterBar,
} from "../../Components/ui";
import { VehicleCard } from "../../Components/cards";
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
            <FilterBar url="/workshop/vehicles" q={q} />
            <CardGrid
                data={vehicles}
                caption="Οχήματα"
                unit={["όχημα", "οχήματα"]}
                render={(vehicle) => <VehicleCard vehicle={vehicle} />}
                empty={{
                    title: "Δεν βρέθηκαν οχήματα",
                    description:
                        "Αλλάξτε την αναζήτηση ή καταχωρίστε νέο όχημα.",
                }}
            />
        </>
    );
}
