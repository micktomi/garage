const messages = {
    "insecure-context": "Η υπαγόρευση χρειάζεται ασφαλή σύνδεση HTTPS. Η πρόσβαση από κινητό μέσω HTTP σε διεύθυνση τοπικού δικτύου δεν επιτρέπει το μικρόφωνο.",
    "not-allowed": "Δεν επιτράπηκε η πρόσβαση στο μικρόφωνο. Ελέγξτε την άδεια μικροφώνου για αυτή τη σελίδα στις ρυθμίσεις του browser.",
    "service-not-allowed": "Ο browser δεν επιτρέπει την υπηρεσία αναγνώρισης ομιλίας. Γράψτε το αίτημά σας ή χρησιμοποιήστε browser που υποστηρίζει υπαγόρευση.",
    "audio-capture": "Δεν είναι διαθέσιμο το μικρόφωνο. Ελέγξτε τη σύνδεση και τις άδειες μικροφώνου της συσκευής.",
    "network": "Δεν ήταν δυνατή η σύνδεση με την υπηρεσία αναγνώρισης ομιλίας. Ελέγξτε τη σύνδεσή σας και δοκιμάστε ξανά.",
    "no-speech": "Δεν εντοπίστηκε ομιλία. Πατήστε ξανά Υπαγόρευση και μιλήστε κοντά στο μικρόφωνο.",
    "language-not-supported": "Ο browser δεν υποστηρίζει αναγνώριση ομιλίας στα ελληνικά. Γράψτε το αίτημά σας.",
};

export function speechErrorMessage(code) {
    return messages[code] || "Η υπαγόρευση δεν ολοκληρώθηκε. Ελέγξτε το μικρόφωνο ή γράψτε το αίτημά σας.";
}

// Development only. Never log audio, transcripts, URLs or customer data.
// Permission queries do not request access or open a second microphone stream.
export function createSpeechDiagnostics(enabled, browser) {
    const report = (event, details = {}) => {
        if (enabled) browser.console.debug("[Workshop dictation]", event, JSON.stringify(details));
    };
    const permission = async () => {
        if (!enabled) return;
        if (!browser.navigator.permissions?.query) {
            report("microphone-permission", { state: "unsupported" });
            return;
        }
        try {
            const result = await browser.navigator.permissions.query({ name: "microphone" });
            report("microphone-permission", { state: result.state });
        } catch (error) {
            report("microphone-permission", { state: "unavailable", error: error.name });
        }
    };
    return {
        report,
        permission,
        inspect() {
            if (!enabled) return;
            report("environment", {
                isSecureContext: browser.isSecureContext,
                mediaDevicesAvailable: Boolean(browser.navigator.mediaDevices),
                speechRecognitionAvailable: Boolean(browser.SpeechRecognition || browser.webkitSpeechRecognition),
            });
            void permission();
        },
    };
}
