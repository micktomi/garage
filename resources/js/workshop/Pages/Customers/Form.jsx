import React from "react";
import { useForm } from "@inertiajs/react";
import {
    PageHeading,
    Form,
    Field,
    Feedback,
    FormFooter,
} from "../../Components/ui";
export default function CustomerForm({ customer }) {
    const form = useForm({
        full_name: customer?.full_name || "",
        phone: customer?.phone || "",
        email: customer?.email || "",
        address: customer?.address || "",
        notes: customer?.notes || "",
    });
    return (
        <>
            <PageHeading
                title={customer ? "Επεξεργασία πελάτη" : "Νέος πελάτης"}
                description="Αποθηκεύστε τα στοιχεία και συνεχίστε στην καρτέλα πελάτη."
            />
            <Feedback errors={form.errors} />
            <Form
                form={form}
                onSubmit={() =>
                    customer
                        ? form.put(`/workshop/customers/${customer.id}`)
                        : form.post("/workshop/customers")
                }
            >
                <section className="ws-form-section">
                    <h2>Στοιχεία επικοινωνίας</h2>
                    <div className="ws-form-grid">
                        <Field
                            label="Ονοματεπώνυμο / επωνυμία"
                            name="full_name"
                            form={form}
                            autoComplete="name"
                        />
                        <Field
                            label="Τηλέφωνο"
                            name="phone"
                            form={form}
                            type="tel"
                            autoComplete="tel"
                        />
                        <Field
                            label="Email"
                            name="email"
                            form={form}
                            type="email"
                            autoComplete="email"
                        />
                        <Field
                            label="Διεύθυνση"
                            name="address"
                            form={form}
                            autoComplete="street-address"
                        />
                        <Field
                            label="Σημειώσεις"
                            name="notes"
                            form={form}
                            as="textarea"
                            wide
                        />
                    </div>
                </section>
                <FormFooter
                    form={form}
                    cancel={
                        customer
                            ? `/workshop/customers/${customer.id}`
                            : "/workshop/customers"
                    }
                    label={customer ? "Αποθήκευση πελάτη" : "Δημιουργία πελάτη"}
                />
            </Form>
        </>
    );
}
