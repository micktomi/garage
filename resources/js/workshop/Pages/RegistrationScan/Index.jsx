import React, { useRef, useState } from "react";
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
export default function Index({ extracted, modelsByMake }) {
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
                <Upload />
            )}
        </>
    );
}
function Upload() {
    const form = useForm({
        registration_image: null,
        browser_preprocess_ms: null,
    });
    const [preparing, setPreparing] = useState(false);
    const camera = useRef(null);
    const gallery = useRef(null);
    const select = async (e) => {
        const file = e.target.files?.[0];
        e.target.value = "";
        if (!file) return;
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
        let processed = file;
        try {
            processed = await prepareImage(file);
        } catch {
            /* keep original for server validation */
        }
        form.setData({
            registration_image: processed,
            browser_preprocess_ms: Math.round(performance.now() - start),
        });
        setPreparing(false);
    };
    return (
        <>
            <Feedback errors={form.errors} />
            <Form
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
                            τμήματα στο κάδρο. JPEG, PNG ή WebP έως 10 MB.
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
                    <p className="ws-muted" style={{ marginTop: 16 }}>
                        Η φωτογραφία δεν αποθηκεύεται. Ελέγχετε κάθε
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
                form={form}
                onSubmit={() => form.post("/workshop/registration-scan")}
            >
                <section className="ws-form-section">
                    <h2>Απαραίτητος ανθρώπινος έλεγχος</h2>
                    <p className="ws-muted" style={{ marginBottom: 20 }}>
                        Διορθώστε ό,τι δεν διαβάστηκε σωστά. Αν το VIN δεν είναι
                        βέβαιο, ελέγξτε το πεδίο (E) της άδειας.
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
                        <label className="ws-actions" style={{ marginTop: 20 }}>
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
