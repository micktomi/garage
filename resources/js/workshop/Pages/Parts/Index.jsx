import React from "react";
import {
    Badge,
    DataTable,
    FilterBar,
    ListCount,
    PageHeading,
    Panel,
    money,
} from "../../Components/ui";
const LOW_STOCK = 5;
export default function Index({ parts, q }) {
    return (
        <>
            <PageHeading
                title="Ανταλλακτικά"
                description="Διαθεσιμότητα και τιμές πώλησης. Η διαχείριση καταλόγου γίνεται στο Filament."
            />
            <FilterBar url="/workshop/parts" q={q} />
            <ListCount data={parts} unit={["ανταλλακτικό", "ανταλλακτικά"]} />
            <Panel>
                <DataTable
                    data={parts}
                    caption="Ανταλλακτικά"
                    columns={[
                        {
                            label: "Κωδικός",
                            render: (p) => <Badge mono>{p.code}</Badge>,
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
                            render: (p) =>
                                p.quantity <= LOW_STOCK ? (
                                    <Badge tone="warning">
                                        {p.quantity} · Χαμηλό απόθεμα
                                    </Badge>
                                ) : (
                                    p.quantity
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
