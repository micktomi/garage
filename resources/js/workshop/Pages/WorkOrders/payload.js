// Shared by the real form and its cross-boundary regression test.
export function workOrderPayload(data, canPrice) {
    return {
        ...data,
        parts: data.parts
            .filter((p) => p.source)
            .map((p) => {
                const result = {
                    source: p.source,
                    quantity: p.quantity,
                    unit_price: p.unit_price,
                    description: p.description,
                    note: p.note,
                };
                if (p.id) result.id = p.id;
                if (p.source === "from_stock") result.part_id = p.part_id;
                if (canPrice) result.unit_cost = p.unit_cost;
                return result;
            }),
    };
}
