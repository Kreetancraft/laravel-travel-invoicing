<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Models\Invoice;

/**
 * What this package exposes over HTTP, and what it deliberately does not.
 *
 * It used to ship a REST API — index, store, show, destroy for both invoices and
 * quotes, plus an endpoint that credited an invoice with a payment — on the
 * `api` middleware with every form request answering `authorize(): true`. Anyone
 * who found the URL could list every invoice with buyer names and amounts,
 * create them, delete them, or record money that never arrived.
 *
 * The donor application never had any of that. Its entire public surface is
 * token-scoped: read the document, read its PDF, accept or reject a quote. An
 * invoice is created from a booking, in code, never over HTTP.
 *
 * So the API is gone rather than merely protected. These tests hold that line —
 * if someone adds a create endpoint back, they fail.
 */
function portalInvoice(): Invoice
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

it('exposes no endpoint that lists invoices', function (): void {
    $this->getJson('/api/v1/invoicing/invoices')->assertNotFound();
});

it('exposes no endpoint that creates an invoice', function (): void {
    $this->postJson('/api/v1/invoicing/invoices', [
        'buyer_name' => 'Nobody',
        'buyer_email' => 'nobody@example.com',
        'issue_date' => now()->toDateString(),
        'items' => [['description' => 'Free trek', 'quantity' => 1, 'unit_price_cents' => 0]],
    ])->assertNotFound();

    expect(Invoice::count())->toBe(0);
});

it('exposes no endpoint that deletes an invoice', function (): void {
    $invoice = portalInvoice();

    $this->deleteJson("/api/v1/invoicing/invoices/{$invoice->id}")->assertNotFound();

    expect(Invoice::find($invoice->id))->not->toBeNull();
});

it('exposes no endpoint that credits an invoice with a payment', function (): void {
    // The worst of the ones removed: money recorded by anyone at all. Recording
    // a payment now happens on the invoice screen, authorized against the
    // invoice, or through the gateway listener.
    $invoice = portalInvoice();

    $this->postJson("/api/v1/invoicing/invoices/{$invoice->id}/payments", [
        'amount_cents' => 50000,
        'gateway' => 'cash',
    ])->assertNotFound();

    expect($invoice->fresh()->amount_paid_cents)->toBe(0);
});

it('exposes no endpoint that lists or creates quotes', function (): void {
    $this->getJson('/api/v1/invoicing/quotes')->assertNotFound();
    $this->postJson('/api/v1/invoicing/quotes', [])->assertNotFound();
});

it('still lets a customer open their own invoice by token', function (): void {
    // The surface that remains, and the one the donor keeps: unguessable
    // per-document tokens, so a buyer reaches their own invoice without an
    // account and cannot enumerate anyone else's.
    $invoice = portalInvoice();

    $this->get("/portal/invoices/{$invoice->public_token}")->assertOk();
    $this->get("/portal/invoices/{$invoice->public_token}/pdf")->assertOk();
});

it('still refuses a token that does not exist', function (): void {
    $this->get('/portal/invoices/not-a-real-token')->assertNotFound();
});
