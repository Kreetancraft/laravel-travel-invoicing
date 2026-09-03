<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Controllers\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View as ViewFactory;
use Kreetancraft\TravelInvoicing\Jobs\GenerateInvoicePdfJob;
use Throwable;

/**
 * Hand back a PDF file, or the printable page when there is no way to make one.
 *
 * The `/pdf` routes returned a Blade view, so "Download PDF" opened the invoice
 * on screen — a printable page, not a file. That was all the package could do
 * before: it had no renderer.
 *
 * Now there usually is one, so the order is:
 *
 *   1. A file already rendered by the queue — send it, no work done here.
 *   2. A renderer configured but no file yet — render it now. Slower, but the
 *      person is waiting for a download and expects to wait a moment.
 *   3. No renderer — fall back to the printable page, which is what this package
 *      did before and is still better than an error.
 */
trait ServesDocumentPdf
{
    /**
     * @param  array<string, mixed>  $data  what the view needs
     */
    protected function pdfResponse(string $view, array $data, string $filename, ?string $storedPath = null): Response|View
    {
        if (filled($storedPath) && Storage::disk(GenerateInvoicePdfJob::disk())->exists($storedPath)) {
            return $this->download(
                Storage::disk(GenerateInvoicePdfJob::disk())->get($storedPath),
                $filename
            );
        }

        $renderer = GenerateInvoicePdfJob::renderer();
        $page = ViewFactory::make($view, $data);

        if ($renderer === null) {
            return $page;
        }

        try {
            return $this->download($renderer->render($page->render()), $filename);
        } catch (Throwable) {
            // Rendering failed — a missing browser, most likely. Showing the
            // printable page is far better than showing the customer an error.
            return $page;
        }
    }

    protected function download(string $bytes, string $filename): Response
    {
        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            // `attachment`, so the browser saves it instead of displaying it.
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
