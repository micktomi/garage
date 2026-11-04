<?php

namespace App\Enums;

/** Derived from total_cost and the order's payments; never stored. */
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    /** The total was lowered after payment: the customer has paid more than the order costs. */
    case Overpaid = 'overpaid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Απλήρωτη',
            self::Partial => 'Μερική εξόφληση',
            self::Paid => 'Εξοφλημένη',
            self::Overpaid => 'Υπερπληρωμή',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::Unpaid => 'danger',
            self::Partial => 'warning',
            self::Paid => 'success',
            self::Overpaid => 'info',
        };
    }

    /** Amounts in cents, so 0.1 + 0.2 never decides a status. */
    public static function fromCents(int $totalCents, int $paidCents): self
    {
        $balance = $totalCents - $paidCents;

        return match (true) {
            $balance < 0 => self::Overpaid,
            $balance === 0 => self::Paid,
            $paidCents === 0 => self::Unpaid,
            default => self::Partial,
        };
    }
}
