<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kreetancraft\TravelInvoicing\Database\Factories\QuoteItemFactory;
use Kreetancraft\TravelInvoicing\Support\MoneyFormatter;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'title',
        'description',
        'quantity',
        'unit_price_cents',
        'total_price_cents',
        'sort_order',
        'metadata',
    ];

    public function getTable(): string
    {
        return (string) config('travel-invoicing.tables.quote_items', 'quote_items');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'total_price_cents' => 'integer',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function quote(): BelongsTo
    {
        $quoteClass = config('travel-invoicing.models.quote', Quote::class);

        return $this->belongsTo($quoteClass, 'quote_id');
    }

    protected function formattedUnitPrice(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->unit_price_cents, $this->quote?->currency ?? 'USD'));
    }

    protected function formattedTotalPrice(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->total_price_cents, $this->quote?->currency ?? 'USD'));
    }

    protected static function newFactory(): QuoteItemFactory
    {
        return QuoteItemFactory::new();
    }
}
