import React from "react";
import { router, usePage } from "@inertiajs/react";
import {
    DataTable,
    PageHeading,
    Panel,
    SearchFilter,
    workOrderColumns,
} from "../../Components/ui";
export default function Index({ workOrders, q, status }) {
    const { workOrderStatuses } = usePage().props;
    return (
        <>
            <PageHeading
                title="Εντολές εργασίας"
                description="Η ουρά εργασιών και το ιστορικό του συνεργείου."
            />
            <SearchFilter url="/workshop/work-orders" q={q} params={{ status }}>
                <select
                    aria-label="Κατάσταση εντολών"
                    className="ws-input"
                    style={{ width: "auto" }}
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
            </SearchFilter>
            <Panel>
                <DataTable
                    data={workOrders}
                    columns={workOrderColumns}
                    caption="Εντολές εργασίας"
                />
            </Panel>
        </>
    );
}
