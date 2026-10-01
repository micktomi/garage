import React, { useState } from "react";
import { inputDate } from "../../Components/dates";
import { Link } from "@inertiajs/react";
import {
    PageHeading,
    DataTable,
    Panel,
    Plate,
    customerLink,
    date,
    Button,
} from "../../Components/ui";
function Contact({ vehicle }) {
    const [feedback, setFeedback] = useState("");
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
    return (
        <>
            <div className="ws-actions">
                {vehicle.customer?.phone && (
                    <>
                        <a href={`tel:${vehicle.customer.phone}`}>Κλήση</a>
                        <a
                            href={`sms:${vehicle.customer.phone}?body=${encodeURIComponent(message)}`}
                        >
                            Άνοιγμα SMS
                        </a>
                    </>
                )}
                <Button onClick={copy}>Αντιγραφή μηνύματος</Button>
            </div>
            {feedback && (
                <div role="status">
                    <small>{feedback}</small>
                    <details>
                        <summary>Κείμενο μηνύματος</summary>
                        {message}
                    </details>
                </div>
            )}
        </>
    );
}
export default function Index({ vehicles, today }) {
    const day =
        inputDate(today) ||
        new Date().toLocaleDateString("sv-SE", { timeZone: "Europe/Athens" });
    return (
        <>
            <PageHeading
                title="ΚΤΕΟ"
                description="Ληγμένα και επερχόμενα ΚΤΕΟ εντός 30 ημερών. Τα παλαιότερα εμφανίζονται πρώτα."
            />
            <Panel>
                <DataTable
                    data={vehicles}
                    columns={[
                        {
                            label: "Όχημα",
                            render: (v) => <Plate vehicle={v} />,
                        },
                        {
                            label: "Πελάτης",
                            render: (v) => customerLink(v.customer),
                        },
                        {
                            label: "Ημερομηνία λήξης",
                            render: (v) => date(v.kteo_expires_at),
                        },
                        {
                            label: "Κατάσταση",
                            render: (v) => (
                                <span
                                    className={`ws-badge ws-badge-${inputDate(v.kteo_expires_at) < day ? "danger" : "warning"}`}
                                >
                                    {inputDate(v.kteo_expires_at) < day
                                        ? "Ληγμένο"
                                        : "Προσεχής λήξη"}
                                </span>
                            ),
                        },
                        {
                            label: "Επικοινωνία",
                            render: (v) => <Contact vehicle={v} />,
                        },
                        {
                            label: "Ενέργεια",
                            render: (v) => (
                                <Link href={`/workshop/vehicles/${v.id}/edit`}>
                                    Ενημέρωση ΚΤΕΟ →
                                </Link>
                            ),
                        },
                    ]}
                />
            </Panel>
        </>
    );
}
