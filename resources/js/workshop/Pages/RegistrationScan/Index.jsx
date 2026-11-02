import React, { useEffect, useRef, useState } from "react";
import { useForm, usePage } from "@inertiajs/react";
import {
    Button,
    PageHeading,
    Form,
    Field,
    Feedback,
    FormFooter,
    Panel,
} from "../../Components/ui";
import MakeModelFields from "../../Components/MakeModelFields";
import { prepareImage } from "./prepareImage";
const blankReview = {
    owner_full_name: "",
    afm: "",
    phone: "",
    address: "",
    plate_number: "",
    vin: "",
    make: "",
    model: "",
    fuel: "",
    engine_cc: "",
    first_registered_at: "",
    confirm_near_vin: false,
    review_ready: "1",
};
export default function Index({
    extracted,
    modelsByMake,
    uploadMaxBytes = 2 * 1024 * 1024,
}) {
    return (
        <>
            <PageHeading
                title="Σάρωση άδειας κυκλοφορίας"
                description="Φωτογραφία άδειας → αναγνώριση → ανθρώπινος έλεγχος → καταχώριση."
            />
            {extracted ? (
                <Review
                    key={JSON.stringify(extracted)}
                    extracted={extracted}
                    modelsByMake={modelsByMake}
                />
            ) : (
                <Upload uploadMaxBytes={uploadMaxBytes} />
            )}
        </>
    );
}
function Upload({ uploadMaxBytes }) {
    const form = useForm({
        registration_image: null,
        browser_preprocess_ms: null,
    });
    const [preparing, setPreparing] = useState(false);
    const [rotation, setRotation] = useState(0);
    const original = useRef(null);
    const [preview, setPreview] = useState(null);
    useEffect(() => {
        if (!form.data.registration_image) {
            setPreview(null);
            return;
        }
        const url = URL.createObjectURL(form.data.registration_image);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [form.data.registration_image]);
    const camera = useRef(null);
    const gallery = useRef(null);
    const prepare = async (file, quarterTurns) => {
        form.clearErrors();
        form.setData({ registration_image: null, browser_preprocess_ms: null });
        if (file.size > 10 * 1024 * 1024) {
            form.setError(
                "registration_image",
                "Το αρχείο δεν μπορεί να ξεπερνά τα 10 MB.",
            );
            return;
        }
        const start = performance.now();
        setPreparing(true);
        try {
            const maxBytes = Math.floor(uploadMaxBytes * 0.9);
            let processed;
            try {
                processed = await prepareImage(file, { maxBytes, quarterTurns });
            } catch (error) {
                if (error.message === "size") throw error;
                if (quarterTurns) throw new Error("rotate");
                // Some browsers cannot use canvas, but can still upload a small,
                // supported original. Never send an oversize or undecodable HEIC.
                if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
                    throw new Error("format");
                }
                if (file.size > maxBytes) throw new Error("size");
                processed = file;
            }
            form.setData({
                registration_image: processed,
                browser_preprocess_ms: Math.round(performance.now() - start),
            });
            setRotation(quarterTurns);
        } catch (error) {
            form.setError(
                "registration_image",
                error.message === "size"
                    ? "Η εικόνα παραμένει πολύ μεγάλη για τον διακομιστή. Δοκιμάστε μικρότερη εικόνα ή ζητήστε αύξηση του ορίου μεταφόρτωσης."
                    : error.message === "rotate"
                      ? "Η περιστροφή δεν ολοκληρώθηκε. Επιλέξτε ξανά την εικόνα ή περιστρέψτε την στην εφαρμογή φωτογραφιών."
                      : "Η εικόνα δεν μπορεί να διαβαστεί σε αυτόν τον browser. Επιλέξτε αρχείο JPEG, PNG ή WebP.",
            );
        } finally {
            setPreparing(false);
        }
    };
    const select = async (e) => {
        const file = e.target.files?.[0];
        e.target.value = "";
        if (!file) return;
        original.current = file;
        await prepare(file, 0);
    };
    return (
        <>
            <Feedback errors={form.errors} />
            <Form
                className="ws-tool"
                form={form}
                onSubmit={() =>
                    form.post("/workshop/registration-scan/extract", {
                        forceFormData: true,
                    })
                }
            >
                <section className="ws-form-section">
                    <h2>Φωτογραφία εγγράφου</h2>
                    <div className="ws-upload">
                        <strong id="registration-upload-label">
                            Επιλέξτε ή φωτογραφίστε την άδεια
                        </strong>
                        <p id="registration-upload-hint">
                            Καθαρή εικόνα, χωρίς αντανακλάσεις, με όλα τα
                            τμήματα στο κάδρο. JPEG, PNG ή WebP έως 10 MB. Η εικόνα
                            προσαρμόζεται αυτόματα πριν από τη μεταφόρτωση.
                        </p>
                        <div
                            className="ws-actions"
                            role="group"
                            aria-labelledby="registration-upload-label"
                        >
                            <Button
                                onClick={() => camera.current?.click()}
                                disabled={preparing || form.processing}
                                aria-describedby="registration-upload-hint registration-upload-error"
                            >
                                Λήψη φωτογραφίας
                            </Button>
                            <Button
                                onClick={() => gallery.current?.click()}
                                disabled={preparing || form.processing}
                                aria-describedby="registration-upload-hint registration-upload-error"
                            >
                                Επιλογή αρχείου
                            </Button>
                        </div>
                        <input
                            ref={camera}
                            id="registration_camera"
                            name="registration_image"
                            type="file"
                            accept="image/*"
                            capture="environment"
                            hidden
                            onChange={select}
                            disabled={preparing || form.processing}
                            aria-label="Λήψη φωτογραφίας άδειας"
                        />
                        <input
                            ref={gallery}
                            id="registration_image"
                            name="registration_image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            hidden
                            onChange={select}
                            disabled={preparing || form.processing}
                            aria-label="Επιλογή εικόνας άδειας από αρχεία"
                        />
                        <p
                            id="registration-upload-error"
                            className="ws-field-error"
                            role="alert"
                        >
                            {form.errors.registration_image}
                        </p>
                        {form.data.registration_image && (
                            <p>{form.data.registration_image.name}</p>
                        )}
                        {preview && (
                            <figure className="ws-scan-preview">
                                <a
                                    href={preview}
                                    target="_blank"
                                    rel="noreferrer"
                                    aria-label="Άνοιγμα φωτογραφίας σε πλήρες μέγεθος"
                                >
                                    <img
                                        src={preview}
                                        alt="Η φωτογραφία άδειας που θα σταλεί για αναγνώριση"
                                    />
                                </a>
                                <figcaption>
                                    Πατήστε τη φωτογραφία για πλήρες μέγεθος.
                                    Γυρίστε τη ώστε τα γράμματα να διαβάζονται οριζόντια.
                                    Ελέγξτε ότι φαίνονται η πινακίδα (A), το VIN (E) και τα μικρά
                                    γράμματα σε όλα τα φύλλα της άδειας. Αν είναι θολά, τραβήξτε ξανά από πιο
                                    κοντά, χωρίς ψηφιακό zoom ή αντανακλάσεις.
                                </figcaption>
                                <div className="ws-actions">
                                    <Button
                                        className="ws-btn-sm"
                                        disabled={preparing || form.processing}
                                        onClick={() => prepare(original.current, (rotation + 3) % 4)}
                                    >
                                        Περιστροφή 90° αριστερά
                                    </Button>
                                </div>
                            </figure>
                        )}
                        {preparing && (
                            <p role="status">Προετοιμασία εικόνας…</p>
                        )}
                        {form.processing && (
                            <p role="status">
                                {form.progress && form.progress.percentage < 100
                                    ? `Μεταφόρτωση ${form.progress.percentage}%`
                                    : "Γίνεται αναγνώριση στοιχείων…"}
                            </p>
                        )}
                    </div>
                    <p className="ws-muted ws-mt-4">
                        Η εικόνα αποστέλλεται στο Gemini για αναγνώριση και δεν
                        αποθηκεύεται στην εφαρμογή. Ελέγχετε κάθε
                        αναγνωρισμένο στοιχείο πριν την καταχώριση.
                    </p>
                </section>
                <div className="ws-form-footer">
                    <Button href="/workshop/vehicles">Ακύρωση</Button>
                    <Button
                        type="submit"
                        primary
                        busy={form.processing || preparing}
                        disabled={!form.data.registration_image}
                    >
                        Αναγνώριση στοιχείων
                    </Button>
                </div>
            </Form>
        </>
    );
}
function Review({ extracted, modelsByMake }) {
    const { errors } = usePage().props;
    const form = useForm(
        Object.fromEntries(
            Object.entries({ ...blankReview, ...extracted }).map(
                ([key, value]) => [key, value ?? ""],
            ),
        ),
    );
    return (
        <>
            <Feedback errors={form.errors} />
            <Form
                className="ws-tool"
                form={form}
                onSubmit={() => form.post("/workshop/registration-scan")}
            >
                <section className="ws-form-section">
                    <h2>Απαραίτητος ανθρώπινος έλεγχος</h2>
                    <p className="ws-muted ws-mb-6">
                        Διορθώστε ό,τι δεν διαβάστηκε σωστά. Τα στοιχεία που δεν
                        διαβάζονται με βεβαιότητα ή δεν υπάρχουν στην άδεια μένουν κενά.
                        Ελέγξτε την πινακίδα στο (A), το VIN στο (E) και το μοντέλο στο (D.3).
                    </p>
                    <div className="ws-form-grid">
                        <Field
                            label="Ονοματεπώνυμο / επωνυμία κατόχου"
                            name="owner_full_name"
                            form={form}
                            wide
                        />
                        <Field
                            label="ΑΦΜ"
                            name="afm"
                            form={form}
                            maxLength="9"
                        />
                        <Field
                            label="Τηλέφωνο"
                            name="phone"
                            form={form}
                            type="tel"
                        />
                        <Field
                            label="Διεύθυνση"
                            name="address"
                            form={form}
                            wide
                        />
                        <Field
                            label="Πινακίδα"
                            name="plate_number"
                            form={form}
                        />
                        <Field
                            label="VIN / αριθμός πλαισίου"
                            name="vin"
                            form={form}
                        />
                        <MakeModelFields
                            form={form}
                            modelsByMake={modelsByMake}
                        />
                        <Field label="Καύσιμο" name="fuel" form={form} />
                        <Field
                            label="Κυβισμός (cc)"
                            name="engine_cc"
                            form={form}
                            type="number"
                        />
                        <Field
                            label="Πρώτη κυκλοφορία"
                            name="first_registered_at"
                            form={form}
                            type="date"
                        />
                    </div>
                    {(errors.near_vin ||
                        form.errors.near_vin ||
                        form.data.confirm_near_vin) && (
                        <label className="ws-actions ws-mt-6">
                            <input
                                name="confirm_near_vin"
                                type="checkbox"
                                checked={!!form.data.confirm_near_vin}
                                onChange={(e) =>
                                    form.setData(
                                        "confirm_near_vin",
                                        e.target.checked,
                                    )
                                }
                            />
                            Έλεγξα ξανά το πεδίο (E): πρόκειται για διαφορετικό
                            όχημα από το παρόμοιο VIN.
                        </label>
                    )}
                </section>
                <FormFooter
                    form={form}
                    cancel="/workshop/registration-scan?new=1"
                    label="Δημιουργία πελάτη και οχήματος"
                >
                    <span className="ws-muted">
                        Υπάρχων πελάτης με ίδιο ΑΦΜ επαναχρησιμοποιείται.
                    </span>
                </FormFooter>
            </Form>
        </>
    );
}
