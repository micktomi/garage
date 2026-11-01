import React, { useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import { timeOfDay } from "./dates";
import {
    Age,
    Badge,
    Button,
    CardFacts,
    CardPerson,
    EmptyState,
    Icon,
    Panel,
    PhoneLink,
    Plate,
    RecordCard,
    StatusBadge,
    date,
    kteoStatus,
    money,
    orderAge,
    padId,
    rows,
    statusTone,
    vehicleName,
} from "./ui";

/* Entity cards. Each one only decides which facts to show inside the shared RecordCard. */
export function WorkOrderCard({ order, facts = false }) {
    const { workOrderStatuses } = usePage().props;
    const open = workOrderStatuses?.find((s) => s.value === order.status)?.open;
    const days = open ? orderAge(order) : null;
    return (
        <RecordCard
            href={`/workshop/work-orders/${order.id}`}
            label={`Εντολή ${padId(order.id)}, ${order.vehicle?.plate_number || ""}`}
            plate={order.vehicle && <Plate vehicle={order.vehicle} />}
            title={vehicleName(order.vehicle) || `Εντολή ${padId(order.id)}`}
            subtitle={order.vehicle?.year}
            badge={<StatusBadge status={order.status} />}
            meta={
                <>
                    {order.vehicle && (
                        <span className="ws-card-id">{padId(order.id)}</span>
                    )}
                    <span>{date(order.created_at)}</span>
                    <Age days={days} />
                </>
            }
            actions={
                <>
                    <strong className="ws-card-total">
                        {Number(order.total_cost) ? money(order.total_cost) : "—"}
                    </strong>
                    <PhoneLink customer={order.customer} />
                    <Button
                        href={`/workshop/work-orders/${order.id}`}
                        className="ws-btn-sm"
                    >
                        Άνοιγμα
                    </Button>
                </>
            }
        >
            {order.problem_description && (
                <p className="ws-card-lead">{order.problem_description}</p>
            )}
            {facts && (
                <CardFacts
                    items={[
                        ["Χιλιόμετρα", order.current_mileage],
                        [
                            "Επόμενο service",
                            order.next_service_date &&
                                [
                                    date(order.next_service_date),
                                    order.next_service_mileage &&
                                        `${order.next_service_mileage} χλμ`,
                                ]
                                    .filter(Boolean)
                                    .join(" · "),
                        ],
                    ]}
                />
            )}
            {order.customer && <CardPerson customer={order.customer} />}
        </RecordCard>
    );
}

/* intent="open": the vehicle sheet is the main action (vehicle lists).
   intent="order": starting a work order is the main action (search results, a customer's vehicles). */
export function VehicleCard({
    vehicle,
    intent = "open",
    showCustomer = true,
    openOrder,
}) {
    const kteo = kteoStatus(vehicle, { quiet: true });
    const mileage = vehicle.mileage
        ? `${new Intl.NumberFormat("el-GR").format(vehicle.mileage)} χλμ`
        : null;
    const engine = [
        vehicle.fuel,
        vehicle.engine_cc ? `${vehicle.engine_cc} cc` : null,
    ]
        .filter(Boolean)
        .join(" · ");
    const sheet = `/workshop/vehicles/${vehicle.id}/edit`;
    return (
        <RecordCard
            href={sheet}
            label={`Καρτέλα οχήματος ${vehicle.plate_number}`}
            plate={<Plate vehicle={vehicle} link={false} />}
            title={vehicleName(vehicle) || vehicle.plate_number}
            subtitle={vehicle.year}
            badge={kteo && <Badge tone={kteo.tone}>ΚΤΕΟ · {kteo.label}</Badge>}
            meta={
                <>
                    {openOrder && (
                        <Link href={`/workshop/work-orders/${openOrder.id}`}>
                            Ανοικτή εντολή {padId(openOrder.id)}
                        </Link>
                    )}
                    {vehicle.vin && (
                        <span className="ws-card-id">VIN {vehicle.vin}</span>
                    )}
                    {!openOrder && (
                        <span>Καταχωρίστηκε {date(vehicle.created_at)}</span>
                    )}
                </>
            }
            actions={
                <>
                    {showCustomer && <PhoneLink customer={vehicle.customer} />}
                    {intent === "order" ? (
                        <Button
                            href={`/workshop/work-orders/create?customer_id=${vehicle.customer_id}&vehicle_id=${vehicle.id}`}
                            className="ws-btn-sm"
                        >
                            Νέα εντολή
                        </Button>
                    ) : (
                        <Button href={sheet} className="ws-btn-sm">
                            Καρτέλα οχήματος
                        </Button>
                    )}
                </>
            }
        >
            <CardFacts
                items={[
                    ["Χιλιόμετρα", mileage],
                    [
                        "ΚΤΕΟ έως",
                        vehicle.kteo_expires_at && date(vehicle.kteo_expires_at),
                    ],
                    ["Κινητήρας", engine],
                ]}
            />
            {showCustomer && vehicle.customer && (
                <CardPerson customer={vehicle.customer} />
            )}
        </RecordCard>
    );
}

export function CustomerCard({ customer }) {
    const vehicles = customer.vehicles || [];
    return (
        <RecordCard
            href={`/workshop/customers/${customer.id}`}
            label={`Καρτέλα πελάτη ${customer.full_name}`}
            title={customer.full_name}
            subtitle={customer.email}
            meta={
                customer.created_at && (
                    <span>Πελάτης από {date(customer.created_at)}</span>
                )
            }
            actions={
                <>
                    <PhoneLink customer={customer} />
                    <Button
                        href={`/workshop/customers/${customer.id}`}
                        className="ws-btn-sm"
                    >
                        Καρτέλα πελάτη
                    </Button>
                </>
            }
        >
            <CardFacts
                items={[
                    ["Τηλέφωνο", customer.phone],
                    [
                        "Οχήματα",
                        customer.vehicles_count !== undefined &&
                            String(customer.vehicles_count),
                    ],
                ]}
            />
            {vehicles.length > 0 && (
                <div className="ws-card-plates">
                    {vehicles.slice(0, 4).map((v) => (
                        <Plate key={v.id} vehicle={v} link={false} />
                    ))}
                </div>
            )}
        </RecordCard>
    );
}

export function AppointmentCard({ appointment }) {
    const vehicle = appointment.vehicle;
    return (
        <RecordCard
            href={`/workshop/appointments/${appointment.id}/edit`}
            label={`Ραντεβού ${date(appointment.appointment_date)} ${timeOfDay(appointment.appointment_date)}`}
            plate={vehicle && <Plate vehicle={vehicle} link={false} />}
            title={vehicleName(vehicle) || date(appointment.appointment_date)}
            subtitle={vehicle?.year}
            badge={<StatusBadge status={appointment.status} appointment />}
            meta={
                <>
                    {vehicle && <span>{date(appointment.appointment_date)}</span>}
                    <span className="ws-card-id">
                        {timeOfDay(appointment.appointment_date)}
                    </span>
                </>
            }
            actions={
                <>
                    <PhoneLink customer={appointment.customer} />
                    <Button
                        href={`/workshop/appointments/${appointment.id}/edit`}
                        className="ws-btn-sm"
                    >
                        Επεξεργασία
                    </Button>
                </>
            }
        >
            {appointment.description && (
                <p className="ws-card-lead">{appointment.description}</p>
            )}
            {appointment.customer && <CardPerson customer={appointment.customer} />}
        </RecordCard>
    );
}

/* KTEO reminder: the card carries the contact tools (call, SMS, copy the message). */
export function KteoCard({ vehicle }) {
    const [feedback, setFeedback] = useState("");
    const status = kteoStatus(vehicle);
    const message = `Υπενθύμιση: το ΚΤΕΟ του οχήματός σας ${vehicle.plate_number} λήγει στις ${date(vehicle.kteo_expires_at)}.`;
    const copy = async () => {
        try {
            await navigator.clipboard.writeText(message);
            setFeedback("Το μήνυμα αντιγράφηκε.");
        } catch {
            setFeedback(
                "Η αντιγραφή δεν είναι διαθέσιμη. Επιλέξτε το παρακάτω κείμενο.",
            );
        }
    };
    const phone = vehicle.customer?.phone;
    return (
        <RecordCard
            href={`/workshop/vehicles/${vehicle.id}/edit`}
            label={`Ενημέρωση ΚΤΕΟ ${vehicle.plate_number}`}
            plate={<Plate vehicle={vehicle} link={false} />}
            title={vehicleName(vehicle) || vehicle.plate_number}
            subtitle={vehicle.year}
            badge={status && <Badge tone={status.tone}>{status.label}</Badge>}
            meta={
                <>
                    <PhoneLink customer={vehicle.customer} />
                    {phone && (
                        <a
                            className="ws-icon-btn"
                            href={`sms:${phone}?body=${encodeURIComponent(message)}`}
                            aria-label={`Άνοιγμα SMS προς ${vehicle.customer.full_name}`}
                        >
                            <Icon name="sms" size={16} />
                        </a>
                    )}
                    <button
                        type="button"
                        className="ws-icon-btn"
                        onClick={copy}
                        aria-label="Αντιγραφή μηνύματος"
                    >
                        <Icon name="copy" size={16} />
                    </button>
                </>
            }
            actions={
                <Button
                    href={`/workshop/vehicles/${vehicle.id}/edit`}
                    className="ws-btn-sm"
                >
                    Ενημέρωση ΚΤΕΟ
                </Button>
            }
        >
            <CardFacts
                items={[["ΚΤΕΟ έως", date(vehicle.kteo_expires_at)]]}
            />
            <CardPerson customer={vehicle.customer} />
            {feedback && (
                <div role="status" className="ws-card-note">
                    <span>{feedback}</span>
                    <details>
                        <summary>Κείμενο μηνύματος</summary>
                        {message}
                    </details>
                </div>
            )}
        </RecordCard>
    );
}

/* One vehicle on the status board. The title link covers the card; the call link sits above it. */
export function WorkOrderTicket({ order, showStatus = false }) {
    const here = order.in_shop !== false;
    return (
        <article className="ws-ticket">
            <div className="ws-ticket-top">
                <Plate vehicle={order.vehicle} link={false} />
                {showStatus && <StatusBadge status={order.status} />}
                <Age days={orderAge(order)} />
            </div>
            <h3 className="ws-ticket-car">
                <Link
                    href={`/workshop/work-orders/${order.id}`}
                    aria-label={`Εντολή ${padId(order.id)}, ${order.vehicle?.plate_number || ""}`}
                >
                    {vehicleName(order.vehicle) ||
                        `Εντολή ${padId(order.id)}`}
                </Link>
            </h3>
            {order.problem_description && (
                <p className="ws-ticket-job">{order.problem_description}</p>
            )}
            {!here && (
                <span className="ws-ticket-flag">Το όχημα έχει αποχωρήσει</span>
            )}
            {order.status === "ready" && order.customer?.phone && (
                <a
                    className="ws-ticket-call"
                    href={`tel:${order.customer.phone}`}
                >
                    <Icon name="phone" size={14} />
                    Κλήση {order.customer.phone}
                </a>
            )}
            <div className="ws-ticket-foot">
                <span className="ws-ticket-cust">
                    {order.customer?.full_name || "—"}
                </span>
                <span className="ws-ticket-total">
                    {Number(order.total_cost) ? money(order.total_cost) : "—"}
                </span>
            </div>
        </article>
    );
}

/* Open orders laid out by status. Wide screens show one lane per status;
   narrow screens show the same orders as status chips plus a filtered list. */
export function StatusBoard({ orders, counts = {}, empty }) {
    const { workOrderStatuses } = usePage().props;
    const lanes = (workOrderStatuses || []).filter((s) => s.open);
    const [filter, setFilter] = useState("all");
    const all = rows(orders);
    const countOf = (value) =>
        counts[value] ?? all.filter((o) => o.status === value).length;
    const total = lanes.reduce((sum, lane) => sum + Number(countOf(lane.value)), 0);
    const visible = all.filter((o) => filter === "all" || o.status === filter);
    if (!all.length) return <Panel><EmptyState {...empty} /></Panel>;
    return (
        <>
            <div className="ws-board">
                {lanes.map((lane) => {
                    const items = all.filter((o) => o.status === lane.value);
                    const tone = statusTone(lane.value, lane.tone);
                    return (
                        <section
                            key={lane.value}
                            className={`ws-lane ws-tone-${tone}`}
                            aria-label={lane.label}
                        >
                            <div className="ws-lane-head">
                                <span className="dot" aria-hidden="true" />
                                {lane.label}
                                <span className="n">{countOf(lane.value)}</span>
                            </div>
                            {items.length ? (
                                items.map((o) => (
                                    <WorkOrderTicket key={o.id} order={o} />
                                ))
                            ) : (
                                <div className="ws-lane-empty">
                                    Δεν υπάρχει εντολή σε αυτή τη φάση.
                                </div>
                            )}
                        </section>
                    );
                })}
            </div>
            <div className="ws-chips" role="group" aria-label="Φίλτρο κατάστασης">
                <button
                    type="button"
                    className="ws-chip"
                    aria-pressed={filter === "all"}
                    onClick={() => setFilter("all")}
                >
                    Όλα <span className="n">{total}</span>
                </button>
                {lanes.map((lane) => (
                    <button
                        key={lane.value}
                        type="button"
                        className="ws-chip"
                        aria-pressed={filter === lane.value}
                        onClick={() => setFilter(lane.value)}
                    >
                        {chipLabel[lane.value] || lane.label}
                        <span className="n">{countOf(lane.value)}</span>
                    </button>
                ))}
            </div>
            <div className="ws-board-list" aria-live="polite">
                {visible.length ? (
                    visible.map((o) => (
                        <WorkOrderTicket key={o.id} order={o} showStatus />
                    ))
                ) : (
                    <EmptyState
                        title="Καμία εντολή σε αυτή τη φάση"
                        description="Επιλέξτε «Όλα» για να δείτε όλες τις ανοικτές εντολές."
                    />
                )}
            </div>
        </>
    );
}
const chipLabel = { awaiting_parts: "Αναμονή", ready: "Έτοιμα" };
