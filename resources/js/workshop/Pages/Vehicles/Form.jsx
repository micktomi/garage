import React from "react";
import { inputDate } from "../../Components/dates";
import { Link, useForm } from "@inertiajs/react";
import {
    PageHeading,
    Form,
    Field,
    Feedback,
    FormFooter,
    Panel,
    DataTable,
    StatusBadge,
    date,
} from "../../Components/ui";
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
                {vehicle && <ButtonLinks vehicle={vehicle} />}
            </PageHeading>
            <Feedback errors={form.errors} />
            <div className="ws-stack">
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
                    <>
                        <Panel title="Ιστορικό εργασιών / service">
                            <DataTable
                                data={vehicleWorkOrders}
                                columns={[
                                    {
                                        label: "Εντολή",
                                        render: (o) => (
                                            <Link
                                                href={`/workshop/work-orders/${o.id}`}
                                            >
                                                #{o.id}
                                            </Link>
                                        ),
                                    },
                                    {
                                        label: "Εργασία",
                                        render: (o) => o.problem_description,
                                    },
                                    {
                                        label: "Κατάσταση",
                                        render: (o) => (
                                            <StatusBadge status={o.status} />
                                        ),
                                    },
                                    {
                                        label: "Χιλιόμετρα",
                                        render: (o) => o.current_mileage || "—",
                                    },
                                    {
                                        label: "Επόμενο service",
                                        render: (o) => (
                                            <>
                                                {date(o.next_service_date)}
                                                <small>
                                                    {o.next_service_mileage
                                                        ? `${o.next_service_mileage} χλμ.`
                                                        : ""}
                                                </small>
                                            </>
                                        ),
                                    },
                                ]}
                                empty={{
                                    title: "Δεν υπάρχουν εντολές για αυτό το όχημα.",
                                    description:
                                        "Δημιουργήστε την πρώτη εντολή εργασίας.",
                                }}
                            />
                        </Panel>
                        <Panel title="Ιστορικό ραντεβού">
                            <DataTable
                                data={vehicleAppointments}
                                columns={[
                                    {
                                        label: "Ημερομηνία",
                                        render: (a) => date(a.appointment_date),
                                    },
                                    {
                                        label: "Περιγραφή",
                                        render: (a) => a.description,
                                    },
                                    {
                                        label: "Κατάσταση",
                                        render: (a) => (
                                            <StatusBadge
                                                status={a.status}
                                                appointment
                                            />
                                        ),
                                    },
                                    {
                                        label: "Ενέργεια",
                                        render: (a) => (
                                            <Link
                                                href={`/workshop/appointments/${a.id}/edit`}
                                            >
                                                Επεξεργασία
                                            </Link>
                                        ),
                                    },
                                ]}
                                empty={{
                                    title: "Δεν υπάρχουν ραντεβού για αυτό το όχημα.",
                                    description:
                                        "Προσθέστε ένα ραντεβού για την επόμενη επίσκεψη.",
                                }}
                            />
                        </Panel>
                    </>
                )}
            </div>
        </>
    );
}
function ButtonLinks({ vehicle }) {
    return (
        <>
            <Link
                className="ws-btn"
                href={`/workshop/customers/${vehicle.customer_id}`}
            >
                Καρτέλα πελάτη
            </Link>
            <Link
                className="ws-btn ws-btn-primary"
                href={`/workshop/work-orders/create?customer_id=${vehicle.customer_id}&vehicle_id=${vehicle.id}`}
            >
                Νέα εντολή οχήματος
            </Link>
        </>
    );
}
