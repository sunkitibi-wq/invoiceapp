<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<Money, mixed>
 */
class MoneyCast implements CastsAttributes
{
    /**
     * Cast the stored minor units to a money value object.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Money
    {
        return Money::of((int) $value);
    }

    /**
     * Prepare a money value for storage.
     *
     * Value objects and integers are already in the minor unit, while strings
     * are treated as user input such as "1,234.56".
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return match (true) {
            $value instanceof Money => $value->cents,
            is_int($value) => $value,
            is_float($value) => Money::fromDecimal($value)->cents,
            $value === null => 0,
            default => Money::fromInput((string) $value)->cents,
        };
    }
}
