<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Support;

class MoneyFormatter
{
    public static function formatCents(int $amountCents, string $currency = 'USD'): string
    {
        $decimal = $amountCents / 100;
        $currency = strtoupper($currency);

        $symbol = match ($currency) {
            'USD' => '$',
            'NPR' => 'NPR ',
            'EUR' => '€',
            'GBP' => '£',
            'AUD' => 'A$',
            'CAD' => 'C$',
            default => "{$currency} ",
        };

        return $symbol.number_format($decimal, 2);
    }
}
