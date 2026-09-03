<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;

class PublicInvoiceController extends Controller
{
    /**
     * The invoice as the customer sees it, reached by its public token.
     */
    public function show(string $token, InvoicesContract $invoices, InvoicingSettingsContract $settings): View
    {
        $invoice = $invoices->findByToken($token);

        abort_if(! $invoice, 404, 'Invoice not found or link has expired.');

        return view('travel-invoicing::public.invoice-pay', [
            'invoice' => $invoice,
            // Never passed before, though the template reads it.
            'settings' => $settings->get(),
        ]);
    }
}
