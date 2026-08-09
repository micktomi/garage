<?php

namespace App\Enums;

/**
 * Why no invoice/receipt was issued, when {@see ClosureDocument::None} is
 * chosen. Values correspond 1:1 to garage-aade-bridge's
 * Micktomi\GarageAadeBridge\Enums\ReasonNonIssueType — kept as a separate
 * local enum (rather than reusing the package's) because this is a
 * garage-manager domain choice, not a package concern.
 */
enum NonIssueReason: string
{
    case FreeService = 'free_service';
    case Warranty = 'warranty';
    case SelfUse = 'self_use';

    public function label(): string
    {
        return match ($this) {
            self::FreeService => 'Δωρεάν υπηρεσία',
            self::Warranty => 'Αποζημίωση παροχής εγγύησης',
            self::SelfUse => 'Ιδιόχρηση',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
