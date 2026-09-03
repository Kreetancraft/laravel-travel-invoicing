<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Support\Pdf\PdfRenderer;
use Throwable;

/**
 * Render an invoice to a PDF file, off the request.
 *
 * Rendering drives a headless browser, which takes seconds — far too long to
 * make a customer wait while a page loads, and far too long to hold a web worker
 * for. So it happens on the queue, and `invoices.pdf_path` records where the
 * file landed. That column has existed since the first migration and nothing
 * ever wrote to it.
 *
 * The job is deliberately forgiving. A missing renderer is a host that has not
 * chosen one, not a failure; a renderer that throws is logged and swallowed,
 * because an invoice that exists without a PDF is recoverable — re-run the job —
 * while a queue that keeps retrying a browser that will never start is not.
 */
class GenerateInvoicePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Two attempts, not the default. Rendering failures are usually the
     * environment — no Node, no Chromium — and retrying those is just noise.
     */
    public int $tries = 2;

    public function __construct(public int $invoiceId) {}

    public function handle(InvoicingSettingsContract $settings): void
    {
        $renderer = static::renderer();

        if ($renderer === null) {
            // No renderer configured. The HTML view still serves; there is
            // simply no file. This is the default state and not an error.
            return;
        }

        $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

        /** @var Invoice|null $invoice */
        $invoice = $invoiceClass::query()->with('items')->find($this->invoiceId);

        if ($invoice === null) {
            return;
        }

        try {
            $html = View::make('travel-invoicing::pdf.tax-invoice', [
                'invoice' => $invoice,
                'settings' => $settings->get(),
            ])->render();

            $path = static::pathFor($invoice);

            Storage::disk(static::disk())->put($path, $renderer->render($html));

            // Written straight to the column rather than through the model's
            // events: nothing should listen to "the PDF finished".
            $invoiceClass::query()->whereKey($invoice->getKey())->update(['pdf_path' => $path]);
        } catch (Throwable $e) {
            Log::warning('Could not render the invoice PDF.', [
                'invoice' => $invoice->invoice_number,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Where this invoice's file lives on the disk.
     */
    public static function pathFor(Invoice $invoice): string
    {
        $directory = trim((string) config('travel-invoicing.pdf.directory', 'invoices'), '/');

        return "{$directory}/{$invoice->invoice_number}.pdf";
    }

    public static function disk(): string
    {
        return (string) config('travel-invoicing.pdf.disk', 'local');
    }

    /**
     * The configured renderer, or null when the host has not chosen one.
     *
     * Accepts a class name, an object, or a closure — the same shape as the
     * image and customer seams, so there is one thing to learn rather than three.
     */
    public static function renderer(): ?PdfRenderer
    {
        $configured = config('travel-invoicing.pdf.renderer');

        if ($configured === null) {
            return null;
        }

        $instance = is_string($configured) ? app($configured) : $configured;

        if ($instance instanceof PdfRenderer) {
            return $instance;
        }

        // Duck typing, so a host can supply something that never heard of this
        // package's interface.
        if (is_callable($instance) || method_exists($instance, 'render')) {
            return new class($instance) implements PdfRenderer
            {
                public function __construct(private mixed $inner) {}

                public function render(string $html): string
                {
                    return is_callable($this->inner)
                        ? (string) ($this->inner)($html)
                        : (string) $this->inner->render($html);
                }
            };
        }

        return null;
    }
}
