<?php

namespace App\Enums;

enum DiscountType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    /**
     * Get the human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }

    /**
     * Get the list of types.
     *
     * @return list<self>
     */
    public static function values(): array
    {
        return self::cases();
    }
}
