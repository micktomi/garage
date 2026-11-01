import React from "react";
import {
    Button,
    CardGrid,
    DetailList,
    PageHeading,
    Panel,
} from "../../Components/ui";
import { VehicleCard, WorkOrderCard } from "../../Components/cards";
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
            <div className="ws-columns ws-columns-lead">
                <div className="ws-aside">
                    <Panel title="Στοιχεία επικοινωνίας" body>
                        <DetailList
                            items={[
                                [
                                    "Τηλέφωνο",
                                    customer.phone ? (
                                        <a href={`tel:${customer.phone}`}>
                                            {customer.phone}
                                        </a>
                                    ) : null,
                                ],
                                [
                                    "Email",
                                    customer.email ? (
                                        <a href={`mailto:${customer.email}`}>
                                            {customer.email}
                                        </a>
                                    ) : null,
                                ],
                                ["Διεύθυνση", customer.address],
                                ["Σημειώσεις", customer.notes],
                                ...(customer.afm ? [["ΑΦΜ", customer.afm]] : []),
                            ]}
                        />
                    </Panel>
                </div>
                <div className="ws-stack ws-main-col">
                    <section>
                        <div className="ws-section-head">
                            <h2>Οχήματα</h2>
                        </div>
                        <CardGrid
                            data={customer.vehicles}
                            caption="Οχήματα του πελάτη"
                            render={(vehicle) => (
                                <VehicleCard
                                    vehicle={vehicle}
                                    intent="order"
                                    showCustomer={false}
                                />
                            )}
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
                    </section>
                    <section>
                        <div className="ws-section-head">
                            <h2>Ιστορικό εργασιών</h2>
                        </div>
                        <CardGrid
                            data={workOrders}
                            caption="Ιστορικό εργασιών"
                            render={(order) => <WorkOrderCard order={order} />}
                            empty={{
                                title: "Καμία εντολή ακόμη",
                                description:
                                    "Οι εντολές του πελάτη εμφανίζονται εδώ.",
                            }}
                        />
                    </section>
                </div>
            </div>
        </>
    );
}
