import React from "react";
import {
    Button,
    CardGrid,
    DetailList,
    PageHeading,
    Panel,
    date,
    vehicleName,
} from "../../Components/ui";
import { CustomerCard, WorkOrderCard } from "../../Components/cards";
export default function Show({ vehicle, workOrders }) {
    const subtitle = [vehicleName(vehicle), vehicle.year]
        .filter(Boolean)
        .join(" · ");
    const mileage = vehicle.mileage
        ? `${new Intl.NumberFormat("el-GR").format(vehicle.mileage)} χλμ`
        : null;
    const engine = [vehicle.fuel, vehicle.engine_cc ? `${vehicle.engine_cc} cc` : null]
        .filter(Boolean)
        .join(" · ");
    return (
        <>
            <PageHeading
                title={vehicle.plate_number}
                description={subtitle || "Καρτέλα οχήματος"}
            >
                <Button
                    href={`/workshop/work-orders/create?customer_id=${vehicle.customer_id}&vehicle_id=${vehicle.id}`}
                >
                    Νέα εντολή
                </Button>
                <Button
                    href={`/workshop/vehicles/${vehicle.id}/edit`}
                    primary
                >
                    Επεξεργασία
                </Button>
            </PageHeading>
            <div className="ws-columns ws-columns-lead">
                <div className="ws-aside">
                    {vehicle.customer && (
                        <CustomerCard customer={vehicle.customer} />
                    )}
                </div>
                <div className="ws-stack ws-main-col">
                    <Panel title="Στοιχεία οχήματος" body>
                        <DetailList
                            items={[
                                ["Κατασκευαστής", vehicle.make],
                                ["Μοντέλο", vehicle.model],
                                ["Έτος", vehicle.year],
                                ["Κινητήρας", engine || null],
                                ["VIN", vehicle.vin],
                                ["Χιλιόμετρα", mileage],
                                [
                                    "ΚΤΕΟ έως",
                                    vehicle.kteo_expires_at
                                        ? date(vehicle.kteo_expires_at)
                                        : null,
                                ],
                                ["Σημειώσεις", vehicle.notes],
                            ]}
                        />
                    </Panel>
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
                                    "Οι εντολές του οχήματος εμφανίζονται εδώ.",
                            }}
                        />
                    </section>
                </div>
            </div>
        </>
    );
}
