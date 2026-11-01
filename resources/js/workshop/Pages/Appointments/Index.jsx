import React from "react";
import { router, usePage } from "@inertiajs/react";
import {
    Button,
    CardGrid,
    FilterBar,
    PageHeading,
} from "../../Components/ui";
import { AppointmentCard } from "../../Components/cards";
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
            <FilterBar url="/workshop/appointments" search={false}>
                <label className="ws-check">
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
                    className="ws-input ws-field-auto"
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
            </FilterBar>
            <CardGrid
                data={appointments}
                caption="Ραντεβού"
                unit={["ραντεβού", "ραντεβού"]}
                render={(appointment) => (
                    <AppointmentCard appointment={appointment} />
                )}
                empty={{
                    title: "Δεν υπάρχουν προγραμματισμένα ραντεβού",
                    description:
                        "Ελέγξτε τα φίλτρα ή προσθέστε ένα νέο ραντεβού.",
                }}
            />
        </>
    );
}
