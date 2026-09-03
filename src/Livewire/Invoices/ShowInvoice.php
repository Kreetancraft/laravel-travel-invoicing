<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Livewire\Invoices;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Layout;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Livewire\Attributes\Title;
use Livewire\Component;

class ShowInvoice extends Component
{
    public int $invoiceId;

    // Record Payment form properties
    /**
     * The amount as a person writes it — 3240.00, not 324000.
     *
     * The field used to be bound to cents, so recording a payment meant doing
     * the arithmetic in your head and typing a number three orders of magnitude
     * larger than the one on the invoice. One slip is a payment a hundred times
     * too big.
     */
    public string $paymentAmount = '0.00';

    public string $paymentGateway = 'bank_transfer';

    public ?string $paymentReference = null;

    public ?string $paymentNotes = null;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('view', $invoice);
        $this->invoiceId = (int) $invoice->id;

        $this->paymentAmount = number_format($invoice->balanceDueCents() / 100, 2, '.', '');
    }

    public function issueInvoice(InvoicesContract $invoices): void
    {
        $invoice = $invoices->findOrFail($this->invoiceId);
        $this->authorize('update', $invoice);

        $invoices->issue($invoice);

        Flux::toast(variant: 'success', text: __('Invoice issued.'));
    }

    public function voidInvoice(InvoicesContract $invoices): void
    {
        $invoice = $invoices->findOrFail($this->invoiceId);
        $this->authorize('update', $invoice);

        $invoices->void($invoice);

        Flux::toast(variant: 'warning', text: __('Invoice marked as void.'));
    }

    /**
     * A reference for a payment nobody else numbered.
     *
     * A gateway payment arrives with its own reference. Cash across a counter or
     * a cheque in the post does not, and asking an admin to invent one means
     * either a blank field or a different scheme every time. A generated one is
     * unique, sortable and says how it was taken.
     *
     * It also matters beyond tidiness: recording is idempotent on this
     * reference, so a payment without one cannot be recognised as a repeat if
     * the form is submitted twice.
     */
    public function generateReference(): string
    {
        $prefix = match ($this->paymentGateway) {
            'bank_transfer' => 'WIRE',
            'cash' => 'CASH',
            'cheque' => 'CHQ',
            default => 'MAN',
        };

        return $prefix.'-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
    }

    /**
     * Keep the suggested reference honest when the method changes.
     */
    public function updatedPaymentGateway(): void
    {
        $this->paymentReference = $this->generateReference();
    }

    public function openRecordPaymentModal(): void
    {
        $this->paymentReference = $this->generateReference();

        Flux::modal('record-payment-modal')->show();
    }

    /**
     * Methods a person records by hand.
     *
     * Gateway payments are deliberately absent. Stripe and the bank record
     * themselves when their webhook lands, so choosing one here would invent a
     * payment with no gateway reference — and then the real webhook arrives and
     * records it again. Money counted twice on the invoice, and no way to tell
     * which row was the real one.
     *
     * @return array<string, string>
     */
    public function paymentMethods(): array
    {
        return [
            'bank_transfer' => __('Bank wire transfer'),
            'cash' => __('Cash / in person'),
            'cheque' => __('Cheque'),
            'manual' => __('Other'),
        ];
    }

    public function recordPayment(InvoicesContract $invoices): void
    {
        $invoice = $invoices->findOrFail($this->invoiceId);
        $this->authorize('update', $invoice);

        $balanceDue = $invoice->balanceDueCents();

        $this->validate([
            'paymentAmount' => ['required', 'numeric', 'gt:0', 'max:'.($balanceDue / 100)],
            'paymentGateway' => ['required', 'string', Rule::in(array_keys($this->paymentMethods()))],
            'paymentReference' => ['nullable', 'string', 'max:100'],
            'paymentNotes' => ['nullable', 'string', 'max:1000'],
        ], [
            // An invoice cannot be overpaid. Saying so plainly beats "the
            // paymentAmount may not be greater than 3240".
            'paymentAmount.max' => __('That is more than the :balance still outstanding.', [
                'balance' => $invoice->formatted_balance_due,
            ]),
            'paymentAmount.gt' => __('Enter an amount greater than zero.'),
        ]);

        $invoices->recordPayment(
            $invoice,
            (int) round(((float) $this->paymentAmount) * 100),
            $this->paymentGateway,
            // Blank if the admin cleared it. Every payment gets one regardless,
            // because it is what makes a double submission recognisable.
            filled($this->paymentReference) ? $this->paymentReference : $this->generateReference(),
            $this->paymentNotes
        );

        // Reset to whatever is left, so reopening the modal offers the new
        // balance rather than the amount just paid.
        $this->paymentAmount = number_format($invoice->fresh()->balanceDueCents() / 100, 2, '.', '');

        $this->reset(['paymentReference', 'paymentNotes']);
        Flux::modal('record-payment-modal')->close();
        Flux::toast(variant: 'success', text: __('Payment recorded successfully.'));
    }

    #[Title('Invoice Details')]
    public function render(InvoicesContract $invoices): View
    {
        $invoice = $invoices->findOrFail($this->invoiceId);

        return view('travel-invoicing::livewire.invoices.show-invoice', [
            'invoice' => $invoice,
        ])->layout(Layout::admin());
    }
}
