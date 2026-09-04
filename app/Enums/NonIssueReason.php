<?php

namespace App\Enums;

/**
 * Why no invoice/receipt was issued, when {@see ClosureDocument::None} is
 * chosen.
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
