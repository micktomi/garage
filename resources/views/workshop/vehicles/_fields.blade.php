@php
    $vehicle = $vehicle ?? null;
@endphp

<div class="woc-card">
    <div class="woc-card-title">Στοιχεία Οχήματος</div>
    <div class="woc-card-body woc-card-body--grid">

        <div class="woc-field span2">
            <label for="customer_id" class="woc-label">Πελάτης <span class="woc-req">*</span></label>
            <select id="customer_id" name="customer_id"
                class="woc-select {{ $errors->has('customer_id') ? 'is-invalid' : '' }}">
                <option value="">— Επιλογή πελάτη —</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customer_id', $vehicle?->customer_id) == $customer->id ? 'selected' : '' }}>
                        {{ $customer->full_name }}
                    </option>
                @endforeach
            </select>
            @error('customer_id') <span class="woc-error">{{ $message }}</span> @enderror
        </div>

        <div class="woc-field span2">
            <label for="license_plate" class="woc-label">Πινακίδα <span class="woc-req">*</span></label>
            <input type="text" id="license_plate" name="license_plate"
                class="woc-input {{ $errors->has('license_plate') ? 'is-invalid' : '' }}"
                value="{{ old('license_plate', $vehicle?->plate_number) }}"
                placeholder="π.χ. ΑΒΓ-1234">
            @error('license_plate') <span class="woc-error">{{ $message }}</span> @enderror
        </div>

        <div class="woc-field">
            <label for="make" class="woc-label">Μάρκα</label>
            <input type="text" id="make" name="make" list="makes-list" autocomplete="off"
                class="woc-input {{ $errors->has('make') ? 'is-invalid' : '' }}"
                value="{{ old('make', $vehicle?->make) }}"
                placeholder="π.χ. Toyota">
            <datalist id="makes-list">
                @foreach($makes as $make)
                    <option value="{{ $make }}"></option>
                @endforeach
            </datalist>
            @error('make') <span class="woc-error">{{ $message }}</span> @enderror
        </div>

        <div class="woc-field">
            <label for="model" class="woc-label">Μοντέλο</label>
            <input type="text" id="model" name="model" list="models-list" autocomplete="off"
                class="woc-input {{ $errors->has('model') ? 'is-invalid' : '' }}"
                value="{{ old('model', $vehicle?->model) }}"
                placeholder="π.χ. Yaris">
            <datalist id="models-list"></datalist>
            @error('model') <span class="woc-error">{{ $message }}</span> @enderror
        </div>

        <div class="woc-field">
            <label for="year" class="woc-label">Έτος</label>
            <input type="number" id="year" name="year"
                class="woc-input {{ $errors->has('year') ? 'is-invalid' : '' }}"
                value="{{ old('year', $vehicle?->year) }}"
                min="1900" max="{{ date('Y') + 1 }}" step="1" placeholder="π.χ. 2018">
            @error('year') <span class="woc-error">{{ $message }}</span> @enderror
        </div>

        <div class="woc-field">
            <label for="vin" class="woc-label">VIN / Αρ. πλαισίου</label>
            <input type="text" id="vin" name="vin"
                class="woc-input {{ $errors->has('vin') ? 'is-invalid' : '' }}"
                value="{{ old('vin', $vehicle?->vin) }}"
                maxlength="50" placeholder="π.χ. WVWZZZ1JZXW000001">
            @error('vin') <span class="woc-error">{{ $message }}</span> @enderror
        </div>

        <div class="woc-field">
            <label for="mileage" class="woc-label">Χιλιόμετρα</label>
            <input type="number" id="mileage" name="mileage"
                class="woc-input {{ $errors->has('mileage') ? 'is-invalid' : '' }}"
                value="{{ old('mileage', $vehicle?->mileage) }}"
                min="0" step="1" placeholder="π.χ. 85000">
            @error('mileage') <span class="woc-error">{{ $message }}</span> @enderror
        </div>

        <div class="woc-field">
            <label for="kteo_expires_at" class="woc-label">Λήξη ΚΤΕΟ</label>
            <input type="date" id="kteo_expires_at" name="kteo_expires_at"
                class="woc-input {{ $errors->has('kteo_expires_at') ? 'is-invalid' : '' }}"
                value="{{ old('kteo_expires_at', $vehicle?->kteo_expires_at?->format('Y-m-d')) }}">
            @error('kteo_expires_at') <span class="woc-error">{{ $message }}</span> @enderror
        </div>

    </div>
</div>
@once
    @push('scripts')
        <script>
            (() => {
                const makeInput = document.getElementById('make');
                const modelsList = document.getElementById('models-list');
                const modelsByMake = @json($modelsByMake);

                if (!makeInput || !modelsList) {
                    return;
                }

                const normalizedModelsByMake = Object.entries(modelsByMake).reduce((catalog, [make, models]) => {
                    catalog[make.trim().toLocaleLowerCase()] = models;

                    return catalog;
                }, {});

                const updateModels = () => {
                    const make = makeInput.value.trim().toLocaleLowerCase();
                    const models = normalizedModelsByMake[make] ?? [];

                    modelsList.replaceChildren(...models.map((model) => {
                        const option = document.createElement('option');
                        option.value = model;

                        return option;
                    }));
                };

                makeInput.addEventListener('input', updateModels);
                makeInput.addEventListener('change', updateModels);
                updateModels();
            })();
        </script>
    @endpush
@endonce
