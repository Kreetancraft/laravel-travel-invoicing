<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\InvoicePayment;
use Kreetancraft\TravelInvoicing\Models\Quote;

interface InvoicesContract
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(array $data, array $items = []): Invoice;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function update(Invoice $invoice, array $data, array $items = []): Invoice;

    public function createFromQuote(Quote $quote): Invoice;

    public function findOrFail(int $id): Invoice;

    public function findByToken(string $token): ?Invoice;

    public function paginate(?string $search, ?string $status, ?string $sort = '-created_at', int $perPage = 15): LengthAwarePaginator;

    public function issue(Invoice $invoice): Invoice;

    public function recordPayment(Invoice $invoice, int $amountCents, ?string $gateway = null, ?string $reference = null, ?string $notes = null): InvoicePayment;

    public function void(Invoice $invoice): Invoice;

    public function delete(int $id): void;
}
