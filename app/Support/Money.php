<?php

namespace App\Support;

use App\Casts\MoneyCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use NumberFormatter;
use Stringable;
use Throwable;

/**
 * An immutable monetary amount stored in the minor unit of the configured
 * currency, e.g. 12.34 USD is represented by 1234 cents.
 *
 * @implements Arrayable<string, mixed>
 */
final class Money implements Arrayable, Castable, JsonSerializable, Stringable
{
    public function __construct(public readonly int $cents) {}

    /**
     * Get the caster class used when the value is cast from a database column.
     *
     * @param  array<string, mixed>  $arguments
     * @return class-string<CastsAttributes<Money, mixed>>
     */
    public static function castUsing(array $arguments): string
    {
        return MoneyCast::class;
    }

    /**
     * Create an amount from a number of cents.
     */
    public static function of(int $cents): self
    {
        return new self($cents);
    }

    /**
     * Create a zero amount.
     */
    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Create an amount from a decimal value, e.g. 12.34 becomes 1234.
     */
    public static function fromDecimal(float|int|string $amount): self
    {
        return new self((int) round((float) $amount * 100));
    }

    /**
     * Create an amount from user input such as "1,234.56".
     */
    public static function fromInput(?string $value): self
    {
        if ($value === null || trim($value) === '') {
            return self::zero();
        }

        return self::fromDecimal(self::parseInput($value));
    }

    /**
     * Normalise a user supplied amount to a plain decimal string.
     */
    public static function parseInput(string $value): string
    {
        $value = trim($value);
        $negative = str_starts_with($value, '-');

        $value = str_replace([' ', ' '], '', $value);
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // Whichever separator appears last is the decimal separator.
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            // A single comma is a decimal separator only when it is not
            // followed by exactly three digits, e.g. "1,50" but not "1,500".
            $value = preg_match('/,\d{3}$/', $value) === 1
                ? str_replace(',', '', $value)
                : str_replace(',', '.', $value);
        }

        $value = preg_replace('/[^0-9.\-]/', '', $value) ?? '0';

        return ($negative && ! str_starts_with($value, '-') ? '-' : '').($value === '' ? '0' : $value);
    }

    /**
     * Add the given amounts together.
     */
    public function plus(self|int ...$amounts): self
    {
        $cents = $this->cents;

        foreach ($amounts as $amount) {
            $cents += $amount instanceof self ? $amount->cents : $amount;
        }

        return new self($cents);
    }

    /**
     * Subtract the given amounts.
     */
    public function minus(self|int ...$amounts): self
    {
        $cents = $this->cents;

        foreach ($amounts as $amount) {
            $cents -= $amount instanceof self ? $amount->cents : $amount;
        }

        return new self($cents);
    }

    /**
     * Negate the amount.
     */
    public function negated(): self
    {
        return new self(-$this->cents);
    }

    /**
     * Multiply the amount by a factor, e.g. a quantity.
     */
    public function multipliedBy(float $multiplier): self
    {
        return new self((int) round($this->cents * $multiplier));
    }

    /**
     * Take a percentage of the amount, e.g. 0.2 for 20%.
     */
    public function percentage(float $rate): self
    {
        return $this->multipliedBy($rate);
    }

    /**
     * Determine the smaller of two amounts.
     */
    public function min(self $other): self
    {
        return $this->cents <= $other->cents ? $this : $other;
    }

    /**
     * Determine the larger of two amounts.
     */
    public function max(self $other): self
    {
        return $this->cents >= $other->cents ? $this : $other;
    }

    /**
     * Determine whether the amount is exactly zero.
     */
    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    /**
     * Determine whether the amount is above zero.
     */
    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    /**
     * Determine whether the amount is below zero.
     */
    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    /**
     * Compare the amount with another, returning -1, 0 or 1.
     */
    public function compare(self|int $other): int
    {
        return $this->cents <=> ($other instanceof self ? $other->cents : $other);
    }

    /**
     * Format the amount for display.
     */
    public function format(): string
    {
        $currency = (string) config('invoicing.currency', 'USD');
        $locale = (string) config('invoicing.currency_locale', 'en_US');

        try {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

            $formatted = $formatter->formatCurrency($this->cents / 100, $currency);

            return $formatted === false
                ? $currency.' '.number_format($this->cents / 100, 2)
                : $formatted;
        } catch (Throwable) {
            return $currency.' '.number_format($this->cents / 100, 2);
        }
    }

    /**
     * Get the amount as a decimal float.
     */
    public function toDecimal(): float
    {
        return $this->cents / 100;
    }

    /**
     * Get the amount as a plain decimal string suitable for form inputs.
     */
    public function toInput(): string
    {
        return number_format($this->cents / 100, 2, '.', '');
    }

    /**
     * Get the array representation of the amount.
     *
     * @return array{cents: int, formatted: string}
     */
    public function toArray(): array
    {
        return [
            'cents' => $this->cents,
            'formatted' => $this->format(),
        ];
    }

    /**
     * Get the JSON representation of the amount.
     */
    public function jsonSerialize(): string
    {
        return $this->format();
    }

    /**
     * Get the string representation of the amount.
     */
    public function __toString(): string
    {
        return $this->format();
    }
}
