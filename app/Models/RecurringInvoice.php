<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\RecurrenceFrequency;
use Database\Factories\RecurringInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property RecurrenceFrequency $frequency
 * @property Carbon $next_invoice_at
 * @property Carbon|null $end_at
 * @property int $due_date_days
 * @property float $tax_rate
 * @property DiscountType|null $discount_type
 * @property float|null $discount_value
 * @property string|null $notes
 * @property string|null $terms
 * @property bool $is_active
 * @property Carbon|null $last_generated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Customer $customer
 * @property-read Collection<int, RecurringInvoiceItem> $items
 * @property-read HasMany<Invoice, $this> $invoices
 */
#[Fillable([
    'customer_id',
    'frequency',
    'next_invoice_at',
    'end_at',
    'due_date_days',
    'tax_rate',
    'discount_type',
    'discount_value',
    'notes',
    'terms',
    'is_active',
])]
#[Appends(['frequency_label', 'is_due', 'tax_rate_percent'])]
class RecurringInvoice extends Model
{
    /** @use HasFactory<RecurringInvoiceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'frequency' => RecurrenceFrequency::class,
            'next_invoice_at' => 'date',
            'end_at' => 'date',
            'due_date_days' => 'integer',
            'tax_rate' => 'float',
            'discount_type' => DiscountType::class,
            'discount_value' => 'float',
            'is_active' => 'boolean',
            'last_generated_at' => 'datetime',
        ];
    }

    /**
     * Get the customer the invoices are billed to.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the line items copied onto every generated invoice.
     *
     * @return HasMany<RecurringInvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RecurringInvoiceItem::class)->orderBy('position');
    }

    /**
     * Get the invoices generated from this schedule.
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Determine whether the schedule has run to its end date.
     */
    public function hasEnded(): bool
    {
        return $this->end_at !== null && $this->end_at->isPast();
    }

    /**
     * Determine whether an invoice is due for generation.
     */
    public function getIsDueAttribute(): bool
    {
        return $this->is_active
            && ! $this->hasEnded()
            && $this->next_invoice_at->isPast();
    }

    /**
     * Get the human readable frequency.
     */
    public function getFrequencyLabelAttribute(): string
    {
        return $this->frequency->label();
    }

    /**
     * Get the tax rate as the percentage people type into forms.
     */
    public function getTaxRatePercentAttribute(): float
    {
        return (float) $this->tax_rate * 100;
    }

    /**
     * Determine the next date an invoice should be generated on, without
     * running past the end of the schedule.
     */
    public function nextRunDate(): Carbon
    {
        $next = $this->frequency->nextDate(
            Carbon::parse($this->next_invoice_at)->startOfDay()
        );

        return $this->end_at !== null && $next->greaterThan($this->end_at)
            ? Carbon::parse($this->end_at)
            : $next;
    }

    /**
     * Scope a query to only include schedules that are due for generation.
     *
     * @param  Builder<RecurringInvoice>  $query
     */
    #[Scope]
    protected function due(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereDate('next_invoice_at', '<=', now()->toDateString())
            ->where(function (Builder $query): void {
                $query->whereNull('end_at')->orWhereDate('end_at', '>=', now()->toDateString());
            });
    }

    /**
     * Scope a query to only include active schedules.
     *
     * @param  Builder<RecurringInvoice>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
