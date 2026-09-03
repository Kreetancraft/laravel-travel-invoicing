<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Models\Invoice;

/**
 * Who may reach the API.
 *
 * Every one of these endpoints is staff work, and none of them was protected:
 * the group carried only `api` middleware and every form request answered
 * `authorize(): true`. Anyone who found the URL could list every invoice with
 * buyer names and amounts, create invoices, delete them, or credit one with a
 * payment that never happened.
 *
 * The donor application never exposed any of this publicly — its only
 * unauthenticated invoice routes are token-scoped reads and a checkout start.
 */
function existingInvoice(): Invoice
{
    return app(CreateInvoiceAction::class)->handle([
        'buyer_name' => 'Toby Flenderson',
        'buyer_email' => 'toby@dundermifflin.com',
        'status' => InvoiceStatus::Issued,
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 50000],
    ]);
}

it('does not let a stranger list the invoices', function (): void {
    existingInvoice();

    $this->getJson('/api/v1/invoicing/invoices')->assertUnauthorized();
});

it('does not let a stranger create an invoice', function (): void {
    $this->postJson('/api/v1/invoicing/invoices', [
        'buyer_name' => 'Nobody',
        'buyer_email' => 'nobody@example.com',
        'issue_date' => now()->toDateString(),
        'items' => [
            ['description' => 'Free trek', 'quantity' => 1, 'unit_price_cents' => 0],
        ],
    ])->assertUnauthorized();

    expect(Invoice::count())->toBe(0);
});

it('does not let a stranger read one invoice', function (): void {
    $invoice = existingInvoice();

    $this->getJson("/api/v1/invoicing/invoices/{$invoice->id}")->assertUnauthorized();
});

it('does not let a stranger delete an invoice', function (): void {
    $invoice = existingInvoice();

    $this->deleteJson("/api/v1/invoicing/invoices/{$invoice->id}")->assertUnauthorized();

    expect(Invoice::find($invoice->id))->not->toBeNull();
});

it('does not let a stranger credit an invoice with a payment', function (): void {
    // The worst of them: money recorded against an invoice by anyone at all.
    $invoice = existingInvoice();

    $this->postJson("/api/v1/invoicing/invoices/{$invoice->id}/payments", [
        'amount_cents' => 50000,
        'gateway' => 'cash',
    ])->assertUnauthorized();

    expect($invoice->fresh()->amount_paid_cents)->toBe(0)
        ->and($invoice->fresh()->status)->not->toBe(InvoiceStatus::Paid);
});

it('does not let a stranger list or create quotes', function (): void {
    $this->getJson('/api/v1/invoicing/quotes')->assertUnauthorized();
    $this->postJson('/api/v1/invoicing/quotes', [])->assertUnauthorized();
});

it('still answers the health check without a login', function (): void {
    // Deliberately open: it reveals nothing and monitoring needs it.
    $this->getJson('/api/v1/invoicing/health')
        ->assertOk()
        ->assertJson(['status' => 'ok']);
});

it('still lets a customer open their own invoice by token', function (): void {
    // The customer-facing path is not behind auth and must not be — it is
    // reached by an unguessable per-document token instead.
    $invoice = existingInvoice();

    $this->get("/portal/invoices/{$invoice->public_token}")->assertOk();
});
