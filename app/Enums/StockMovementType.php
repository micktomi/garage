<?php

namespace App\Enums;

enum StockMovementType: string
{
    /** Stock entering the shop (a new part's opening quantity). */
    case In = 'in';

    /** Consumed by a work order line (negative), or returned from one (positive). */
    case Out = 'out';

    /** Manual correction of the counted quantity, and the opening balance of the ledger. */
    case Adjust = 'adjust';

    /** A work order was cancelled: its lines give their stock back. */
    case Cancel = 'cancel';

    /** A cancelled work order was reopened: its lines take their stock again. */
    case Reopen = 'reopen';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Εισαγωγή',
            self::Out => 'Εντολή εργασίας',
            self::Adjust => 'Διόρθωση',
            self::Cancel => 'Ακύρωση εντολής',
            self::Reopen => 'Επανάνοιγμα εντολής',
        };
    }
}
