<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Sent => 'blue',
            self::Accepted => 'emerald',
            self::Rejected => 'red',
            self::Expired => 'amber',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Accepted, self::Rejected, self::Expired], true);
    }
}
