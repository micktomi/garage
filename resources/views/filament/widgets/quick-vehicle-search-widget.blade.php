<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Γρήγορη αναζήτηση οχήματος / πελάτη
        </x-slot>

        <div class="space-y-3">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Πινακίδα, μάρκα, μοντέλο, πελάτης ή τηλέφωνο..."
                />
            </x-filament::input.wrapper>

            @if (mb_strlen(trim($search ?? '')) >= 2)
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Πινακίδα</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Όχημα</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Πελάτης</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Τηλέφωνο</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white dark:divide-white/10 dark:bg-transparent">
                            @forelse ($this->getResults() as $vehicle)
                                <tr wire:key="quick-vehicle-{{ $vehicle->id }}">
                                    <td class="px-3 py-2 font-semibold text-gray-950 dark:text-white">{{ $vehicle->plate_number }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ trim($vehicle->make . ' ' . $vehicle->model) }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $vehicle->customer?->full_name ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $vehicle->customer?->phone ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <x-filament::link :href="$this->vehicleUrl($vehicle)">
                                            Προβολή
                                        </x-filament::link>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Δεν βρέθηκαν αποτελέσματα.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
