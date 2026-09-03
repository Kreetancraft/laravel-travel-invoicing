<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Http\Controllers\Concerns\ServesDocumentPdf;

class QuotePdfController extends Controller
{
    use ServesDocumentPdf;

    /**
     * The proposal as a file to keep. See InvoicePdfController for why `show`.
     */
    public function show(string $token, QuotesContract $quotes, InvoicingSettingsContract $settings): Response|View
    {
        $quote = $quotes->findByToken($token);

        abort_if(! $quote, 404, 'Quote not found.');

        return $this->pdfResponse(
            view: 'travel-invoicing::pdf.quote-proposal',
            data: ['quote' => $quote, 'settings' => $settings->get()],
            filename: $quote->quote_reference.'.pdf',
            storedPath: $quote->pdf_path,
        );
    }
}
