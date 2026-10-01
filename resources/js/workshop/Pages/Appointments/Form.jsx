import React from "react";
import { useForm, usePage } from "@inertiajs/react";
import {
    PageHeading,
    Form,
    Field,
    Feedback,
    FormFooter,
} from "../../Components/ui";
import CustomerVehicleFields from "../../Components/CustomerVehicleFields";
export default function AppointmentForm({
    appointment,
    customers,
    vehiclesByCustomer,
    defaults = {},
}) {
    const { appointmentStatuses } = usePage().props;
    const localDate = appointment
        ? new Date(appointment.appointment_date).toLocaleDateString("sv-SE", {
              timeZone: "Europe/Athens",
          })
        : "";
    const localTime = appointment
        ? new Intl.DateTimeFormat("en-GB", {
              timeZone: "Europe/Athens",
              hour: "2-digit",
              minute: "2-digit",
              hour12: false,
          }).format(new Date(appointment.appointment_date))
        : "";
    const form = useForm({
        customer_id: appointment?.customer_id || defaults.customer_id || "",
        vehicle_id: appointment?.vehicle_id || defaults.vehicle_id || "",
        appointment_date: localDate,
        appointment_time: localTime,
        description: appointment?.description || "",
        status: appointment?.status || "scheduled",
    });
    return (
        <>
            <PageHeading
                title={appointment ? "Επεξεργασία ραντεβού" : "Νέο ραντεβού"}
                description="Επιλέξτε πελάτη, όχημα και χρόνο επίσκεψης."
            />
            <Feedback errors={form.errors} />
            <Form
                form={form}
                onSubmit={() =>
                    appointment
                        ? form.put(`/workshop/appointments/${appointment.id}`)
                        : form.post("/workshop/appointments")
                }
            >
                <section className="ws-form-section">
                    <h2>Πελάτης και όχημα</h2>
                    <CustomerVehicleFields
                        {...{ form, customers, vehiclesByCustomer }}
                    />
                </section>
                <section className="ws-form-section">
                    <h2>Επίσκεψη</h2>
                    <div className="ws-form-grid">
                        <Field
                            label="Ημερομηνία"
                            name="appointment_date"
                            form={form}
                            type="date"
                        />
                        <Field
                            label="Ώρα"
                            name="appointment_time"
                            form={form}
                            type="time"
                        />
                        <Field
                            label="Περιγραφή"
                            name="description"
                            form={form}
                            as="textarea"
                            wide
                        />
                        {appointment && (
                            <Field
                                label="Κατάσταση"
                                name="status"
                                form={form}
                                as="select"
                            >
                                {appointmentStatuses.map((s) => (
                                    <option value={s.value} key={s.value}>
                                        {s.label}
                                    </option>
                                ))}
                            </Field>
                        )}
                    </div>
                </section>
                <FormFooter
                    form={form}
                    cancel="/workshop/appointments"
                    label={
                        appointment
                            ? "Αποθήκευση ραντεβού"
                            : "Δημιουργία ραντεβού"
                    }
                />
            </Form>
        </>
    );
}
