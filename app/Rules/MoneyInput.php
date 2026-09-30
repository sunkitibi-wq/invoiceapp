<?php

namespace App\Rules;

use App\Support\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates money typed by a person, such as "1,234.56".
 */
class MoneyInput implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param  bool  $mustBePositive  Require a value greater than zero.
     */
    public function __construct(
        private readonly bool $mustBePositive = false,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->looksLikeAmount($value)) {
            $fail(__('The :attribute must be a number.'));

            return;
        }

        $amount = Money::fromInput((string) $value);

        if ($this->mustBePositive ? $amount->cents <= 0 : $amount->cents < 0) {
            $fail($this->mustBePositive
                ? __('The :attribute must be greater than zero.')
                : __('The :attribute must not be negative.'));

            return;
        }

        if ($amount->cents > Money::of(99_999_999_00)->cents) {
            $fail(__('The :attribute is too large.'));
        }
    }

    /**
     * Determine whether the value only contains characters an amount may use.
     */
    private function looksLikeAmount(mixed $value): bool
    {
        $raw = trim((string) $value);

        return preg_match('/\d/', $raw) === 1
            && preg_replace('/[\s,\.0-9\-]/', '', $raw) === ''
            && is_numeric(Money::parseInput($raw));
    }
}
