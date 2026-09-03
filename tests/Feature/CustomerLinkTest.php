<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\ConvertQuoteToInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\CreateQuoteAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;

/**
 * Linking a document to the customer it belongs to.
 *
 * `customer_id` has been on both tables from the first migration and nothing
 * ever filled it in, so the same buyer's five invoices had no connection to each
 * other and nothing could answer what a customer had spent.
 *
 * The customer package is reached the way the media package is — a class named
 * in config, called by the method it happens to have — so neither package
 * imports the other, and a host with no customer package is unaffected.
 */
function invoiceWithBuyer(string $email, string $name = 'Kelly Kapoor'): object
{
    return app(CreateInvoiceAction::class)->handle([
        'buyer_name' => $name,
        'buyer_email' => $email,
        'status' => InvoiceStatus::Issued,
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 50000],
    ]);
}

/**
 * Stands in for laravel-travel-customers, without importing it.
 */
function fakeCustomerResolver(): object
{
    return new class
    {
        /** @var array<string, int> */
        public array $seen = [];

        public int $nextId = 100;

        public function findOrCreateByEmail(string $email, array $attributes = []): object
        {
            $id = $this->seen[$email] ??= $this->nextId++;

            return new class($id)
            {
                public function __construct(public int $id) {}

                public function getKey(): int
                {
                    return $this->id;
                }
            };
        }
    };
}

it('leaves the customer unset when the host has no customer package', function (): void {
    config()->set('travel-invoicing.customer_resolver', null);

    expect(invoiceWithBuyer('kelly@example.com')->customer_id)->toBeNull();
});

it('links an invoice to the customer behind the buyer email', function (): void {
    config()->set('travel-invoicing.customer_resolver', fakeCustomerResolver());

    expect(invoiceWithBuyer('kelly@example.com')->customer_id)->toBe(100);
});

it('gives the same buyer the same customer across documents', function (): void {
    // The whole point: five invoices for one person hang together.
    config()->set('travel-invoicing.customer_resolver', fakeCustomerResolver());

    $first = invoiceWithBuyer('ryan@example.com');
    $second = invoiceWithBuyer('ryan@example.com');
    $other = invoiceWithBuyer('someone-else@example.com');

    expect($second->customer_id)->toBe($first->customer_id)
        ->and($other->customer_id)->not->toBe($first->customer_id);
});

it('links quotes too, and carries it through to the invoice', function (): void {
    config()->set('travel-invoicing.customer_resolver', fakeCustomerResolver());

    $quote = app(CreateQuoteAction::class)->handle([
        'buyer_name' => 'Phyllis Vance',
        'buyer_email' => 'phyllis@example.com',
        'title' => 'Langtang Trek',
        'valid_until' => now()->addDays(14)->toDateString(),
    ], [
        ['title' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 90000],
    ]);

    $invoice = app(ConvertQuoteToInvoiceAction::class)->handle($quote);

    expect($quote->customer_id)->not->toBeNull()
        ->and($invoice->customer_id)->toBe($quote->customer_id);
});

it('does not overrule a customer the caller already chose', function (): void {
    config()->set('travel-invoicing.customer_resolver', fakeCustomerResolver());

    $invoice = app(CreateInvoiceAction::class)->handle([
        'buyer_name' => 'Creed Bratton',
        'buyer_email' => 'creed@example.com',
        'customer_id' => 42,
        'status' => InvoiceStatus::Issued,
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 50000],
    ]);

    expect($invoice->customer_id)->toBe(42);
});

it('still issues the invoice when the customer package throws', function (): void {
    // Linking is a convenience. A customer package mid-migration must not stop
    // an invoice being issued — the document bills against its own snapshot.
    config()->set('travel-invoicing.customer_resolver', new class
    {
        public function findOrCreateByEmail(string $email, array $attributes = []): object
        {
            throw new RuntimeException('customers table is missing');
        }
    });

    $invoice = invoiceWithBuyer('angela@example.com');

    expect($invoice->exists)->toBeTrue()
        ->and($invoice->customer_id)->toBeNull();
});

it('accepts a closure as the resolver', function (): void {
    config()->set('travel-invoicing.customer_resolver', fn (string $email, array $attributes): int => 7);

    expect(invoiceWithBuyer('anyone@example.com')->customer_id)->toBe(7);
});
