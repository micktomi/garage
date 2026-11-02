import React, { useState, useEffect } from "react";
import { Link, useForm, usePage } from "@inertiajs/react";
import {
    Button,
    CardPerson,
    ConfirmDialog,
    DataTable,
    DetailList,
    Feedback,
    Field,
    Icon,
    PageHeading,
    Panel,
    Plate,
    RecordCard,
    StatusBadge,
    PhoneLink,
    date,
    money,
    padId,
    vehicleName,
} from "../../Components/ui";
export default function Show({ workOrder: order, canEdit, allowedTransitions = [] }) {
    const { workOrderStatuses } = usePage().props;
    const targets = new Set([order.status, ...allowedTransitions]);
    const options = (workOrderStatuses || []).filter((s) => targets.has(s.value));
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
                title={`Εντολή ${padId(order.id)}`}
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
                        <DetailList items={details} />
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
                    <section>
                        <div className="ws-section-head">
                            <h2>Πελάτης και όχημα</h2>
                        </div>
                        <RecordCard
                            href={`/workshop/vehicles/${order.vehicle_id}`}
                            label={`Ιστορικό οχήματος ${order.vehicle?.plate_number || ""}`}
                            plate={
                                order.vehicle && (
                                    <Plate vehicle={order.vehicle} link={false} />
                                )
                            }
                            title={vehicleName(order.vehicle) || "Όχημα"}
                            subtitle={order.vehicle?.year}
                            meta={
                                !order.customer?.phone && (
                                    <span>Χωρίς τηλέφωνο</span>
                                )
                            }
                            actions={
                                <>
                                    <PhoneLink customer={order.customer} />
                                    {order.customer?.phone && (
                                        <a
                                            className="ws-icon-btn"
                                            href={`sms:${order.customer.phone}?body=${encodeURIComponent(`Το όχημά σας ${order.vehicle?.plate_number} είναι έτοιμο προς παραλαβή.`)}`}
                                            aria-label="Άνοιγμα SMS"
                                        >
                                            <Icon name="sms" size={16} />
                                        </a>
                                    )}
                                    <Button
                                        href={`/workshop/vehicles/${order.vehicle_id}`}
                                        className="ws-btn-sm"
                                    >
                                        Ιστορικό οχήματος
                                    </Button>
                                </>
                            }
                        >
                            <CardPerson customer={order.customer} />
                        </RecordCard>
                    </section>
                    <Panel title="Κατάσταση εντολής" body>
                        {canEdit ? (
                            <>
                                <Field
                                    label="Νέα κατάσταση"
                                    name="status"
                                    form={form}
                                    as="select"
                                >
                                    {options.map((s) => (
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
                                    className="ws-mt-4"
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
                title={`Ακύρωση εντολής ${padId(order.id)}`}
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
