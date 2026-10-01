import React from "react";
import {
    PageHeading,
    DataTable,
    Panel,
    SearchFilter,
    money,
} from "../../Components/ui";
export default function Index({ parts, q }) {
    return (
        <>
            <PageHeading
                title="Ανταλλακτικά"
                description="Διαθεσιμότητα και τιμές πώλησης. Η διαχείριση καταλόγου γίνεται στο Filament."
            />
            <SearchFilter url="/workshop/parts" q={q} />
            <Panel>
                <DataTable
                    data={parts}
                    columns={[
                        {
                            label: "Κωδικός",
                            render: (p) => (
                                <span className="ws-plate">{p.code}</span>
                            ),
                        },
                        {
                            label: "Ανταλλακτικό",
                            render: (p) => (
                                <>
                                    <strong>{p.name}</strong>
                                    <small>{p.description}</small>
                                </>
                            ),
                        },
                        {
                            label: "Απόθεμα",
                            numeric: true,
                            render: (p) => (
                                <span
                                    className={`ws-badge ${p.quantity <= 5 ? "ws-badge-warning" : ""}`}
                                >
                                    {p.quantity}
                                    {p.quantity <= 5 ? " · Χαμηλό" : ""}
                                </span>
                            ),
                        },
                        {
                            label: "Τιμή πώλησης",
                            numeric: true,
                            render: (p) => money(p.sale_price),
                        },
                    ]}
                />
            </Panel>
        </>
    );
}
