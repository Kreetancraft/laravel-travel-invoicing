# Laravel Travel Invoicing

A unified, modern travel billing and commercial proposal package for Laravel combining **Quotes / Commercial Proposals** and **Invoices / Tax Bills**, with deposit milestone schedules, PDF rendering with dynamic QR code verification, and secure client self-service portals.

---

## 🌟 Key Features

* **Concurrency-Safe Sequential Numbering**: Dedicated locked counter table generating audit-ready gap-free sequences (`QT-2026-0001`, `INV-2026-0001`).
* **Quote $\rightarrow$ Invoice 1-Click State Machine**: Converts approved proposals directly into official tax invoices with agreed pricing.
* **Trek Deposit & Milestone Schedules**: Upfront deposit (e.g. 20%) vs. final balance tracking with automatic status transitions (`Draft`, `Issued`, `PartiallyPaid`, `Paid`, `Overdue`, `Void`).
* **Integer Cents Precision**: High-precision financial calculations across multiple currencies (`USD`, `NPR`, `EUR`, `GBP`, `AUD`, `CAD`).
* **Public Client Self-Service Portal**: High-entropy token URL (`/quotes/{token}`, `/invoices/{token}`) for review, digital acceptance, and online payment.
* **Branded PDF Generation**: Clean Blade templates with dynamic QR codes for authentic verification.
* **Cross-Package Compatibility**: Integrates with `laravel-travel-customers`, `laravel-media-manager`, `laravel-user-management` and `laravel-payment-gateway` without depending on any of them — see [Taking payments](#taking-payments).

---

## 🚀 Installation

```bash
composer require kreetancraft/laravel-travel-invoicing
php artisan migrate
```

Publish configuration:

```bash
php artisan vendor:publish --tag=travel-invoicing-config
```


## Taking payments

This package does not depend on a payment package, and a payment package does not
depend on this one. They meet in two places you wire up yourself.

### An invoice that can be paid

The payment package will only charge something that implements its `Payable`
contract, and it checks the interface for real rather than duck-typing it. So the
invoice model your host uses has to implement it. That is what
`travel-invoicing.models.invoice` is for:

```php
namespace App\Models;

use Kreetancraft\PaymentGateway\Contracts\Payable;
use Kreetancraft\TravelInvoicing\Models\Invoice as BaseInvoice;

class Invoice extends BaseInvoice implements Payable
{
    public function paymentAmountCents(): int
    {
        // What is left, not the total — a part-paid invoice must not be
        // charged its full value again.
        return $this->balanceDueCents();
    }

    public function paymentCurrency(): string
    {
        return $this->currency;
    }

    public function paymentReference(): string
    {
        return $this->invoice_number;
    }

    public function paymentDescription(): ?string
    {
        return $this->title;
    }
}
```

Then point both packages at it:

```php
// config/travel-invoicing.php
'models' => ['invoice' => \App\Models\Invoice::class],

// config/payment-gateway.php
'payables' => ['invoice' => \App\Models\Invoice::class],
```

Nothing else is needed. When the payment settles, `RecordGatewayPayment` credits
the invoice, moves it to `partially_paid` or `paid`, and fires `InvoicePaid`.
Recording is idempotent on the payment's reference, so a webhook delivered twice
— which is routine — credits the invoice once.

To listen for something other than `Kreetancraft\PaymentGateway\Events\PaymentSucceeded`,
or to handle payments yourself, change or null out
`travel-invoicing.payment_succeeded_event`.

### Paying first and invoicing afterwards

If the customer pays before an invoice exists — an e-commerce order, or a booking
deposit — then the thing being paid for is the booking, not an invoice, and this
package cannot know how to turn a booking into line items. Write that listener in
your application:

```php
use Kreetancraft\PaymentGateway\Events\PaymentSucceeded;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;

class CreateInvoiceForPaidBooking
{
    public function __construct(private InvoicesContract $invoices) {}

    public function handle(PaymentSucceeded $event): void
    {
        $booking = $event->payment->payable;

        if (! $booking instanceof Booking) {
            return;
        }

        // One invoice per booking, whatever happens upstream.
        $invoice = Invoice::firstWhere('booking_id', $booking->id) ?? $this->invoices->create([
            'buyer_name' => $booking->customer_name,
            'buyer_email' => $booking->customer_email,
            'status' => InvoiceStatus::Issued,
            'issue_date' => now()->toDateString(),
        ], $booking->toInvoiceItems());

        $this->invoices->recordPayment(
            $invoice,
            $event->payment->amount_cents,
            $event->payment->gateway,
            $event->payment->reference,
        );
    }
}
```

Two things that matter and are easy to miss:

* **Guard the invoice, not just the payment.** `recordPayment` is idempotent, but
  invoice *creation* is yours to make safe — a redelivered webhook must find the
  existing invoice rather than mint a second one. A unique index on the column
  holding the booking id is the reliable way.
* **Never fulfil from the return page.** A buyer can pay and lose their connection
  before the redirect lands. `PaymentSucceeded` fires wherever the payment
  settles, including from the webhook and the reconcile sweep, which is why it is
  the right thing to hang this on.
