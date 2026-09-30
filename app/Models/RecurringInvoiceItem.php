<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\RecurringInvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $recurring_invoice_id
 * @property int|null $service_id
 * @property string $description
 * @property float $quantity
 * @property Money $unit_price
 * @property float|null $tax_rate
 * @property int $position
 * @property-read BelongsTo<RecurringInvoice, $this> $recurringInvoice
 * @property-read BelongsTo<Service, $this> $service
 */
#[Fillable([
    'service_id',
    'description',
    'quantity',
    'unit_price',
    'tax_rate',
    'position',
])]
#[Appends(['tax_rate_percent'])]
class RecurringInvoiceItem extends Model
{
    /** @use HasFactory<RecurringInvoiceItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'tax_rate' => 'float',
            'unit_price' => Money::class,
            'position' => 'integer',
        ];
    }

    /**
     * Get the schedule the item belongs to.
     *
     * @return BelongsTo<RecurringInvoice, $this>
     */
    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    /**
     * Get the service the item was created from, if any.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the line's tax rate as the percentage people type into forms.
     */
    public function getTaxRatePercentAttribute(): float
    {
        return (float) $this->tax_rate * 100;
    }
}
