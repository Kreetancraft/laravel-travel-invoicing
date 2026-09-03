<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued / Awaiting Payment',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Fully Paid',
            self::Overdue => 'Overdue',
            self::Void => 'Void / Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Issued => 'blue',
            self::PartiallyPaid => 'amber',
            self::Paid => 'emerald',
            self::Overdue => 'red',
            self::Void => 'zinc',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }
}
