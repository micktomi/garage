import React from "react";
import { Field } from "./ui";
export default function CustomerVehicleFields({
    form,
    customers,
    vehiclesByCustomer,
    mileage = false,
}) {
    const vehicles = vehiclesByCustomer[form.data.customer_id] || [];
    return (
        <div className="ws-form-grid">
            <Field
                label="Πελάτης"
                name="customer_id"
                form={form}
                as="select"
                onChange={(e) => {
                    const options = vehiclesByCustomer[e.target.value] || [];
                    const selected = options.length === 1 ? options[0] : null;
                    form.setData({
                        ...form.data,
                        customer_id: e.target.value,
                        vehicle_id: selected?.id || "",
                        ...(mileage
                            ? { current_mileage: selected?.mileage ?? "" }
                            : {}),
                    });
                }}
            >
                <option value="">Επιλέξτε πελάτη</option>
                {customers.map((c) => (
                    <option key={c.id} value={c.id}>
                        {c.full_name}
                    </option>
                ))}
            </Field>
            <Field
                label="Όχημα"
                name="vehicle_id"
                form={form}
                as="select"
                disabled={!form.data.customer_id}
                onChange={(e) => {
                    const selected = vehicles.find(
                        (v) => String(v.id) === e.target.value,
                    );
                    form.setData({
                        ...form.data,
                        vehicle_id: e.target.value,
                        ...(mileage
                            ? { current_mileage: selected?.mileage ?? "" }
                            : {}),
                    });
                }}
                hint={
                    form.data.customer_id && !vehicles.length
                        ? "Ο πελάτης δεν έχει όχημα. Προσθέστε πρώτα το όχημα από την καρτέλα πελάτη."
                        : undefined
                }
            >
                <option value="">Επιλέξτε όχημα</option>
                {vehicles.map((v) => (
                    <option key={v.id} value={v.id}>
                        {v.label}
                    </option>
                ))}
            </Field>
        </div>
    );
}
