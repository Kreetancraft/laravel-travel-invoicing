<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Kreetancraft\TravelInvoicing\Actions\Quote\AcceptQuoteAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\CreateQuoteAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\RejectQuoteAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\SendQuoteAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\UpdateQuoteAction;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\Quote;

class QuotesRepository implements QuotesContract
{
    public function __construct(
        protected CreateQuoteAction $createQuote,
        protected UpdateQuoteAction $updateQuote,
        protected SendQuoteAction $sendQuote,
        protected AcceptQuoteAction $acceptQuote,
        protected RejectQuoteAction $rejectQuote,
    ) {}

    public function create(array $data, array $items = []): Quote
    {
        return $this->createQuote->handle($data, $items);
    }

    public function update(Quote $quote, array $data, array $items = []): Quote
    {
        return $this->updateQuote->handle($quote, $data, $items);
    }

    public function findOrFail(int $id): Quote
    {
        $quoteClass = config('travel-invoicing.models.quote', Quote::class);

        return $quoteClass::with(['items', 'invoice'])->findOrFail($id);
    }

    public function findByToken(string $token): ?Quote
    {
        $quoteClass = config('travel-invoicing.models.quote', Quote::class);

        return $quoteClass::with('items')->where('public_token', $token)->first();
    }

    public function paginate(?string $search, ?string $status, ?string $sort = '-created_at', int $perPage = 15): LengthAwarePaginator
    {
        $quoteClass = config('travel-invoicing.models.quote', Quote::class);
        $allowed = ['created_at', 'valid_until', 'grand_total_cents', 'status', 'quote_reference'];
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');
        if (! in_array($field, $allowed, true)) {
            $field = 'created_at';
            $direction = 'desc';
        }
        $escaped = $search !== null ? addcslashes($search, '%_') : null;

        return $quoteClass::query()
            ->with('items')
            ->when(filled($escaped), fn (Builder $q) => $q->search((string) $escaped))
            ->when(filled($status), fn (Builder $q) => $q->where('status', $status))
            ->orderBy($field, $direction)
            ->paginate($perPage);
    }

    public function send(Quote $quote): Quote
    {
        return $this->sendQuote->handle($quote);
    }

    public function accept(Quote $quote, ?string $clientNotes = null, bool $autoGenerateInvoice = true): ?Invoice
    {
        return $this->acceptQuote->handle($quote, $clientNotes, $autoGenerateInvoice);
    }

    public function reject(Quote $quote, ?string $reason = null): Quote
    {
        return $this->rejectQuote->handle($quote, $reason);
    }

    public function delete(int $id): void
    {
        $quote = $this->findOrFail($id);
        $quote->delete();
    }
}
