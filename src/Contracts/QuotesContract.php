<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\Quote;

interface QuotesContract
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(array $data, array $items = []): Quote;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function update(Quote $quote, array $data, array $items = []): Quote;

    public function findOrFail(int $id): Quote;

    public function findByToken(string $token): ?Quote;

    public function paginate(?string $search, ?string $status, ?string $sort = '-created_at', int $perPage = 15): LengthAwarePaginator;

    public function send(Quote $quote): Quote;

    public function accept(Quote $quote, ?string $clientNotes = null, bool $autoGenerateInvoice = true): ?Invoice;

    public function reject(Quote $quote, ?string $reason = null): Quote;

    public function delete(int $id): void;
}
