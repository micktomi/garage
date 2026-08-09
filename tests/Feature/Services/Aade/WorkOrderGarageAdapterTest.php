<?php

namespace Tests\Feature\Services\Aade;

use App\Enums\ClosureDocument;
use App\Enums\NonIssueReason;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use App\Services\Aade\WorkOrderGarageAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Micktomi\GarageAadeBridge\Enums\InvoiceKind;
use Micktomi\GarageAadeBridge\Enums\ProvidedServiceCategory;
use Micktomi\GarageAadeBridge\Enums\ReasonNonIssueType;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class WorkOrderGarageAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_new_client_data_maps_branch_and_vehicle_plate(): void
    {
        config(['services.aade.branch' => 2]);

        $workOrder = $this->makeWorkOrder('ΑΒΓ1234');

        $data = (new WorkOrderGarageAdapter($workOrder))->toNewClientData();

        $this->assertSame(2, $data->branch);
        $this->assertSame('ΑΒΓ1234', $data->useCase->vehicleRegistrationNumber);
        $this->assertNull($data->useCase->vehicleCategory);
        $this->assertNull($data->customerVatNumber);
    }

    public function test_update_client_data_throws_without_a_closure_document(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΒΓ1234');

        $this->expectException(RuntimeException::class);

        (new WorkOrderGarageAdapter($workOrder))->toUpdateClientData(100000000830764);
    }

    public function test_update_client_data_throws_for_none_without_a_reason(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΒΓ1234', closureDocument: ClosureDocument::None);

        $this->expectException(RuntimeException::class);

        (new WorkOrderGarageAdapter($workOrder))->toUpdateClientData(100000000830764);
    }

    public function test_retail_receipt_maps_to_invoice_kind_without_non_issue_invoice(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΒΓ1234', closureDocument: ClosureDocument::RetailReceipt);

        $data = (new WorkOrderGarageAdapter($workOrder))->toUpdateClientData(100000000830764);

        $this->assertSame(100000000830764, $data->initialDclId);
        $this->assertTrue($data->entryCompletion);
        $this->assertFalse($data->nonIssueInvoice);
        $this->assertSame(InvoiceKind::RetailReceipt, $data->invoiceKind);
        $this->assertNull($data->reasonNonIssueType);
    }

    public function test_invoice_maps_to_invoice_kind_without_non_issue_invoice(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΒΓ1234', closureDocument: ClosureDocument::Invoice);

        $data = (new WorkOrderGarageAdapter($workOrder))->toUpdateClientData(100000000830764);

        $this->assertFalse($data->nonIssueInvoice);
        $this->assertSame(InvoiceKind::Invoice, $data->invoiceKind);
        $this->assertNull($data->reasonNonIssueType);
    }

    /**
     * @return array<string, array{NonIssueReason, ReasonNonIssueType}>
     */
    public static function nonIssueReasonMappings(): array
    {
        return [
            'free_service' => [NonIssueReason::FreeService, ReasonNonIssueType::FreeService],
            'warranty' => [NonIssueReason::Warranty, ReasonNonIssueType::WarrantyCompensation],
            'self_use' => [NonIssueReason::SelfUse, ReasonNonIssueType::SelfUse],
        ];
    }

    #[DataProvider('nonIssueReasonMappings')]
    public function test_none_maps_non_issue_reason_via_the_package_enum(
        NonIssueReason $reason,
        ReasonNonIssueType $expected,
    ): void {
        $workOrder = $this->makeWorkOrder('ΑΒΓ1234', closureDocument: ClosureDocument::None, nonIssueReason: $reason);

        $data = (new WorkOrderGarageAdapter($workOrder))->toUpdateClientData(100000000830764);

        $this->assertTrue($data->nonIssueInvoice);
        $this->assertNull($data->invoiceKind);
        $this->assertSame($expected, $data->reasonNonIssueType);
    }

    public function test_no_parts_maps_to_no_parts_used(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΒΓ1234', closureDocument: ClosureDocument::RetailReceipt);

        $data = (new WorkOrderGarageAdapter($workOrder))->toUpdateClientData(1);

        $this->assertSame(ProvidedServiceCategory::NoPartsUsed, $data->providedServiceCategory);
    }

    public function test_all_customer_supplied_parts_map_to_parts_supplied_by_customer(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΒΓ1234', closureDocument: ClosureDocument::RetailReceipt);
        $this->addPart($workOrder, 'customer_supplied');
        $this->addPart($workOrder, 'customer_supplied');

        $data = (new WorkOrderGarageAdapter($workOrder))->toUpdateClientData(1);

        $this->assertSame(ProvidedServiceCategory::PartsSuppliedByCustomer, $data->providedServiceCategory);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function garageSourcedParts(): array
    {
        return [
            'from_stock' => ['from_stock'],
            'purchased_for_job' => ['purchased_for_job'],
        ];
    }

    #[DataProvider('garageSourcedParts')]
    public function test_any_garage_sourced_part_maps_to_parts_used_by_garage(string $source): void
    {
        $workOrder = $this->makeWorkOrder('ΑΒΓ1234', closureDocument: ClosureDocument::RetailReceipt);
        $this->addPart($workOrder, 'customer_supplied');
        $this->addPart($workOrder, $source);

        $data = (new WorkOrderGarageAdapter($workOrder))->toUpdateClientData(1);

        $this->assertSame(ProvidedServiceCategory::PartsUsedByGarage, $data->providedServiceCategory);
    }

    private function makeWorkOrder(
        string $plate,
        ?ClosureDocument $closureDocument = null,
        ?NonIssueReason $nonIssueReason = null,
    ): WorkOrder {
        $customer = Customer::create(['full_name' => 'Test Customer']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
        ]);

        return WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Test service',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
            'closure_document' => $closureDocument,
            'non_issue_reason' => $nonIssueReason,
        ]);
    }

    private function addPart(WorkOrder $workOrder, string $source): WorkOrderPart
    {
        return WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'source' => $source,
            'description' => 'Test part',
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
        ]);
    }
}
