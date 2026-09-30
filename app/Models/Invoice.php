<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Support\Money;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int $customer_id
 * @property int|null $recurring_invoice_id
 * @property InvoiceStatus $status
 * @property Carbon $issue_date
 * @property Carbon $due_date
 * @property string $currency
 * @property Money $subtotal
 * @property DiscountType|null $discount_type
 * @property float|null $discount_value
 * @property Money $discount_amount
 * @property float $tax_rate
 * @property Money $tax_total
 * @property Money $total
 * @property Money $amount_paid
 * @property string|null $notes
 * @property string|null $terms
 * @property Carbon|null $sent_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Customer $customer
 * @property-read BelongsTo<RecurringInvoice, $this> $recurringInvoice
 * @property-read HasMany<InvoiceItem, $this> $items
 * @property-read HasMany<Payment, $this> $payments
 */
#[Fillable([
    'number',
    'customer_id',
    'recurring_invoice_id',
    'status',
    'issue_date',
    'due_date',
    'currency',
    'subtotal',
    'discount_type',
    'discount_value',
    'discount_amount',
    'tax_rate',
    'tax_total',
    'total',
    'amount_paid',
    'notes',
    'terms',
    'sent_at',
    'paid_at',
])]
#[Appends(['balance', 'status_label', 'is_overdue', 'tax_rate_percent', 'is_editable'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'discount_type' => DiscountType::class,
            'discount_value' => 'float',
            'tax_rate' => 'float',
            'subtotal' => Money::class,
            'discount_amount' => Money::class,
            'tax_total' => Money::class,
            'total' => Money::class,
            'amount_paid' => Money::class,
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the customer the invoice is billed to.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the recurring invoice the invoice was generated from.
     *
     * @return BelongsTo<RecurringInvoice, $this>
     */
    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    /**
     * Get the line items on the invoice.
     *
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    /**
     * Get the payments recorded against the invoice.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    /**
     * Get the amount still outstanding on the invoice.
     */
    public function getBalanceAttribute(): Money
    {
        return $this->total->minus($this->amount_paid);
    }

    /**
     * Get the human readable status.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * Determine whether the invoice is past its due date and unpaid.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status->isOutstanding()
            && $this->balance->cents > 0
            && $this->due_date->isPast();
    }

    /**
     * Get the tax rate as the percentage people type into forms.
     */
    public function getTaxRatePercentAttribute(): float
    {
        return (float) $this->tax_rate * 100;
    }

    /**
     * Determine whether the invoice may still be edited.
     */
    public function getIsEditableAttribute(): bool
    {
        return $this->isEditable();
    }

    /**
     * Determine whether the invoice has been paid in full.
     */
    public function isPaid(): bool
    {
        return $this->total->isPositive() && $this->balance->cents <= 0;
    }

    /**
     * Determine whether the invoice may still be edited.
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Determine the status the invoice should hold given its payment progress.
     *
     * A passed due date always wins, so an invoice that is both part paid and
     * late is reported as overdue rather than as a healthy partial payment.
     */
    public function resolveStatusAfterPayment(): InvoiceStatus
    {
        if ($this->isPaid()) {
            return InvoiceStatus::Paid;
        }

        if ($this->due_date->isPast()) {
            return InvoiceStatus::Overdue;
        }

        return $this->amount_paid->cents > 0
            ? InvoiceStatus::PartiallyPaid
            : InvoiceStatus::Sent;
    }

    /**
     * Scope a query to only include invoices with the given status.
     *
     * @param  Builder<Invoice>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, InvoiceStatus|string|null $status): void
    {
        $query->when(
            $status,
            fn (Builder $query) => $query->where('status', $status instanceof InvoiceStatus ? $status->value : $status)
        );
    }

    /**
     * Scope a query to only include invoices matching the given term.
     *
     * @param  Builder<Invoice>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $query->when($term, function (Builder $query, string $term): void {
            $query->where(function (Builder $query) use ($term): void {
                $query->where('number', 'like', "%{$term}%")
                    ->orWhereHas('customer', function (Builder $query) use ($term): void {
                        $query->where('name', 'like', "%{$term}%")
                            ->orWhere('company', 'like', "%{$term}%");
                    });
            });
        });
    }

    /**
     * Scope a query to only include invoices with an outstanding balance.
     *
     * @param  Builder<Invoice>  $query
     */
    #[Scope]
    protected function outstanding(Builder $query): void
    {
        $query->whereIn('status', [
            InvoiceStatus::Sent->value,
            InvoiceStatus::PartiallyPaid->value,
            InvoiceStatus::Overdue->value,
        ])->whereColumn('amount_paid', '<', 'total');
    }

    /**
     * Scope a query to only include invoices that are past their due date.
     *
     * @param  Builder<Invoice>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->outstanding()
            ->whereDate('due_date', '<', now()->toDateString());
    }
}
