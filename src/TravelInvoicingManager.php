<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing;

use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\InvoicePayment;
use Kreetancraft\TravelInvoicing\Models\InvoicingSetting;
use Kreetancraft\TravelInvoicing\Models\Quote;

/**
 * The package's front door, behind the `TravelInvoicing` facade.
 *
 * The facade advertised `createQuote()`, `createInvoice()` and `getSettings()`
 * in its docblock and resolved to `QuotesContract`, which has none of them — so
 * every one of the three documented calls threw BadMethodCallException, while
 * the methods that did work were undocumented. The docblock was the only
 * description of this API, so following it was the one guaranteed way to fail.
 *
 * Rather than correcting the docblock down to what happened to be reachable,
 * this makes the advertised API real. Each method delegates to the contract that
 * owns the work; nothing new is implemented here.
 */
class TravelInvoicingManager
{
    public function __construct(
        protected QuotesContract $quotes,
        protected InvoicesContract $invoices,
        protected InvoicingSettingsContract $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items  line items, as rows in their own table
     */
    public function createQuote(array $data, array $items = []): Quote
    {
        return $this->quotes->create($data, $items);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createInvoice(array $data, array $items = []): Invoice
    {
        return $this->invoices->create($data, $items);
    }

    /**
     * Turn an accepted proposal into a bill. Idempotent: a quote already
     * converted hands back the invoice it already has.
     */
    public function convertQuote(Quote $quote): Invoice
    {
        return $this->invoices->createFromQuote($quote);
    }

    /**
     * Credit an invoice. Idempotent on the reference, so a gateway redelivering
     * a webhook does not credit twice.
     */
    public function recordPayment(
        Invoice $invoice,
        int $amountCents,
        ?string $gateway = null,
        ?string $reference = null,
        ?string $notes = null,
    ): InvoicePayment {
        return $this->invoices->recordPayment($invoice, $amountCents, $gateway, $reference, $notes);
    }

    public function getSettings(): InvoicingSetting
    {
        return $this->settings->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(array $data): InvoicingSetting
    {
        return $this->settings->update($data);
    }

    /**
     * The contracts themselves, for anything this front door does not cover.
     */
    public function quotes(): QuotesContract
    {
        return $this->quotes;
    }

    public function invoices(): InvoicesContract
    {
        return $this->invoices;
    }
}
