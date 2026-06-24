<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Γρήγορες ενέργειες
        </x-slot>

        <div class="grid gap-3 sm:grid-cols-3">
            <x-filament::button
                tag="a"
                :href="$this->customerCreateUrl()"
                icon="heroicon-o-user-plus"
                class="w-full justify-center"
            >
                Νέος Πελάτης
            </x-filament::button>

            <x-filament::button
                tag="a"
                :href="$this->vehicleCreateUrl()"
                icon="heroicon-o-truck"
                class="w-full justify-center"
            >
                Νέο Όχημα
            </x-filament::button>

            <x-filament::button
                tag="a"
                :href="$this->workOrderCreateUrl()"
                icon="heroicon-o-clipboard-document-list"
                class="w-full justify-center"
            >
                Νέα Εντολή Εργασίας
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
