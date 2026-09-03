<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Kreetancraft\TravelInvoicing\Database\Factories\QuoteFactory;
use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Support\MoneyFormatter;

class Quote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'quote_reference',
        'public_token',
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
        'discount_note',
        'grand_total_cents',
        'deposit_percent',
        'deposit_amount_cents',
        'status',
        'valid_until',
        'sent_at',
        'responded_at',
        'client_notes',
        'internal_notes',
        'rejection_reason',
        'pdf_path',
        'metadata',
        'created_by',
    ];

    public function getTable(): string
    {
        return (string) config('travel-invoicing.tables.quotes', 'quotes');
    }

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
            'subtotal_cents' => 'integer',
            'tax_cents' => 'integer',
            'discount_amount_cents' => 'integer',
            'grand_total_cents' => 'integer',
            'deposit_percent' => 'integer',
            'deposit_amount_cents' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote): void {
            if (empty($quote->public_token)) {
                $quote->public_token = Str::random(64);
            }
        });
    }

    public function items(): HasMany
    {
        $itemClass = config('travel-invoicing.models.quote_item', QuoteItem::class);

        return $this->hasMany($itemClass, 'quote_id')->orderBy('sort_order');
    }

    public function invoice(): HasOne
    {
        $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

        return $this->hasOne($invoiceClass, 'quote_id');
    }

    public function customer(): mixed
    {
        $customerClass = config('travel-customers.models.customer');
        if ($customerClass && class_exists($customerClass)) {
            return $this->belongsTo($customerClass, 'customer_id');
        }

        return null;
    }

    public function isExpired(): bool
    {
        return $this->valid_until->isPast() && $this->status !== QuoteStatus::Accepted;
    }

    public function publicUrl(): string
    {
        $publicPrefix = (string) config('travel-invoicing.routes.public_prefix', 'portal');

        return url("{$publicPrefix}/quotes/{$this->public_token}");
    }

    protected function formattedGrandTotal(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->grand_total_cents, $this->currency));
    }

    protected function formattedDepositAmount(): Attribute
    {
        return Attribute::get(fn (): string => MoneyFormatter::formatCents($this->deposit_amount_cents, $this->currency));
    }

    public function scopeSearch(Builder $query, string $term): void
    {
        $query->where(function (Builder $inner) use ($term): void {
            $inner->where('quote_reference', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%")
                ->orWhere('buyer_name', 'like', "%{$term}%")
                ->orWhere('buyer_email', 'like', "%{$term}%");
        });
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [QuoteStatus::Draft->value, QuoteStatus::Sent->value]);
    }

    protected static function newFactory(): QuoteFactory
    {
        return QuoteFactory::new();
    }
}
