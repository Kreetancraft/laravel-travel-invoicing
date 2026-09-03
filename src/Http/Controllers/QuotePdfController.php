<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;

class QuotePdfController extends Controller
{
    /**
     * The printable proposal. See InvoicePdfController for why this is `show`.
     */
    public function show(string $token, QuotesContract $quotes, InvoicingSettingsContract $settings): View
    {
        $quote = $quotes->findByToken($token);

        abort_if(! $quote, 404, 'Quote not found.');

        return view('travel-invoicing::pdf.quote-proposal', [
            'quote' => $quote,
            'settings' => $settings->get(),
        ]);
    }
}
