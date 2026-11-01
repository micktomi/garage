import React from "react";
import { router, usePage } from "@inertiajs/react";
import {
    CardGrid,
    PageHeading,
    FilterBar,
} from "../../Components/ui";
import { WorkOrderCard } from "../../Components/cards";
export default function Index({ workOrders, q, status }) {
    const { workOrderStatuses } = usePage().props;
    return (
        <>
            <PageHeading
                title="Εντολές εργασίας"
                description="Η ουρά εργασιών και το ιστορικό του συνεργείου."
            />
            <FilterBar url="/workshop/work-orders" q={q} params={{ status }}>
                <select
                    aria-label="Κατάσταση εντολών"
                    className="ws-input ws-field-auto"
                    value={status}
                    onChange={(e) =>
                        router.get("/workshop/work-orders", {
                            q,
                            status: e.target.value,
                        })
                    }
                >
                    <option value="open">Ανοικτές εντολές</option>
                    <option value="archive">Αρχείο</option>
                    <option value="all">Όλες οι εντολές</option>
                    {workOrderStatuses.map((s) => (
                        <option key={s.value} value={s.value}>
                            {s.label}
                        </option>
                    ))}
                </select>
            </FilterBar>
            <CardGrid
                data={workOrders}
                caption="Εντολές εργασίας"
                unit={["εντολή", "εντολές"]}
                render={(order) => <WorkOrderCard order={order} />}
                empty={{
                    title: "Δεν βρέθηκαν εντολές",
                    description:
                        "Αλλάξτε την αναζήτηση ή το φίλτρο κατάστασης.",
                }}
            />
        </>
    );
}
