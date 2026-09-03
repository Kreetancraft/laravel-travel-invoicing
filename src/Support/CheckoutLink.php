<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Support;

use Kreetancraft\TravelInvoicing\Models\Invoice;
use Throwable;

/**
 * Where a customer goes to pay.
 *
 * The invoice portal is called `invoice-pay`, and until now there was no way to
 * pay from it — line items, totals, and bank wire text a customer had to act on
 * by hand. Accepting a quote generated an invoice and then the trail went cold.
 *
 * This package does not take payments and should not learn how. It asks the host
 * for a URL and puts a button on it, the same way it asks for a customer or a
 * PDF renderer. Null means no buttons, which is what a host wanting bank
 * transfer only should get.
 *
 * With kreetancraft/laravel-payment-gateway installed, the host writes:
 *
 *     'checkout_url' => fn (Invoice $invoice, string $portion) => route('payment.checkout', [
 *         'payableType' => 'invoice',
 *         'payableId' => $invoice->getKey(),
 *         'amountType' => $portion,
 *     ]),
 */
class CheckoutLink
{
    /**
     * Pay the deposit — what is left of it, if part has already been paid.
     */
    public const DEPOSIT = 'deposit';

    /**
     * Pay everything still outstanding.
     */
    public const BALANCE = 'balance';

    /**
     * The URL to send this customer to, or null when the host takes no online
     * payments.
     */
    public static function for(Invoice $invoice, string $portion = self::BALANCE): ?string
    {
        if (static::amountCentsFor($invoice, $portion) <= 0) {
            return null;
        }

        $resolver = config('travel-invoicing.checkout_url');

        if ($resolver === null) {
            return null;
        }

        try {
            $url = is_callable($resolver)
                ? $resolver($invoice, $portion)
                : (is_string($resolver) ? app($resolver)($invoice, $portion) : null);

            return filled($url) ? (string) $url : null;
        } catch (Throwable) {
            // A misconfigured link must not take the invoice down with it. The
            // customer still sees what they owe and how to pay by transfer.
            return null;
        }
    }

    /**
     * How much this portion comes to, in minor units.
     *
     * The deposit is what remains *of the deposit* — a customer who has paid
     * part of it owes the rest, not the whole thing again — and never more than
     * the invoice's outstanding balance.
     */
    public static function amountCentsFor(Invoice $invoice, string $portion): int
    {
        $balance = $invoice->balanceDueCents();

        if ($portion !== self::DEPOSIT) {
            return $balance;
        }

        return min($balance, max(0, $invoice->deposit_amount_cents - $invoice->amount_paid_cents));
    }

    /**
     * Should the deposit be offered as a separate choice?
     *
     * Only when it is genuinely a smaller first step. Once enough has been paid
     * to cover it, "pay the deposit" and "pay the balance" would be the same
     * button twice.
     */
    public static function offersDeposit(Invoice $invoice): bool
    {
        $deposit = static::amountCentsFor($invoice, self::DEPOSIT);

        return $deposit > 0 && $deposit < $invoice->balanceDueCents();
    }
}
