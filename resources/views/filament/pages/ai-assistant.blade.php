<x-filament-panels::page>
    <div
        class="mx-auto flex w-full max-w-5xl flex-col gap-5"
        x-data="{
            supported: !!(window.SpeechRecognition || window.webkitSpeechRecognition),
            listening: false,
            recognition: null,
            startListening() {
                if (!this.supported || this.listening) return;
                const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                this.recognition = new Recognition();
                this.recognition.lang = 'el-GR';
                this.recognition.interimResults = true;
                this.recognition.continuous = false;
                let finalText = '';
                this.recognition.onstart = () => this.listening = true;
                this.recognition.onend = () => this.listening = false;
                this.recognition.onerror = () => this.listening = false;
                this.recognition.onresult = (event) => {
                    let interimText = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const text = event.results[i][0].transcript;
                        if (event.results[i].isFinal) finalText += text;
                        else interimText += text;
                    }
                    $wire.set('input', (finalText || interimText).trim());
                };
                this.recognition.start();
            },
            stopListening() {
                if (this.recognition) this.recognition.stop();
            }
        }"
    >
        @if (! config('garage-assistant.enabled'))
            <div class="rounded-xl border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-200">
                Ο AI Βοηθός είναι απενεργοποιημένος. Όρισε <code>GARAGE_ASSISTANT_ENABLED=true</code> στο τοπικό <code>.env</code>.
            </div>
        @elseif (blank(config('garage-assistant.gemini.api_key')))
            <div class="rounded-xl border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-200">
                Λείπει το Gemini API key. Συμπλήρωσε το <code>GEMINI_API_KEY</code> στο τοπικό <code>.env</code>.
            </div>
        @endif

        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Τα δεδομένα διαβάζονται μόνο μέσω ελεγχόμενων tools. Καμία εγγραφή δεν γίνεται χωρίς επιβεβαίωση.
            </p>
            <x-filament::button color="gray" size="sm" wire:click="clearChat">
                Νέα συνομιλία
            </x-filament::button>
        </div>

        <section class="min-h-[26rem] space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:p-6">
            @foreach ($messages as $index => $message)
                <div wire:key="assistant-message-{{ $index }}" class="flex {{ ($message['role'] ?? '') === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[88%] rounded-2xl px-4 py-3 text-sm leading-6 {{ ($message['role'] ?? '') === 'user' ? 'bg-primary-600 text-white' : (($message['type'] ?? '') === 'error' ? 'border border-danger-200 bg-danger-50 text-danger-800 dark:border-danger-800 dark:bg-danger-950 dark:text-danger-200' : 'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white') }}">
                        {!! nl2br(e($message['message'] ?? '')) !!}
                    </div>
                </div>
            @endforeach

            @if ($currentAmbiguity)
                <div class="rounded-2xl border border-info-200 bg-info-50 p-4 dark:border-info-800 dark:bg-info-950">
                    <h3 class="font-semibold text-info-900 dark:text-info-100">Χρειάζεται επιλογή</h3>
                    <div class="mt-3 grid gap-2">
                        @foreach (($currentAmbiguity['data']['options'] ?? []) as $optionIndex => $option)
                            <button
                                type="button"
                                wire:click="selectAmbiguity({{ $optionIndex }})"
                                class="rounded-xl border border-info-300 bg-white px-4 py-3 text-left text-sm font-medium text-gray-900 transition hover:border-info-500 dark:border-info-700 dark:bg-gray-900 dark:text-white"
                            >
                                {{ $option['label'] ?? 'Επιλογή' }}
                            </button>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-info-700 dark:text-info-300">Η επιλογή μεταφέρεται στο επεξεργάσιμο πεδίο πριν σταλεί.</p>
                </div>
            @endif

            @if ($currentProposal)
                <div class="rounded-2xl border-2 border-warning-300 bg-warning-50 p-5 dark:border-warning-700 dark:bg-warning-950">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-warning-700 dark:text-warning-300">Απαιτείται ανθρώπινη επιβεβαίωση</p>
                            <h3 class="mt-1 text-lg font-bold text-warning-950 dark:text-warning-100">{{ $currentProposal['proposal']['title'] ?? 'Προτεινόμενη ενέργεια' }}</h3>
                        </div>
                        <span class="rounded-full bg-warning-200 px-3 py-1 text-xs font-semibold text-warning-900 dark:bg-warning-800 dark:text-warning-100">Δεν έχει εκτελεστεί</span>
                    </div>
                    <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach (($currentProposal['proposal']['display'] ?? []) as $label => $value)
                            <div class="rounded-xl bg-white/80 p-3 dark:bg-gray-900/70">
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <x-filament::button color="warning" wire:click="confirmProposal" wire:loading.attr="disabled">
                            Επιβεβαίωση και δημιουργία
                        </x-filament::button>
                        <x-filament::button color="gray" wire:click="cancelProposal">
                            Ακύρωση
                        </x-filament::button>
                    </div>
                </div>
            @endif

            <div wire:loading.flex wire:target="sendMessage,confirmProposal" class="items-center gap-2 text-sm text-gray-500">
                <x-filament::loading-indicator class="h-5 w-5" />
                Επεξεργασία…
            </div>
        </section>

        <form wire:submit="sendMessage" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <label for="assistant-input" class="mb-2 block text-sm font-semibold text-gray-900 dark:text-white">Μήνυμα ή transcript</label>
            <textarea
                id="assistant-input"
                wire:model.live.debounce.300ms="input"
                rows="4"
                maxlength="2000"
                placeholder="π.χ. Ποια ραντεβού έχουμε αύριο;"
                class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
            ></textarea>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-filament::button type="button" color="gray" x-on:click="listening ? stopListening() : startListening()" x-bind:disabled="!supported">
                        <span x-text="listening ? 'Διακοπή μικροφώνου' : '🎙 Μικρόφωνο'"></span>
                    </x-filament::button>
                    <span x-show="listening" class="text-sm font-medium text-danger-600">Ακούω…</span>
                    <span x-show="!supported" class="text-xs text-gray-500">Ο browser δεν υποστηρίζει speech recognition. Η πληκτρολόγηση λειτουργεί κανονικά.</span>
                </div>
                <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="sendMessage">
                    Αποστολή
                </x-filament::button>
            </div>
            <p class="mt-2 text-xs text-gray-500">Το transcript δεν αποστέλλεται αυτόματα· μπορείς να το διορθώσεις πρώτα.</p>
        </form>
    </div>
</x-filament-panels::page>
