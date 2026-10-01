import React from "react";
import { Head } from "@inertiajs/react";
export default function Error({ status }) {
    const copy = {
        403: [
            "Δεν επιτρέπεται αυτή η ενέργεια",
            "Ο λογαριασμός σας δεν έχει δικαίωμα τροποποίησης αυτής της εγγραφής.",
        ],
        404: [
            "Η σελίδα δεν βρέθηκε",
            "Η εγγραφή μπορεί να έχει διαγραφεί ή ο σύνδεσμος να έχει αλλάξει.",
        ],
        500: [
            "Η ενέργεια δεν ολοκληρώθηκε",
            "Παρουσιάστηκε πρόβλημα στον server. Επιστρέψτε και δοκιμάστε ξανά.",
        ],
        503: [
            "Το συνεργείο είναι προσωρινά εκτός σύνδεσης",
            "Δοκιμάστε ξανά σε λίγο.",
        ],
    }[status] || [
        "Η ενέργεια δεν ολοκληρώθηκε",
        "Επιστρέψτε στο συνεργείο και δοκιμάστε ξανά.",
    ];
    return (
        <main className="ws-content">
            <Head title={copy[0]} />
            <section className="ws-panel ws-panel-body ws-form">
                <p className="ws-muted">Garage Manager · {status}</p>
                <h1>{copy[0]}</h1>
                <p>{copy[1]}</p>
                <a className="ws-btn ws-btn-primary" href="/workshop">
                    Επιστροφή στο συνεργείο
                </a>
            </section>
        </main>
    );
}
Error.layout = (page) => page;
