import React, { useEffect, useRef, useState } from "react";
import { Link, useForm } from "@inertiajs/react";
import {
    Button,
    ConfirmDialog,
    Field,
    Feedback,
    Form,
    PageHeading,
    Panel,
} from "../../Components/ui";
export default function Index({ messages, proposal, ambiguity, enabled }) {
    const form = useForm({ message: "", action: "send" });
    const [confirm, setConfirm] = useState(false);
    const [listening, setListening] = useState(false);
    const [speechError, setSpeechError] = useState("");
    const speech = useRef(null);
    const last = useRef(null);
    useEffect(() => {
        last.current?.scrollIntoView({ block: "nearest" });
    }, [messages.length]);
    useEffect(() => () => speech.current?.abort(), []);
    const submit = (action) => {
        form.transform((data) => ({ ...data, action }));
        form.post("/workshop/assistant", {
            preserveScroll: true,
            onSuccess: () => {
                if (action === "send") form.reset("message");
                setConfirm(false);
            },
        });
    };
    const dictate = () => {
        const Recognition =
            window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!Recognition) {
            setSpeechError(
                "Η υπαγόρευση δεν υποστηρίζεται από αυτόν τον browser. Γράψτε το αίτημά σας.",
            );
            return;
        }
        if (listening) {
            speech.current?.stop();
            return;
        }
        const recognition = new Recognition();
        speech.current = recognition;
        recognition.lang = "el-GR";
        recognition.interimResults = false;
        recognition.onresult = (e) =>
            form.setData(
                "message",
                `${form.data.message} ${e.results[0][0].transcript}`.trim(),
            );
        recognition.onend = () => setListening(false);
        recognition.onerror = () => {
            setSpeechError(
                "Η υπαγόρευση δεν ολοκληρώθηκε. Ελέγξτε το μικρόφωνο ή γράψτε το αίτημά σας.",
            );
            setListening(false);
        };
        setSpeechError("");
        setListening(true);
        recognition.start();
    };
    return (
        <>
            <PageHeading
                title="AI Βοηθός"
                description="Αναζήτηση, ιστορικό και προετοιμασία εγγραφών με δική σας επιβεβαίωση."
            >
                <Button onClick={() => submit("clear")} busy={form.processing}>
                    Νέα συνομιλία
                </Button>
            </PageHeading>
            <div className="ws-chat">
                {!enabled && (
                    <div
                        className="ws-feedback ws-feedback-error"
                        role="status"
                    >
                        Ο βοηθός δεν είναι διαθέσιμος. Επικοινωνήστε με τον
                        διαχειριστή για ενεργοποίηση.
                    </div>
                )}
                {!messages.length && (
                    <Panel title="Πώς μπορώ να βοηθήσω;" body>
                        <p>
                            Αναζητήστε πελάτη ή πινακίδα, ζητήστε ιστορικό και
                            ανοικτές εντολές ή προετοιμάστε νέο ραντεβού.
                        </p>
                        <p className="ws-muted">
                            Παράδειγμα: «Δείξε το ιστορικό του οχήματος
                            ΑΒΓ-1234».
                        </p>
                        <p className="ws-muted">
                            Καμία νέα εγγραφή δεν δημιουργείται πριν την
                            επιβεβαίωσή σας.
                        </p>
                    </Panel>
                )}
                {messages.map((m, i) => (
                    <article
                        className={`ws-message ${m.role === "user" ? "ws-message-user" : ""}`}
                        key={i}
                    >
                        <small>
                            {m.role === "user" ? "Εσείς" : "AI Βοηθός"}
                        </small>
                        <p>{m.message}</p>
                        {m.data?.created && (
                            <Link
                                href={
                                    m.data.created.type === "work_order"
                                        ? `/workshop/work-orders/${m.data.created.id}`
                                        : `/workshop/appointments/${m.data.created.id}/edit`
                                }
                            >
                                Άνοιγμα εγγραφής →
                            </Link>
                        )}
                    </article>
                ))}
                <div ref={last} />
                {ambiguity?.options && (
                    <Panel title="Επιλέξτε τη σωστή εγγραφή" body>
                        <div className="ws-actions">
                            {ambiguity.options.map((option, index) => (
                                <Button
                                    key={index}
                                    onClick={() =>
                                        form.setData(
                                            "message",
                                            option.selection_prompt,
                                        )
                                    }
                                >
                                    {option.label}
                                </Button>
                            ))}
                        </div>
                        <p className="ws-muted">
                            Η επιλογή συμπληρώνει το μήνυμα. Ελέγξτε το και
                            πατήστε αποστολή.
                        </p>
                    </Panel>
                )}
                {proposal && (
                    <Panel title={proposal.title} body>
                        <dl className="ws-detail-list">
                            {Object.entries(proposal.display).map(
                                ([label, value]) => (
                                    <div key={label}>
                                        <dt>{label}</dt>
                                        <dd>{String(value)}</dd>
                                    </div>
                                ),
                            )}
                        </dl>
                        <div className="ws-actions" style={{ marginTop: 20 }}>
                            <Button
                                primary
                                onClick={() => setConfirm(true)}
                                busy={form.processing}
                            >
                                Επιβεβαίωση δημιουργίας
                            </Button>
                            <Button
                                onClick={() => submit("cancel")}
                                busy={form.processing}
                            >
                                Ακύρωση πρότασης
                            </Button>
                        </div>
                    </Panel>
                )}
                <div className="ws-chat-composer">
                    <Feedback errors={form.errors} />
                    <Form form={form} onSubmit={() => submit("send")}>
                        <section className="ws-form-section">
                            <Field
                                label="Το αίτημά σας"
                                name="message"
                                form={form}
                                as="textarea"
                                placeholder="Γράψτε τι θέλετε να βρείτε ή να προετοιμάσετε…"
                            />
                            <p className="ws-muted" style={{ marginTop: 10 }}>
                                Η υπαγόρευση συμπληρώνει το κείμενο για έλεγχο
                                πριν την αποστολή.
                            </p>
                            {speechError && <p role="status">{speechError}</p>}
                        </section>
                        <div className="ws-form-footer">
                            <Button
                                onClick={dictate}
                                disabled={form.processing}
                                aria-pressed={listening}
                            >
                                {listening
                                    ? "Διακοπή υπαγόρευσης"
                                    : "Υπαγόρευση"}
                            </Button>
                            <Button
                                type="submit"
                                primary
                                busy={form.processing}
                                disabled={!enabled || !form.data.message.trim()}
                            >
                                {form.processing ? "Επεξεργασία…" : "Αποστολή"}
                            </Button>
                        </div>
                    </Form>
                </div>
            </div>
            <ConfirmDialog
                open={confirm}
                title="Δημιουργία εγγραφής"
                confirmLabel="Δημιουργία"
                busy={form.processing}
                onCancel={() => setConfirm(false)}
                onConfirm={() => submit("confirm")}
            >
                Θα δημιουργηθεί η εγγραφή με τα στοιχεία της πρότασης που
                ελέγξατε.
            </ConfirmDialog>
        </>
    );
}
