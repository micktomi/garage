<div
    x-data="{ message: @js($message) }"
    class="space-y-4 pb-2"
>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
            Μήνυμα
        </label>
        <textarea
            x-model="message"
            rows="5"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-3 text-sm resize-y focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        ></textarea>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400">
        Αποστολή στο: <strong class="text-gray-700 dark:text-gray-200">{{ $phone }}</strong>
    </p>

    <a
        :href="'sms:{{ $phone }}?body=' + encodeURIComponent(message)"
        class="inline-flex items-center gap-x-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-semibold text-white hover:bg-success-500 transition-colors"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
        </svg>
        Άνοιγμα SMS
    </a>

    <p class="text-xs text-gray-400">
        Ανοίγει το SMS app. Πατήστε Αποστολή από το κινητό.
    </p>
</div>
