<?php

namespace App\Enums;

enum ClosureDocument: string
{
    case RetailReceipt = 'retail_receipt';
    case Invoice = 'invoice';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::RetailReceipt => 'Απόδειξη λιανικής',
            self::Invoice => 'Τιμολόγιο',
            self::None => 'Χωρίς παραστατικό',
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
