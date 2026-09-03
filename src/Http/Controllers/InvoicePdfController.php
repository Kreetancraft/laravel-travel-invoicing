<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;

class InvoicePdfController extends Controller
{
    /**
     * The printable invoice.
     *
     * Named `show` because that is what `routes/public.php` asks for. It was
     * `renderPdf`, so every request to this route raised a BadMethodCallException
     * — the page could never have been reached.
     */
    public function show(string $token, InvoicesContract $invoices, InvoicingSettingsContract $settings): View
    {
        $invoice = $invoices->findByToken($token);

        abort_if(! $invoice, 404, 'Invoice not found.');

        return view('travel-invoicing::pdf.tax-invoice', [
            'invoice' => $invoice,
            // The view reads `$settings`. This used to pass `$business` from
            // `travel-invoicing.business`, a config key that does not exist — so
            // the variable the template actually uses was never set.
            'settings' => $settings->get(),
        ]);
    }
}
