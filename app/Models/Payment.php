<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property Money $amount
 * @property PaymentMethod $method
 * @property Carbon $paid_at
 * @property string|null $reference
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BelongsTo<Invoice, $this> $invoice
 */
#[Fillable([
    'amount',
    'method',
    'paid_at',
    'reference',
    'note',
])]
#[Appends(['method_label'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => Money::class,
            'method' => PaymentMethod::class,
            'paid_at' => 'date',
        ];
    }

    /**
     * Get the invoice the payment was made against.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the human readable payment method.
     */
    public function getMethodLabelAttribute(): string
    {
        return $this->method->label();
    }

    /**
     * Scope a query to payments made with the given method.
     *
     * @param  Builder<Payment>  $query
     */
    #[Scope]
    protected function withMethod(Builder $query, PaymentMethod|string|null $method): void
    {
        $query->when(
            $method,
            fn (Builder $query) => $query->where('method', $method instanceof PaymentMethod ? $method->value : $method)
        );
    }

    /**
     * Scope a query to payments matching the given term.
     *
     * @param  Builder<Payment>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $query->when($term, function (Builder $query, string $term): void {
            $query->where(function (Builder $query) use ($term): void {
                $query->where('reference', 'like', "%{$term}%")
                    ->orWhereHas('invoice', function (Builder $query) use ($term): void {
                        $query->where('number', 'like', "%{$term}%")
                            ->orWhereHas('customer', function (Builder $query) use ($term): void {
                                $query->where('name', 'like', "%{$term}%")
                                    ->orWhere('company', 'like', "%{$term}%");
                            });
                    });
            });
        });
    }

    /**
     * Scope a query to payments received within the given period.
     *
     * @param  Builder<Payment>  $query
     */
    #[Scope]
    protected function inPeriod(Builder $query, ?string $period): void
    {
        $query->when($period, function (Builder $query) use ($period): void {
            $query->whereDate('paid_at', '>=', match ($period) {
                'today' => now()->toDateString(),
                'week' => now()->subWeek()->toDateString(),
                'month' => now()->startOfMonth()->toDateString(),
                'quarter' => now()->subMonths(3)->toDateString(),
                'year' => now()->startOfYear()->toDateString(),
                default => now()->toDateString(),
            });
        });
    }
}
