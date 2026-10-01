import React from "react";
import { router, Link, usePage } from "@inertiajs/react";
import {
    PageHeading,
    Button,
    DataTable,
    Panel,
    StatusBadge,
    Plate,
    customerLink,
    date,
    time,
} from "../../Components/ui";
export default function Index({ appointments, filters = {} }) {
    const { appointmentStatuses } = usePage().props;
    const update = (params) =>
        router.get("/workshop/appointments", { ...filters, ...params });
    return (
        <>
            <PageHeading
                title="Ραντεβού"
                description="Προγραμματισμός επισκέψεων και παρακολούθηση κατάστασης."
            >
                <Button href="/workshop/appointments/create" primary>
                    Νέο ραντεβού
                </Button>
            </PageHeading>
            <div className="ws-filter">
                <label className="ws-actions">
                    <input
                        type="checkbox"
                        checked={filters.history === "1"}
                        onChange={(e) =>
                            update({ history: e.target.checked ? "1" : "0" })
                        }
                    />
                    Συμπερίληψη ιστορικού
                </label>
                <select
                    className="ws-input"
                    style={{ width: "auto" }}
                    aria-label="Κατάσταση ραντεβού"
                    value={filters.status || ""}
                    onChange={(e) => update({ status: e.target.value })}
                >
                    <option value="">Όλες οι καταστάσεις</option>
                    {appointmentStatuses.map((s) => (
                        <option key={s.value} value={s.value}>
                            {s.label}
                        </option>
                    ))}
                </select>
            </div>
            <Panel>
                <DataTable
                    data={appointments}
                    columns={[
                        {
                            label: "Ημερομηνία / ώρα",
                            render: (a) => (
                                <>
                                    {date(a.appointment_date)}
                                    <small>{time(a.appointment_date)}</small>
                                </>
                            ),
                        },
                        {
                            label: "Πελάτης",
                            render: (a) => customerLink(a.customer),
                        },
                        {
                            label: "Όχημα",
                            render: (a) => <Plate vehicle={a.vehicle} />,
                        },
                        {
                            label: "Περιγραφή",
                            render: (a) => a.description || "—",
                        },
                        {
                            label: "Κατάσταση",
                            render: (a) => (
                                <StatusBadge status={a.status} appointment />
                            ),
                        },
                        {
                            label: "Ενέργεια",
                            render: (a) => (
                                <Link
                                    href={`/workshop/appointments/${a.id}/edit`}
                                >
                                    Επεξεργασία →
                                </Link>
                            ),
                        },
                    ]}
                    empty={{
                        title: "Δεν υπάρχουν προγραμματισμένα ραντεβού",
                        description:
                            "Ελέγξτε τα φίλτρα ή προσθέστε ένα νέο ραντεβού.",
                    }}
                />
            </Panel>
        </>
    );
}
