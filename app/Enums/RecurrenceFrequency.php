<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

enum RecurrenceFrequency: string
{
    case Weekly = 'weekly';
    case Fortnightly = 'fortnightly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    /**
     * Get the human readable label for the frequency.
     */
    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Weekly',
            self::Fortnightly => 'Fortnightly',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Yearly => 'Yearly',
        };
    }

    /**
     * Determine the next occurrence on or after the given date.
     */
    public function nextDate(Carbon $from): Carbon
    {
        return match ($this) {
            self::Weekly => $from->addWeek(),
            self::Fortnightly => $from->addWeeks(2),
            self::Monthly => $from->addMonthNoOverflow(),
            self::Quarterly => $from->addMonthsNoOverflow(3),
            self::Yearly => $from->addYearNoOverflow(),
        };
    }

    /**
     * Get the list of frequencies.
     *
     * @return list<self>
     */
    public static function values(): array
    {
        return self::cases();
    }
}
