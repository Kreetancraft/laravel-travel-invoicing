<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\IssueInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\RecordInvoicePaymentAction;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Jobs\GenerateInvoicePdfJob;
use Kreetancraft\TravelInvoicing\Mail\InvoiceIssuedMail;
use Kreetancraft\TravelInvoicing\Mail\PaymentReceiptMail;
use Kreetancraft\TravelInvoicing\Models\Invoice;

/**
 * What goes out, when, and how many times.
 *
 * The rule this file exists to hold: the invoice is the demand for payment and
 * is sent once. Every payment gets its own receipt. Re-sending the invoice after
 * a deposit would put two documents in the customer's inbox both claiming to be
 * the bill, which is how somebody pays twice.
 */
function draftInvoice(int $totalCents = 150000): Invoice
{
    return app(CreateInvoiceAction::class)->handle([
        'buyer_name' => 'Jim Halpert',
        'buyer_email' => 'jim@example.com',
        'title' => 'Everest Base Camp',
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => $totalCents],
    ]);
}

it('renders the pdf on the queue, not in the request', function (): void {
    // Driving a headless browser takes seconds. No customer waits for that.
    Queue::fake();
    config()->set('travel-invoicing.pdf.renderer', fn (string $html): string => '%PDF-fake');

    app(IssueInvoiceAction::class)->handle(draftInvoice());

    Queue::assertPushed(GenerateInvoicePdfJob::class);
});

it('queues no rendering when the host has chosen no renderer', function (): void {
    // The default. The printable HTML view still serves.
    Queue::fake();
    config()->set('travel-invoicing.pdf.renderer', null);

    app(IssueInvoiceAction::class)->handle(draftInvoice());

    Queue::assertNotPushed(GenerateInvoicePdfJob::class);
});

it('writes the file and records where it went', function (): void {
    // `pdf_path` has been on the table since the first migration and nothing
    // ever wrote to it.
    Storage::fake('local');
    config()->set('travel-invoicing.pdf.renderer', fn (string $html): string => '%PDF-1.4 fake bytes');

    $invoice = draftInvoice();
    (new GenerateInvoicePdfJob($invoice->id))->handle(
        app(InvoicingSettingsContract::class)
    );

    $invoice->refresh();

    expect($invoice->pdf_path)->toBe("invoices/{$invoice->invoice_number}.pdf")
        ->and(Storage::disk('local')->exists($invoice->pdf_path))->toBeTrue();
});

it('does not fail the queue when the renderer breaks', function (): void {
    // A missing Chromium is an environment problem. Retrying it forever is
    // noise, and an invoice without a PDF is recoverable.
    Storage::fake('local');
    config()->set('travel-invoicing.pdf.renderer', function (string $html): string {
        throw new RuntimeException('Could not start the browser');
    });

    $invoice = draftInvoice();

    (new GenerateInvoicePdfJob($invoice->id))->handle(
        app(InvoicingSettingsContract::class)
    );

    // Getting here at all is the assertion: the job swallowed it.
    expect($invoice->fresh()->pdf_path)->toBeNull();
});

it('sends no email at all unless the host turns it on', function (): void {
    // A package that quietly emails customers on install is worse than one that
    // does not.
    Mail::fake();
    config()->set('travel-invoicing.mail.enabled', false);

    $invoice = app(IssueInvoiceAction::class)->handle(draftInvoice());
    app(RecordInvoicePaymentAction::class)->handle($invoice, 50000, 'stripe', 'pi_quiet');

    Mail::assertNothingSent();
});

it('emails the invoice once when it is issued', function (): void {
    Mail::fake();
    config()->set('travel-invoicing.mail.enabled', true);

    app(IssueInvoiceAction::class)->handle(draftInvoice());

    Mail::assertQueued(InvoiceIssuedMail::class, 1);
});

it('sends a receipt for each payment, and never the invoice again', function (): void {
    // The heart of it: a deposit and a balance are two receipts and one invoice.
    Mail::fake();
    config()->set('travel-invoicing.mail.enabled', true);

    $invoice = app(IssueInvoiceAction::class)->handle(draftInvoice(150000));
    $record = app(RecordInvoicePaymentAction::class);

    $record->handle($invoice, 30000, 'stripe', 'pi_deposit');
    $record->handle($invoice->fresh(), 120000, 'stripe', 'pi_balance');

    Mail::assertQueued(PaymentReceiptMail::class, 2);
    Mail::assertQueued(InvoiceIssuedMail::class, 1);
});

it('does not send a second receipt for a redelivered webhook', function (): void {
    Mail::fake();
    config()->set('travel-invoicing.mail.enabled', true);

    $invoice = app(IssueInvoiceAction::class)->handle(draftInvoice());
    $record = app(RecordInvoicePaymentAction::class);

    $record->handle($invoice, 50000, 'stripe', 'pi_repeat');
    $record->handle($invoice->fresh(), 50000, 'stripe', 'pi_repeat');

    Mail::assertQueued(PaymentReceiptMail::class, 1);
});

it('tells the customer what is left to pay', function (): void {
    Mail::fake();
    config()->set('travel-invoicing.mail.enabled', true);

    $invoice = app(IssueInvoiceAction::class)->handle(draftInvoice(150000));
    $payment = app(RecordInvoicePaymentAction::class)->handle($invoice, 30000, 'stripe', 'pi_partial');

    $rendered = (new PaymentReceiptMail($invoice->fresh(), $payment))->render();

    expect($rendered)->toContain($invoice->invoice_number)
        ->and($rendered)->toContain('1,200.00');
});

it('does not email a buyer with no address', function (): void {
    Mail::fake();
    config()->set('travel-invoicing.mail.enabled', true);

    $invoice = app(CreateInvoiceAction::class)->handle([
        'buyer_name' => 'Walk-in',
        'buyer_email' => '',
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 50000],
    ]);

    app(IssueInvoiceAction::class)->handle($invoice);

    Mail::assertNothingQueued();
});

it('marks an invoice overdue once it is past its due date', function (): void {
    // The status existed and nothing wrote it, so no invoice ever reached it.
    $invoice = app(IssueInvoiceAction::class)->handle(draftInvoice());
    $invoice->forceFill(['due_date' => now()->subDays(3)])->save();

    $this->artisan('invoicing:mark-overdue')->assertSuccessful();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Overdue);
});

it('leaves settled and cancelled invoices alone', function (): void {
    // An invoice paid late is not overdue afterwards, and a void one is not owed.
    $paid = app(IssueInvoiceAction::class)->handle(draftInvoice(50000));
    $paid->forceFill(['due_date' => now()->subDays(3)])->save();
    app(RecordInvoicePaymentAction::class)->handle($paid, 50000, 'cash', 'r1');

    $void = app(IssueInvoiceAction::class)->handle(draftInvoice());
    $void->forceFill(['due_date' => now()->subDays(3), 'status' => InvoiceStatus::Void])->save();

    $this->artisan('invoicing:mark-overdue')->assertSuccessful();

    expect($paid->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($void->fresh()->status)->toBe(InvoiceStatus::Void);
});
