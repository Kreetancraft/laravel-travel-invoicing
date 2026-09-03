<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kreetancraft\TravelInvoicing\Database\Factories\InvoiceItemFactory;
use Kreetancraft\TravelInvoicing\Support\MoneyFormatter;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price_cents',
        'total_price_cents',
        'sort_order',
    ];

    public function getTable(): string
    {
        return (string) config('travel-invoicing.tables.invoice_items', 'invoice_items');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'total_price_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

        return $this->belongsTo($invoiceClass, 'invoice_id');
    }

    protected function formattedUnitPrice(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->unit_price_cents, $this->invoice?->currency ?? 'USD'));
    }

    protected function formattedTotalPrice(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->total_price_cents, $this->invoice?->currency ?? 'USD'));
    }

    protected static function newFactory(): InvoiceItemFactory
    {
        return InvoiceItemFactory::new();
    }
}
