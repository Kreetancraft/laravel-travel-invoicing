<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Http\Controllers\Concerns\ServesDocumentPdf;

class InvoicePdfController extends Controller
{
    use ServesDocumentPdf;

    /**
     * The invoice as a file to keep.
     *
     * Named `show` because that is what routes/public.php asks for; it was
     * `renderPdf`, so this route raised BadMethodCallException and could never
     * be reached at all.
     */
    public function show(string $token, InvoicesContract $invoices, InvoicingSettingsContract $settings): Response|View
    {
        $invoice = $invoices->findByToken($token);

        abort_if(! $invoice, 404, 'Invoice not found.');

        return $this->pdfResponse(
            view: 'travel-invoicing::pdf.tax-invoice',
            data: ['invoice' => $invoice, 'settings' => $settings->get()],
            filename: $invoice->invoice_number.'.pdf',
            storedPath: $invoice->pdf_path,
        );
    }
}
