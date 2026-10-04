import test from "node:test";
import assert from "node:assert/strict";
import { createSpeechDiagnostics, speechErrorMessage } from "../../resources/js/workshop/Pages/Assistant/speech.js";

test("production diagnostics never inspect permissions or log speech", async () => {
    const browser = { get navigator() { throw new Error("must not inspect"); }, get console() { throw new Error("must not log"); } };
    const diagnostics = createSpeechDiagnostics(false, browser);
    diagnostics.inspect();
    diagnostics.report("recognition-error", { error: "not-allowed" });
    await diagnostics.permission();
});

test("development records environment, microphone state and exact error without transcript", async () => {
    const logs = [];
    const browser = { isSecureContext: false, webkitSpeechRecognition() {}, console: { debug: (...args) => logs.push(args) }, navigator: { permissions: { query: async (descriptor) => { assert.deepEqual(descriptor, { name: "microphone" }); return { state: "denied" }; } } } };
    const diagnostics = createSpeechDiagnostics(true, browser);
    diagnostics.inspect();
    await diagnostics.permission();
    diagnostics.report("recognition-error", { error: "not-allowed" });
    assert.deepEqual(JSON.parse(logs[0][2]), { isSecureContext: false, mediaDevicesAvailable: false, speechRecognitionAvailable: true });
    assert.ok(logs.some(log => log[1] === "microphone-permission" && JSON.parse(log[2]).state === "denied"));
    assert.deepEqual(JSON.parse(logs.at(-1)[2]), { error: "not-allowed" });
});

test("unsupported microphone permission queries are safe", async () => {
    const logs = [];
    const diagnostics = createSpeechDiagnostics(true, { navigator: { permissions: { query: async () => { throw new TypeError(); } } }, console: { debug: (...args) => logs.push(args) } });
    await diagnostics.permission();
    assert.deepEqual(JSON.parse(logs[0][2]), { state: "unavailable", error: "TypeError" });
});

test("insecure context and speech failures provide distinct actionable messages", () => {
    assert.match(speechErrorMessage("insecure-context"), /HTTPS/);
    assert.match(speechErrorMessage("not-allowed"), /άδεια μικροφώνου/);
    assert.match(speechErrorMessage("network"), /υπηρεσία αναγνώρισης/);
    assert.notEqual(speechErrorMessage("audio-capture"), speechErrorMessage("no-speech"));
    assert.match(speechErrorMessage("unknown"), /Γράψτε|γράψτε/);
});
