<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\RecordInvoicePaymentAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\CreateQuoteAction;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;

/**
 * The pages a customer actually opens.
 *
 * Every one of them was broken, and nothing noticed because no test had ever
 * requested one. Three separate faults, each fatal on its own:
 *
 *   - `routes/public.php` pointed at `show()`; the PDF controllers defined
 *     `renderPdf()`, so the route raised BadMethodCallException.
 *   - The controllers passed `$business` from `travel-invoicing.business`, a
 *     config key that does not exist, while the templates read `$settings`.
 *   - Two templates called `$invoice->isPaid()`. The model has `isFullyPaid()`;
 *     `isPaid()` lives on the status enum.
 */
function publicInvoice(): object
{
    return app(CreateInvoiceAction::class)->handle([
        'buyer_name' => 'Angela Martin',
        'buyer_email' => 'angela@dundermifflin.com',
        'title' => 'Annapurna Circuit',
        'status' => InvoiceStatus::Issued,
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 120000],
    ]);
}

it('shows a customer their invoice', function (): void {
    $invoice = publicInvoice();

    $this->get("/portal/invoices/{$invoice->public_token}")
        ->assertOk()
        ->assertSee($invoice->invoice_number);
});

it('renders the printable invoice', function (): void {
    $invoice = publicInvoice();

    $this->get("/portal/invoices/{$invoice->public_token}/pdf")
        ->assertOk()
        ->assertSee($invoice->invoice_number);
});

it('renders the printable proposal', function (): void {
    $quote = app(CreateQuoteAction::class)->handle([
        'buyer_name' => 'Kevin Malone',
        'buyer_email' => 'kevin@dundermifflin.com',
        'title' => 'Poon Hill Trek',
        'valid_until' => now()->addDays(14)->toDateString(),
    ], [
        ['title' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 60000],
    ]);

    $this->get("/portal/quotes/{$quote->public_token}/pdf")
        ->assertOk()
        ->assertSee($quote->quote_reference);
});

it('shows the business details the settings hold', function (): void {
    // `$settings` was never passed, so every one of these rendered as the
    // template's fallback rather than the configured business.
    app(InvoicingSettingsContract::class)
        ->update(['business_name' => 'Kreetancraft Expeditions']);

    $invoice = publicInvoice();

    $this->get("/portal/invoices/{$invoice->public_token}/pdf")
        ->assertOk()
        ->assertSee('Kreetancraft Expeditions');
});

it('does not leak an invoice to a wrong token', function (): void {
    publicInvoice();

    $this->get('/portal/invoices/not-a-real-token')->assertNotFound();
    $this->get('/portal/invoices/not-a-real-token/pdf')->assertNotFound();
});

it('says whether the invoice has been paid', function (): void {
    // The template called a method the model does not have.
    $invoice = publicInvoice();

    app(RecordInvoicePaymentAction::class)
        ->handle($invoice, 120000, 'stripe', 'pi_public_paid');

    $this->get("/portal/invoices/{$invoice->public_token}")->assertOk();

    expect($invoice->fresh()->isFullyPaid())->toBeTrue();
});
