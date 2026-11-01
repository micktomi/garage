import React from "react";
import { inputDate } from "../../Components/dates";
import { useForm } from "@inertiajs/react";
import {
    Button,
    CardGrid,
    Feedback,
    Field,
    Form,
    FormFooter,
    PageHeading,
    date,
} from "../../Components/ui";
import { AppointmentCard, WorkOrderCard } from "../../Components/cards";
import MakeModelFields from "../../Components/MakeModelFields";
export default function VehicleForm({
    vehicle,
    customers,
    modelsByMake,
    vehicleWorkOrders = [],
    vehicleAppointments = [],
    defaults = {},
}) {
    const form = useForm({
        customer_id: vehicle?.customer_id || defaults.customer_id || "",
        license_plate: vehicle?.plate_number || "",
        make: vehicle?.make || "",
        model: vehicle?.model || "",
        year: vehicle?.year || "",
        vin: vehicle?.vin || "",
        mileage: vehicle?.mileage ?? "",
        kteo_expires_at: inputDate(vehicle?.kteo_expires_at),
        notes: vehicle?.notes || "",
    });
    return (
        <>
            <PageHeading
                title={vehicle ? `Όχημα ${vehicle.plate_number}` : "Νέο όχημα"}
                description={
                    vehicle
                        ? "Στοιχεία, συντήρηση και ιστορικό οχήματος."
                        : "Καταχωρίστε το όχημα και συνδέστε το με τον πελάτη."
                }
            >
                {vehicle && (
                    <>
                        <Button href={`/workshop/customers/${vehicle.customer_id}`}>
                            Καρτέλα πελάτη
                        </Button>
                        <Button
                            href={`/workshop/work-orders/create?customer_id=${vehicle.customer_id}&vehicle_id=${vehicle.id}`}
                        >
                            Νέα εντολή οχήματος
                        </Button>
                    </>
                )}
            </PageHeading>
            <Feedback errors={form.errors} />
            <div className={vehicle ? "ws-columns" : undefined}>
                <Form
                    form={form}
                    onSubmit={() =>
                        vehicle
                            ? form.put(`/workshop/vehicles/${vehicle.id}`)
                            : form.post("/workshop/vehicles")
                    }
                >
                    <section className="ws-form-section">
                        <h2>Στοιχεία οχήματος</h2>
                        <div className="ws-form-grid">
                            <Field
                                label="Πελάτης / ιδιοκτήτης"
                                name="customer_id"
                                form={form}
                                as="select"
                            >
                                <option value="">Επιλέξτε πελάτη</option>
                                {customers.map((c) => (
                                    <option value={c.id} key={c.id}>
                                        {c.full_name}
                                    </option>
                                ))}
                            </Field>
                            <Field
                                label="Πινακίδα"
                                name="license_plate"
                                form={form}
                            />
                            <MakeModelFields
                                form={form}
                                modelsByMake={modelsByMake}
                            />
                            <Field
                                label="Έτος"
                                name="year"
                                form={form}
                                type="number"
                            />
                            <Field
                                label="VIN / αριθμός πλαισίου"
                                name="vin"
                                form={form}
                            />
                            <Field
                                label="Χιλιόμετρα"
                                name="mileage"
                                form={form}
                                type="number"
                            />
                            <Field
                                label="Λήξη ΚΤΕΟ"
                                name="kteo_expires_at"
                                form={form}
                                type="date"
                            />
                            <Field
                                label="Σημειώσεις"
                                name="notes"
                                form={form}
                                as="textarea"
                                wide
                            />
                        </div>
                        {vehicle?.fuel && (
                            <p className="ws-muted">
                                Καύσιμο: {vehicle.fuel} · Κυβισμός:{" "}
                                {vehicle.engine_cc || "—"} · Πρώτη άδεια:{" "}
                                {date(vehicle.first_registered_at)}
                            </p>
                        )}
                    </section>
                    <FormFooter
                        form={form}
                        cancel={
                            vehicle
                                ? "/workshop/vehicles"
                                : "/workshop/vehicles"
                        }
                        label={
                            vehicle
                                ? "Αποθήκευση οχήματος"
                                : "Δημιουργία οχήματος"
                        }
                    />
                </Form>
                {vehicle && (
                    <div className="ws-stack ws-aside">
                        <section>
                            <div className="ws-section-head">
                                <h2>Ιστορικό εργασιών / service</h2>
                            </div>
                            <CardGrid
                                data={vehicleWorkOrders}
                                caption="Ιστορικό εργασιών"
                                render={(order) => (
                                    <WorkOrderCard order={order} facts />
                                )}
                                empty={{
                                    title: "Δεν υπάρχουν εντολές για αυτό το όχημα.",
                                    description:
                                        "Δημιουργήστε την πρώτη εντολή εργασίας.",
                                }}
                            />
                        </section>
                        <section>
                            <div className="ws-section-head">
                                <h2>Ιστορικό ραντεβού</h2>
                            </div>
                            <CardGrid
                                data={vehicleAppointments}
                                caption="Ιστορικό ραντεβού"
                                render={(appointment) => (
                                    <AppointmentCard appointment={appointment} />
                                )}
                                empty={{
                                    title: "Δεν υπάρχουν ραντεβού για αυτό το όχημα.",
                                    description:
                                        "Προσθέστε ένα ραντεβού για την επόμενη επίσκεψη.",
                                }}
                            />
                        </section>
                    </div>
                )}
            </div>
        </>
    );
}
