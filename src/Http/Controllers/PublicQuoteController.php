<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Http\Requests\AcceptQuoteRequest;

class PublicQuoteController extends Controller
{
    public function show(string $token, QuotesContract $quotes): View
    {
        $quote = $quotes->findByToken($token);

        abort_if(! $quote, 404, 'Quote proposal not found or link has expired.');

        return view('travel-invoicing::public.quote-review', [
            'quote' => $quote,
        ]);
    }

    public function accept(string $token, AcceptQuoteRequest $request, QuotesContract $quotes): RedirectResponse
    {
        $quote = $quotes->findByToken($token);

        abort_if(! $quote, 404, 'Quote proposal not found.');
        abort_if($quote->isExpired(), 422, 'This quote has expired.');

        $invoice = $quotes->accept($quote, (string) $request->input('client_notes'));

        return redirect()->route('travel-invoicing.public.quote', ['token' => $token])
            ->with('success', 'Thank you! You have accepted this quote. Invoice #'.$invoice->invoice_number.' has been generated.');
    }

    public function reject(string $token, Request $request, QuotesContract $quotes): RedirectResponse
    {
        $quote = $quotes->findByToken($token);

        abort_if(! $quote, 404, 'Quote proposal not found.');

        $quotes->reject($quote, (string) $request->input('reason', ''));

        return redirect()->route('travel-invoicing.public.quote', ['token' => $token])
            ->with('warning', 'Quote has been declined.');
    }
}
