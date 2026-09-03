<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kreetancraft\TravelInvoicing\Support\MoneyFormatter;

class InvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'transaction_reference',
        'gateway',
        'amount_cents',
        'currency',
        'status',
        'paid_at',
        'notes',
    ];

    public function getTable(): string
    {
        return (string) config('travel-invoicing.tables.invoice_payments', 'invoice_payments');
    }

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

        return $this->belongsTo($invoiceClass, 'invoice_id');
    }

    protected function formattedAmount(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->amount_cents, $this->currency));
    }
}
