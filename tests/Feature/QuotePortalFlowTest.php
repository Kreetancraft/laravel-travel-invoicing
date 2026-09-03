<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Quote\CreateQuoteAction;
use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\Quote;

/**
 * What a customer can actually do with the link they were sent.
 *
 * Accepting was impossible. `AcceptQuoteRequest` requires `agreed_terms` to be
 * present and accepted, and the portal form did not contain that field at all —
 * so every Accept failed validation, redirected back with a 302, and left the
 * customer looking at the same page with nothing said. The view rendered neither
 * validation errors nor flash messages, so a failure was indistinguishable from
 * never having clicked. Declining had a route and a controller and no button.
 */
function portalQuote(): Quote
{
    return app(CreateQuoteAction::class)->handle([
        'buyer_name' => 'Darryl Philbin',
        'buyer_email' => 'darryl@example.com',
        'title' => 'Manaslu Circuit — 16 Day Trek',
        'valid_until' => now()->addDays(14)->toDateString(),
        'deposit_percent' => 20,
    ], [
        ['title' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 100000],
    ]);
}

it('offers the customer a way to agree to the terms', function (): void {
    // Without this field on the page the form could never satisfy its own
    // validation rule.
    $quote = portalQuote();

    $this->get("/portal/quotes/{$quote->public_token}")
        ->assertOk()
        ->assertSee('agreed_terms', false)
        ->assertSee('Accept & Confirm Proposal')
        ->assertSee('Decline');
});

it('refuses to accept a quote without agreement, and says so', function (): void {
    $quote = portalQuote();

    $this->from("/portal/quotes/{$quote->public_token}")
        ->post("/portal/quotes/{$quote->public_token}/accept", ['client_notes' => 'Looks good'])
        ->assertSessionHasErrors('agreed_terms');

    expect($quote->fresh()->status)->toBe(QuoteStatus::Draft)
        ->and(Invoice::where('quote_id', $quote->id)->count())->toBe(0);
});

it('accepts the quote and generates the invoice when the terms are agreed', function (): void {
    $quote = portalQuote();

    $this->post("/portal/quotes/{$quote->public_token}/accept", [
        'agreed_terms' => '1',
        'client_notes' => 'Please book the Lukla flight too.',
    ])->assertRedirect("/portal/quotes/{$quote->public_token}");

    $quote->refresh();
    $invoice = Invoice::where('quote_id', $quote->id)->first();

    expect($quote->status)->toBe(QuoteStatus::Accepted)
        ->and($quote->responded_at)->not->toBeNull()
        ->and($invoice)->not->toBeNull()
        ->and($invoice->grand_total_cents)->toBe($quote->grand_total_cents);
});

it('tells the customer the invoice was generated', function (): void {
    // The success message existed but the view never rendered flash messages.
    $quote = portalQuote();

    $this->post("/portal/quotes/{$quote->public_token}/accept", ['agreed_terms' => '1'])
        ->assertSessionHas('success');

    $this->get("/portal/quotes/{$quote->public_token}")
        ->assertOk()
        ->assertSee(Invoice::where('quote_id', $quote->id)->first()->invoice_number);
});

it('lets the customer decline instead', function (): void {
    $quote = portalQuote();

    $this->post("/portal/quotes/{$quote->public_token}/reject", [
        'rejection_reason' => 'Dates no longer work',
    ])->assertRedirect();

    expect($quote->fresh()->status)->toBe(QuoteStatus::Rejected)
        ->and(Invoice::where('quote_id', $quote->id)->count())->toBe(0);
});

it('does not accept a quote that has expired', function (): void {
    $quote = portalQuote();
    $quote->forceFill(['valid_until' => now()->subDay()])->save();

    $this->post("/portal/quotes/{$quote->public_token}/accept", ['agreed_terms' => '1'])
        ->assertStatus(422);

    expect($quote->fresh()->status)->toBe(QuoteStatus::Draft);
});

it('does not accept against a token that does not exist', function (): void {
    $this->post('/portal/quotes/not-a-real-token/accept', ['agreed_terms' => '1'])
        ->assertNotFound();
});
