<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Kreetancraft\TravelInvoicing\Database\Factories\InvoiceFactory;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Support\MoneyFormatter;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'public_token',
        'quote_id',
        'customer_id',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'buyer_country',
        'buyer_address',
        'title',
        'currency',
        'subtotal_cents',
        'tax_cents',
        'discount_amount_cents',
        'coupon_code',
        'grand_total_cents',
        'deposit_amount_cents',
        'amount_paid_cents',
        'status',
        'issue_date',
        'due_date',
        'sent_at',
        'paid_at',
        'notes',
        'pdf_path',
        'metadata',
        'created_by',
    ];

    public function getTable(): string
    {
        return (string) config('travel-invoicing.tables.invoices', 'invoices');
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'subtotal_cents' => 'integer',
            'tax_cents' => 'integer',
            'discount_amount_cents' => 'integer',
            'grand_total_cents' => 'integer',
            'deposit_amount_cents' => 'integer',
            'amount_paid_cents' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (empty($invoice->public_token)) {
                $invoice->public_token = Str::random(64);
            }
        });
    }

    public function items(): HasMany
    {
        $itemClass = config('travel-invoicing.models.invoice_item', InvoiceItem::class);

        return $this->hasMany($itemClass, 'invoice_id')->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        $paymentClass = config('travel-invoicing.models.invoice_payment', InvoicePayment::class);

        return $this->hasMany($paymentClass, 'invoice_id')->latest();
    }

    public function quote(): BelongsTo
    {
        $quoteClass = config('travel-invoicing.models.quote', Quote::class);

        return $this->belongsTo($quoteClass, 'quote_id');
    }

    public function customer(): mixed
    {
        $customerClass = config('travel-customers.models.customer');
        if ($customerClass && class_exists($customerClass)) {
            return $this->belongsTo($customerClass, 'customer_id');
        }

        return null;
    }

    public function balanceDueCents(): int
    {
        return (int) max(0, $this->grand_total_cents - $this->amount_paid_cents);
    }

    public function isFullyPaid(): bool
    {
        return $this->amount_paid_cents >= $this->grand_total_cents && $this->grand_total_cents > 0;
    }

    public function isDepositPaid(): bool
    {
        return $this->amount_paid_cents >= $this->deposit_amount_cents && $this->deposit_amount_cents > 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && ! $this->isFullyPaid() && $this->status !== InvoiceStatus::Void;
    }

    public function publicUrl(): string
    {
        $publicPrefix = (string) config('travel-invoicing.routes.public_prefix', 'portal');

        return url("{$publicPrefix}/invoices/{$this->public_token}");
    }

    protected function formattedGrandTotal(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->grand_total_cents, $this->currency));
    }

    protected function formattedAmountPaid(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->amount_paid_cents, $this->currency));
    }

    protected function formattedBalanceDue(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->balanceDueCents(), $this->currency));
    }

    protected function formattedDepositAmount(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->deposit_amount_cents, $this->currency));
    }

    public function scopeSearch(Builder $query, string $term): void
    {
        $query->where(function (Builder $inner) use ($term): void {
            $inner->where('invoice_number', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%")
                ->orWhere('buyer_name', 'like', "%{$term}%")
                ->orWhere('buyer_email', 'like', "%{$term}%");
        });
    }

    public function scopeUnpaid(Builder $query): void
    {
        $query->whereIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value, InvoiceStatus::Overdue->value]);
    }

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }
}
