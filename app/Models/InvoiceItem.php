<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int|null $service_id
 * @property string $description
 * @property float $quantity
 * @property Money $unit_price
 * @property float|null $tax_rate
 * @property Money $subtotal
 * @property Money $tax_amount
 * @property Money $total
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BelongsTo<Invoice, $this> $invoice
 * @property-read BelongsTo<Service, $this> $service
 */
#[Fillable([
    'service_id',
    'description',
    'quantity',
    'unit_price',
    'tax_rate',
    'subtotal',
    'tax_amount',
    'total',
    'position',
])]
#[Appends(['tax_rate_percent'])]
class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
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
            'subtotal' => Money::class,
            'tax_amount' => Money::class,
            'total' => Money::class,
            'position' => 'integer',
        ];
    }

    /**
     * Get the invoice the item belongs to.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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
