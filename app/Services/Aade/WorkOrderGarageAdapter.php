<?php

namespace App\Services\Aade;

use App\Models\WorkOrder;
use Micktomi\GarageAadeBridge\Contracts\GarageClientDataSource;
use Micktomi\GarageAadeBridge\Data\GarageUseCaseData;
use Micktomi\GarageAadeBridge\Data\NewClientData;
use Micktomi\GarageAadeBridge\Data\UpdateClientData;
use RuntimeException;

/**
 * Maps a garage-manager WorkOrder to the garage-aade-bridge DTOs — the only
 * class that needs to know about both. Copied from the package's own
 * reference pattern (tests/workbench/app/WorkOrderGarageAdapter.php).
 */
final class WorkOrderGarageAdapter implements GarageClientDataSource
{
    public function __construct(private readonly WorkOrder $workOrder) {}

    public function toNewClientData(): NewClientData
    {
        return new NewClientData(
            branch: (int) config('services.aade.branch'),
            useCase: new GarageUseCaseData(
                vehicleRegistrationNumber: $this->workOrder->vehicle?->plate_number,
            ),
        );
    }

    public function toUpdateClientData(int $initialDclId): UpdateClientData
    {
        // Requires the invoiceKind/reasonNonIssueType closure fields and the
        // providedServiceCategory mapping — landing in checkpoint 5.
        throw new RuntimeException('WorkOrderGarageAdapter::toUpdateClientData() is not implemented yet (checkpoint 5).');
    }
}
