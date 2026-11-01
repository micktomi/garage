import React from "react";
import { CardGrid, PageHeading } from "../../Components/ui";
import { KteoCard } from "../../Components/cards";
export default function Index({ vehicles }) {
    return (
        <>
            <PageHeading
                title="ΚΤΕΟ"
                description="Ληγμένα και επερχόμενα ΚΤΕΟ εντός 30 ημερών. Τα παλαιότερα εμφανίζονται πρώτα."
            />
            <CardGrid
                data={vehicles}
                caption="ΚΤΕΟ προς υπενθύμιση"
                unit={["όχημα", "οχήματα"]}
                render={(vehicle) => <KteoCard vehicle={vehicle} />}
                empty={{
                    title: "Δεν υπάρχουν επείγουσες λήξεις",
                    description: "Ο έλεγχος καλύπτει τις επόμενες 30 ημέρες.",
                }}
            />
        </>
    );
}
