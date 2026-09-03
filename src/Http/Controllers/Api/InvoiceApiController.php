<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Http\Requests\RecordPaymentRequest;
use Kreetancraft\TravelInvoicing\Http\Requests\StoreInvoiceRequest;

class InvoiceApiController extends Controller
{
    public function index(Request $request, InvoicesContract $invoices): JsonResponse
    {
        $paginator = $invoices->paginate($request->query('search'), $request->query('status'), (int) $request->query('per_page', 15));

        return response()->json($paginator);
    }

    public function store(StoreInvoiceRequest $request, InvoicesContract $invoices): JsonResponse
    {
        $data = $request->validated();
        $items = (array) ($data['items'] ?? []);
        unset($data['items']);

        $invoice = $invoices->create($data, $items);

        return response()->json($invoice, 201);
    }

    public function show(int $id, InvoicesContract $invoices): JsonResponse
    {
        $invoice = $invoices->findOrFail($id);

        return response()->json($invoice);
    }

    public function recordPayment(int $id, RecordPaymentRequest $request, InvoicesContract $invoices): JsonResponse
    {
        $invoice = $invoices->findOrFail($id);
        $payment = $invoices->recordPayment(
            $invoice,
            (int) $request->input('amount_cents'),
            (string) $request->input('gateway', 'manual'),
            (string) $request->input('reference'),
            (string) $request->input('notes')
        );

        return response()->json($payment, 201);
    }

    public function destroy(int $id, InvoicesContract $invoices): JsonResponse
    {
        $invoices->delete($id);

        return response()->json(['deleted' => true]);
    }
}
