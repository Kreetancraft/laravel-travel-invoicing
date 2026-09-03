<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Http\Requests\StoreQuoteRequest;

class QuoteApiController extends Controller
{
    public function index(Request $request, QuotesContract $quotes): JsonResponse
    {
        $paginator = $quotes->paginate($request->query('search'), $request->query('status'), (int) $request->query('per_page', 15));

        return response()->json($paginator);
    }

    public function store(StoreQuoteRequest $request, QuotesContract $quotes): JsonResponse
    {
        $data = $request->validated();
        $items = (array) ($data['items'] ?? []);
        unset($data['items']);

        $quote = $quotes->create($data, $items);

        return response()->json($quote, 201);
    }

    public function show(int $id, QuotesContract $quotes): JsonResponse
    {
        $quote = $quotes->findOrFail($id);

        return response()->json($quote);
    }

    public function destroy(int $id, QuotesContract $quotes): JsonResponse
    {
        $quotes->delete($id);

        return response()->json(['deleted' => true]);
    }
}
