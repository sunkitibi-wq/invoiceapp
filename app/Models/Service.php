<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $unit
 * @property string|null $description
 * @property Money $unit_price
 * @property float|null $tax_rate
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HasMany<InvoiceItem, $this> $invoiceItems
 */
#[Fillable([
    'name',
    'unit',
    'description',
    'unit_price',
    'tax_rate',
    'is_active',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => Money::class,
            'tax_rate' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the invoice items created from this service.
     *
     * @return HasMany<InvoiceItem, $this>
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the tax rate as the percentage people type into forms.
     */
    public function getTaxRatePercentAttribute(): float
    {
        return (float) $this->tax_rate * 100;
    }

    /**
     * Scope a query to only include services matching the given term.
     *
     * @param  Builder<Service>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $query->when($term, function (Builder $query, string $term): void {
            $query->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Scope a query to only include active services.
     *
     * @param  Builder<Service>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
