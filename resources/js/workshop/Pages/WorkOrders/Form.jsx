import React from "react";
import { inputDate } from "../../Components/dates";
import { useForm, usePage } from "@inertiajs/react";
import {
    Button,
    Feedback,
    Field,
    Form,
    FormFooter,
    PageHeading,
    money,
} from "../../Components/ui";
import CustomerVehicleFields from "../../Components/CustomerVehicleFields";
import { workOrderPayload } from "./payload";
const newPart = () => ({
    source: "",
    part_id: "",
    description: "",
    quantity: 1,
    unit_cost: "",
    unit_price: "",
    note: "",
});
export default function WorkOrderForm({
    workOrder,
    customers,
    vehiclesByCustomer,
    parts,
    defaults = {},
}) {
    const { auth } = usePage().props;
    const preselected = (vehiclesByCustomer[defaults.customer_id] || []).find(
        (v) => String(v.id) === String(defaults.vehicle_id),
    );
    const form = useForm({
        idempotency_key:
            workOrder?.idempotency_key ||
            defaults.idempotency_key ||
            crypto.randomUUID(),
        lock_version: workOrder?.lock_version ?? 0,
        customer_id: workOrder?.customer_id || defaults.customer_id || "",
        vehicle_id: workOrder?.vehicle_id || defaults.vehicle_id || "",
        problem_description: workOrder?.problem_description || "",
        diagnosis: workOrder?.diagnosis || "",
        work_performed: workOrder?.work_performed || "",
        current_mileage:
            workOrder?.current_mileage ?? preselected?.mileage ?? "",
        next_service_date: inputDate(workOrder?.next_service_date),
        next_service_mileage: workOrder?.next_service_mileage ?? "",
        labor_cost: workOrder?.labor_cost ?? "",
        parts:
            workOrder?.work_order_parts?.map((p) => ({ ...newPart(), ...p })) ||
            [],
    });
    const update = (index, key, value) =>
        form.setData(
            "parts",
            form.data.parts.map((row, i) =>
                i === index ? { ...row, [key]: value } : row,
            ),
        );
    const submit = () => {
        form.transform((data) => workOrderPayload(data, auth.canPrice));
        workOrder
            ? form.put(`/workshop/work-orders/${workOrder.id}`)
            : form.post("/workshop/work-orders");
    };
    const partsTotal = form.data.parts.reduce(
        (sum, p) =>
            sum +
            (p.source
                ? Number(p.quantity || 0) * Number(p.unit_price || 0)
                : 0),
        0,
    );
    return (
        <>
            <PageHeading
                title={
                    workOrder
                        ? `Επεξεργασία εντολής #${workOrder.id}`
                        : "Νέα εντολή εργασίας"
                }
                description="Καταγράψτε το όχημα, την εργασία και τα υλικά."
            />
            <Feedback errors={form.errors} />
            <Form form={form} onSubmit={submit}>
                <input
                    type="hidden"
                    name="idempotency_key"
                    value={form.data.idempotency_key}
                />
                <input
                    type="hidden"
                    name="lock_version"
                    value={form.data.lock_version}
                />
                <section className="ws-form-section">
                    <h2>Πελάτης και όχημα</h2>
                    <CustomerVehicleFields
                        {...{ form, customers, vehiclesByCustomer }}
                        mileage
                    />
                </section>
                <section className="ws-form-section">
                    <h2>Εργασία</h2>
                    <div className="ws-form-grid">
                        <Field
                            label="Πρόβλημα / αίτημα πελάτη"
                            name="problem_description"
                            form={form}
                            as="textarea"
                            wide
                        />
                        <Field
                            label="Διάγνωση"
                            name="diagnosis"
                            form={form}
                            as="textarea"
                        />
                        <Field
                            label="Εργασίες που εκτελέστηκαν"
                            name="work_performed"
                            form={form}
                            as="textarea"
                        />
                    </div>
                </section>
                <section className="ws-form-section">
                    <h2>Χιλιόμετρα και επόμενο service</h2>
                    <div className="ws-form-grid">
                        <Field
                            label="Τρέχοντα χιλιόμετρα"
                            name="current_mileage"
                            form={form}
                            type="number"
                            min="0"
                        />
                        <Field
                            label="Επόμενο service — χιλιόμετρα"
                            name="next_service_mileage"
                            form={form}
                            type="number"
                            min="0"
                        />
                        <Field
                            label="Επόμενο service — ημερομηνία"
                            name="next_service_date"
                            form={form}
                            type="date"
                        />
                        <Field
                            label="Εργατικά (€)"
                            name="labor_cost"
                            form={form}
                            type="number"
                            min="0"
                            step="0.01"
                        />
                    </div>
                </section>
                <section className="ws-form-section">
                    <div className="ws-page-heading">
                        <h2>Ανταλλακτικά και υλικά</h2>
                        <Button
                            onClick={() =>
                                form.setData("parts", [
                                    ...form.data.parts,
                                    newPart(),
                                ])
                            }
                        >
                            Προσθήκη γραμμής
                        </Button>
                    </div>
                    {!form.data.parts.length && (
                        <p className="ws-muted">
                            Δεν έχουν προστεθεί ανταλλακτικά.
                        </p>
                    )}
                    {form.data.parts.map((row, index) => {
                        const rowForm = {
                            data: row,
                            errors: Object.fromEntries(
                                Object.entries(form.errors)
                                    .filter(([key]) =>
                                        key.startsWith(`parts.${index}.`),
                                    )
                                    .map(([key, value]) => [
                                        key.split(".").at(-1),
                                        value,
                                    ]),
                            ),
                            setData: (key, value) => update(index, key, value),
                        };
                        return (
                            <div className="ws-line-form" key={index}>
                                <div className="ws-page-heading">
                                    <h3>Γραμμή {index + 1}</h3>
                                    <Button
                                        danger
                                        onClick={() =>
                                            form.setData(
                                                "parts",
                                                form.data.parts.filter(
                                                    (_, i) => i !== index,
                                                ),
                                            )
                                        }
                                    >
                                        Αφαίρεση
                                    </Button>
                                </div>
                                <div className="ws-form-grid">
                                    <Field
                                        label="Προέλευση"
                                        name="source"
                                        inputName={`parts.${index}.source`}
                                        form={rowForm}
                                        as="select"
                                        onChange={(e) =>
                                            form.setData(
                                                "parts",
                                                form.data.parts.map((p, i) =>
                                                    i === index
                                                        ? {
                                                              ...p,
                                                              source: e.target
                                                                  .value,
                                                              part_id: "",
                                                              unit_cost: "",
                                                          }
                                                        : p,
                                                ),
                                            )
                                        }
                                    >
                                        <option value="">
                                            Επιλέξτε προέλευση
                                        </option>
                                        <option value="from_stock">
                                            Από απόθεμα
                                        </option>
                                        <option value="customer_supplied">
                                            Το έφερε ο πελάτης
                                        </option>
                                        <option value="purchased_for_job">
                                            Αγοράστηκε για εργασία
                                        </option>
                                    </Field>
                                    {row.source === "from_stock" ? (
                                        <Field
                                            label="Ανταλλακτικό"
                                            name="part_id"
                                            inputName={`parts.${index}.part_id`}
                                            form={rowForm}
                                            as="select"
                                            onChange={(e) => {
                                                const part = parts.find(
                                                    (p) =>
                                                        String(p.id) ===
                                                        e.target.value,
                                                );
                                                form.setData(
                                                    "parts",
                                                    form.data.parts.map(
                                                        (p, i) =>
                                                            i === index
                                                                ? {
                                                                      ...p,
                                                                      part_id:
                                                                          e
                                                                              .target
                                                                              .value,
                                                                      unit_price:
                                                                          part?.sale_price ||
                                                                          "",
                                                                      unit_cost:
                                                                          part?.purchase_price ||
                                                                          "",
                                                                  }
                                                                : p,
                                                    ),
                                                );
                                            }}
                                        >
                                            <option value="">
                                                Επιλέξτε ανταλλακτικό
                                            </option>
                                            {parts.map((p) => (
                                                <option key={p.id} value={p.id}>
                                                    {p.name} · απόθεμα{" "}
                                                    {p.quantity}
                                                </option>
                                            ))}
                                        </Field>
                                    ) : (
                                        <Field
                                            label="Περιγραφή υλικού"
                                            name="description"
                                            inputName={`parts.${index}.description`}
                                            form={rowForm}
                                        />
                                    )}
                                    <Field
                                        label="Ποσότητα"
                                        name="quantity"
                                        inputName={`parts.${index}.quantity`}
                                        form={rowForm}
                                        type="number"
                                        step="0.5"
                                        min="0.5"
                                    />
                                    <Field
                                        label="Τιμή / τεμ. (€)"
                                        name="unit_price"
                                        inputName={`parts.${index}.unit_price`}
                                        form={rowForm}
                                        type="number"
                                        step="0.01"
                                    />
                                    {auth.canPrice &&
                                        row.source !== "customer_supplied" && (
                                            <Field
                                                label="Κόστος / τεμ. (€)"
                                                name="unit_cost"
                                                inputName={`parts.${index}.unit_cost`}
                                                form={rowForm}
                                                type="number"
                                                step="0.01"
                                            />
                                        )}
                                    <Field
                                        label="Σημείωση"
                                        name="note"
                                        inputName={`parts.${index}.note`}
                                        form={rowForm}
                                    />
                                </div>
                                <p className="ws-muted">
                                    Προσωρινό σύνολο γραμμής:{" "}
                                    {money(
                                        Number(row.quantity || 0) *
                                            Number(row.unit_price || 0),
                                    )}
                                </p>
                            </div>
                        );
                    })}
                </section>
                <FormFooter
                    form={form}
                    cancel={
                        workOrder
                            ? `/workshop/work-orders/${workOrder.id}`
                            : "/workshop/work-orders"
                    }
                    label={
                        workOrder ? "Αποθήκευση εντολής" : "Δημιουργία εντολής"
                    }
                >
                    <small className="ws-muted">
                        Προεπισκόπηση · τελικός υπολογισμός στην αποθήκευση
                    </small>
                    <h2>
                        {money(Number(form.data.labor_cost || 0) + partsTotal)}
                    </h2>
                </FormFooter>
            </Form>
        </>
    );
}
