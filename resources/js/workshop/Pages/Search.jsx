import React from "react";
import { CardGrid, FilterBar, PageHeading } from "../Components/ui";
import { CustomerCard, VehicleCard } from "../Components/cards";
export default function Search({ vehicles, customers, q }) {
    return (
        <>
            <PageHeading
                title="Αναζήτηση"
                description="Βρείτε πινακίδα, πελάτη ή τηλέφωνο και συνεχίστε στην κατάλληλη καρτέλα."
            />
            <FilterBar url="/workshop/search" q={q} />
            <div className="ws-stack">
                <section>
                    <div className="ws-section-head">
                        <h2>Οχήματα</h2>
                    </div>
                    <CardGrid
                        data={vehicles}
                        caption="Οχήματα"
                        unit={["όχημα", "οχήματα"]}
                        render={(vehicle) => (
                            <VehicleCard
                                vehicle={vehicle}
                                intent="order"
                                openOrder={vehicle.work_orders?.[0]}
                            />
                        )}
                        empty={{
                            title: "Κανένα όχημα",
                            description:
                                "Δοκιμάστε άλλη πινακίδα, μάρκα ή όνομα πελάτη.",
                        }}
                    />
                </section>
                <section>
                    <div className="ws-section-head">
                        <h2>Πελάτες</h2>
                    </div>
                    <CardGrid
                        data={customers}
                        caption="Πελάτες"
                        unit={["πελάτης", "πελάτες"]}
                        render={(customer) => (
                            <CustomerCard customer={customer} />
                        )}
                        empty={{
                            title: "Κανένας πελάτης",
                            description:
                                "Δοκιμάστε άλλο όνομα ή τηλέφωνο.",
                        }}
                    />
                </section>
            </div>
        </>
    );
}
