import React, { useState, useEffect } from "react";
import { Link, useForm, usePage } from "@inertiajs/react";
import {
    Button,
    ConfirmDialog,
    DataTable,
    Field,
    Form,
    Feedback,
    PageHeading,
    Panel,
    Plate,
    StatusBadge,
    customerLink,
    date,
    money,
} from "../../Components/ui";
export default function Show({ workOrder: order, canEdit }) {
    const { workOrderStatuses } = usePage().props;
    const [confirm, setConfirm] = useState(false);
    const form = useForm({
        status: order.status,
        lock_version: order.lock_version,
    });
    useEffect(() => {
        form.setData({
            status: order.status,
            lock_version: order.lock_version,
        });
    }, [order.lock_version, order.status]);
    const changeStatus = () =>
        form.patch(`/workshop/work-orders/${order.id}/status`, {
            onSuccess: () => setConfirm(false),
        });
    const details = [
        ["Πρόβλημα / αίτημα", order.problem_description],
        ["Διάγνωση", order.diagnosis],
        ["Εργασίες που εκτελέστηκαν", order.work_performed],
        ["Τρέχοντα χιλιόμετρα", order.current_mileage],
        ["Επόμενο service", date(order.next_service_date)],
        ["Service στα χιλιόμετρα", order.next_service_mileage],
    ];
    return (
        <>
            <PageHeading
                title={`Εντολή #${String(order.id).padStart(4, "0")}`}
                description={`Δημιουργήθηκε ${date(order.created_at)} · ${order.in_shop ? "Το όχημα βρίσκεται στο συνεργείο" : "Το όχημα έχει αποχωρήσει"}`}
            >
                <a
                    className="ws-btn"
                    href={`/work-orders/${order.id}/print`}
                    target="_blank"
                    rel="noreferrer"
                >
                    Εκτύπωση
                </a>
                {canEdit && (
                    <Button
                        href={`/workshop/work-orders/${order.id}/edit`}
                        primary
                    >
                        Επεξεργασία
                    </Button>
                )}
            </PageHeading>
            <Feedback errors={form.errors} />
            <div className="ws-columns">
                <div className="ws-stack">
                    <Panel
                        title="Στοιχεία εργασίας"
                        action={<StatusBadge status={order.status} />}
                        body
                    >
                        <dl className="ws-detail-list">
                            {details.map(([label, value]) => (
                                <div key={label}>
                                    <dt>{label}</dt>
                                    <dd>{value ?? "—"}</dd>
                                </div>
                            ))}
                        </dl>
                    </Panel>
                    <Panel title="Ανταλλακτικά και υλικά">
                        <DataTable
                            data={order.work_order_parts}
                            columns={[
                                {
                                    label: "Υλικό",
                                    render: (p) =>
                                        p.part?.name || p.description,
                                },
                                {
                                    label: "Προέλευση",
                                    render: (p) =>
                                        ({
                                            from_stock: "Από απόθεμα",
                                            customer_supplied: "Πελάτη",
                                            purchased_for_job:
                                                "Αγορά για εργασία",
                                        })[p.source],
                                },
                                {
                                    label: "Ποσότητα",
                                    numeric: true,
                                    render: (p) => p.quantity,
                                },
                                {
                                    label: "Τιμή",
                                    numeric: true,
                                    render: (p) => money(p.unit_price),
                                },
                                {
                                    label: "Σύνολο",
                                    numeric: true,
                                    render: (p) =>
                                        money(
                                            Number(p.quantity) *
                                                Number(p.unit_price),
                                        ),
                                },
                            ]}
                            empty={{
                                title: "Δεν υπάρχουν ανταλλακτικά",
                                description:
                                    "Τα εργατικά εμφανίζονται στο σύνολο.",
                            }}
                        />
                        <div className="ws-totals">
                            <div>
                                <small>Εργατικά</small>
                                <strong>{money(order.labor_cost)}</strong>
                            </div>
                            <div>
                                <small>Ανταλλακτικά</small>
                                <strong>{money(order.parts_cost)}</strong>
                            </div>
                            <div>
                                <small>Σύνολο</small>
                                <strong>{money(order.total_cost)}</strong>
                            </div>
                        </div>
                    </Panel>
                </div>
                <div className="ws-stack">
                    <Panel title="Πελάτης και όχημα" body>
                        <h2>{customerLink(order.customer)}</h2>
                        <p className="ws-muted">
                            {order.customer?.phone || "Χωρίς τηλέφωνο"}
                        </p>
                        <hr className="ws-divider" />
                        <Plate vehicle={order.vehicle} />
                        <p>
                            {order.vehicle?.make} {order.vehicle?.model}
                        </p>
                        <div className="ws-actions" style={{ marginTop: 18 }}>
                            {order.customer?.phone && (
                                <>
                                    <a
                                        className="ws-btn"
                                        href={`tel:${order.customer.phone}`}
                                    >
                                        Κλήση
                                    </a>
                                    <a
                                        className="ws-btn"
                                        href={`sms:${order.customer.phone}?body=${encodeURIComponent(`Το όχημά σας ${order.vehicle?.plate_number} είναι έτοιμο προς παραλαβή.`)}`}
                                    >
                                        Άνοιγμα SMS
                                    </a>
                                </>
                            )}
                        </div>
                        <hr className="ws-divider" />
                        <Link
                            href={`/workshop/vehicles/${order.vehicle_id}/edit`}
                        >
                            Ιστορικό οχήματος →
                        </Link>
                    </Panel>
                    <Panel title="Κατάσταση εντολής" body>
                        {canEdit ? (
                            <>
                                <Field
                                    label="Νέα κατάσταση"
                                    name="status"
                                    form={form}
                                    as="select"
                                >
                                    {workOrderStatuses.map((s) => (
                                        <option key={s.value} value={s.value}>
                                            {s.label}
                                        </option>
                                    ))}
                                </Field>
                                <Button
                                    busy={form.processing}
                                    disabled={form.data.status === order.status}
                                    onClick={() =>
                                        form.data.status === "cancelled"
                                            ? setConfirm(true)
                                            : changeStatus()
                                    }
                                    style={{ marginTop: 16 }}
                                >
                                    Ενημέρωση κατάστασης
                                </Button>
                            </>
                        ) : (
                            <p className="ws-muted">
                                Η εντολή είναι ολοκληρωμένη. Η τροποποίηση
                                επιτρέπεται στον ιδιοκτήτη.
                            </p>
                        )}
                    </Panel>
                </div>
            </div>
            <ConfirmDialog
                open={confirm}
                title={`Ακύρωση εντολής #${order.id}`}
                danger
                confirmLabel="Ακύρωση εντολής"
                busy={form.processing}
                onCancel={() => setConfirm(false)}
                onConfirm={changeStatus}
            >
                Τα ανταλλακτικά αποθέματος θα επιστραφούν. Η εντολή θα
                εμφανίζεται στο αρχείο.
            </ConfirmDialog>
        </>
    );
}
