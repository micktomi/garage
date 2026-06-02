<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εντολή Εργασίας #{{ $workOrder->id }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; line-height: 1.6; color: #333; }
        .container { width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        .section-title { font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 10px; }
        .grid { display: flex; flex-wrap: wrap; }
        .col { flex: 1; min-width: 50%; }
        .label { font-weight: bold; width: 150px; display: inline-block; }
        .footer { margin-top: 50px; text-align: center; font-size: 0.9em; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
        .total-box { margin-top: 20px; border: 2px solid #333; padding: 10px; text-align: right; }
        .total-amount { font-size: 1.2em; font-weight: bold; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
            .container { border: none; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Εκτύπωση</button>
    </div>

    <div class="container">
        <div class="header">
            <h1>ΕΝΤΟΛΗ ΕΡΓΑΣΙΑΣ #{{ $workOrder->id }}</h1>
            <p>Ημερομηνία: {{ $workOrder->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <div class="grid section">
            <div class="col">
                <div class="section-title">Στοιχεία Πελάτη</div>
                <p><span class="label">Ονοματεπώνυμο:</span> {{ $workOrder->customer->full_name }}</p>
                <p><span class="label">Τηλέφωνο:</span> {{ $workOrder->customer->phone }}</p>
                @if($workOrder->customer->address)
                <p><span class="label">Διεύθυνση:</span> {{ $workOrder->customer->address }}</p>
                @endif
            </div>
            <div class="col">
                <div class="section-title">Στοιχεία Οχήματος</div>
                <p><span class="label">Πινακίδα:</span> <strong>{{ $workOrder->vehicle->plate_number }}</strong></p>
                <p><span class="label">Μάρκα / Μοντέλο:</span> {{ $workOrder->vehicle->make }} {{ $workOrder->vehicle->model }}</p>
                @if($workOrder->vehicle->vin)
                <p><span class="label">VIN:</span> {{ $workOrder->vehicle->vin }}</p>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">Κατάσταση & Περιγραφή</div>
            <p><span class="label">Κατάσταση:</span> 
                @switch ($workOrder->status)
                    @case('new') Νέα @break
                    @case('in_progress') Σε εξέλιξη @break
                    @case('completed') Ολοκληρώθηκε @break
                    @case('delivered') Παραδόθηκε @break
                    @default {{ $workOrder->status }}
                @endswitch
            </p>
            <p><strong>Περιγραφή Προβλήματος:</strong></p>
            <p>{{ $workOrder->problem_description }}</p>
            
            @if($workOrder->diagnosis)
            <p><strong>Διάγνωση:</strong></p>
            <p>{{ $workOrder->diagnosis }}</p>
            @endif

            @if($workOrder->work_performed)
            <p><strong>Εργασίες που εκτελέστηκαν:</strong></p>
            <p>{{ $workOrder->work_performed }}</p>
            @endif
        </div>

        <div class="section">
            <div class="section-title">Οικονομικά Στοιχεία</div>
            <p><span class="label">Κόστος Εργασίας:</span> {{ number_format($workOrder->labor_cost, 2) }} €</p>
            <p><span class="label">Κόστος Ανταλλακτικών:</span> {{ number_format($workOrder->parts_cost, 2) }} €</p>
            
            <div class="total-box">
                <span class="label">ΣΥΝΟΛΙΚΟ ΚΟΣΤΟΣ:</span>
                <span class="total-amount">{{ number_format($workOrder->total_cost, 2) }} €</span>
            </div>
        </div>

        <div class="footer">
            <p>Σας ευχαριστούμε για την εμπιστοσύνη σας!</p>
            <p>Το παρόν έγγραφο αποτελεί εντολή εργασίας και όχι επίσημο παραστατικό πώλησης.</p>
        </div>
    </div>
</body>
</html>
