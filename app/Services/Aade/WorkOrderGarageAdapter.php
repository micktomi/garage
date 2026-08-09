<?php

namespace App\Services\Aade;

use App\Enums\ClosureDocument;
use App\Enums\NonIssueReason;
use App\Models\WorkOrder;
use Micktomi\GarageAadeBridge\Contracts\GarageClientDataSource;
use Micktomi\GarageAadeBridge\Data\GarageUseCaseData;
use Micktomi\GarageAadeBridge\Data\NewClientData;
use Micktomi\GarageAadeBridge\Data\UpdateClientData;
use Micktomi\GarageAadeBridge\Enums\InvoiceKind;
use Micktomi\GarageAadeBridge\Enums\ProvidedServiceCategory;
use Micktomi\GarageAadeBridge\Enums\ReasonNonIssueType;
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
        $closureDocument = $this->workOrder->closure_document
            ?? throw new RuntimeException("WorkOrder #{$this->workOrder->id} has no closure_document set — cannot build UpdateClientData.");

        return new UpdateClientData(
            initialDclId: $initialDclId,
            providedServiceCategory: $this->providedServiceCategory(),
            entryCompletion: true,
            nonIssueInvoice: $closureDocument === ClosureDocument::None,
            invoiceKind: match ($closureDocument) {
                ClosureDocument::RetailReceipt => InvoiceKind::RetailReceipt,
                ClosureDocument::Invoice => InvoiceKind::Invoice,
                ClosureDocument::None => null,
            },
            reasonNonIssueType: $closureDocument === ClosureDocument::None
                ? $this->reasonNonIssueType()
                : null,
        );
    }

    private function reasonNonIssueType(): ReasonNonIssueType
    {
        $reason = $this->workOrder->non_issue_reason
            ?? throw new RuntimeException("WorkOrder #{$this->workOrder->id} has closure_document=none but no non_issue_reason set.");

        return match ($reason) {
            NonIssueReason::FreeService => ReasonNonIssueType::FreeService,
            NonIssueReason::Warranty => ReasonNonIssueType::WarrantyCompensation,
            NonIssueReason::SelfUse => ReasonNonIssueType::SelfUse,
        };
    }

    /**
     * no parts -> NoPartsUsed; all customer_supplied -> PartsSuppliedByCustomer;
     * any from_stock/purchased_for_job -> PartsUsedByGarage. No manual
     * override yet (YAGNI) — add one only if this mapping proves wrong for a
     * real work order.
     */
    private function providedServiceCategory(): ProvidedServiceCategory
    {
        $sources = $this->workOrder->workOrderParts()->pluck('source');

        if ($sources->isEmpty()) {
            return ProvidedServiceCategory::NoPartsUsed;
        }

        if ($sources->every(fn (string $source): bool => $source === 'customer_supplied')) {
            return ProvidedServiceCategory::PartsSuppliedByCustomer;
        }

        return ProvidedServiceCategory::PartsUsedByGarage;
    }
}
