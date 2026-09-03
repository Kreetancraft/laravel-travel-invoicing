<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Support\Pdf;

use RuntimeException;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Turn a rendered Blade page into a PDF using spatie/laravel-pdf.
 *
 * That package drives a real headless browser through Browsershot, so the PDF
 * looks like the page a customer sees — the same stylesheet, the same layout —
 * rather than an approximation from an HTML-to-PDF parser. The cost is that the
 * server needs Node and Puppeteer, which is why this is a seam and not baked in:
 * a host without them keeps the HTML view it already had.
 *
 * Point `travel-invoicing.pdf.renderer` at this class to use it, or at anything
 * else exposing `render(string $html): string`.
 */
class SpatiePdfRenderer implements PdfRenderer
{
    public function render(string $html): string
    {
        if (! class_exists(Pdf::class)) {
            throw new RuntimeException(
                'spatie/laravel-pdf is not installed. Run `composer require spatie/laravel-pdf`, '
                .'or point travel-invoicing.pdf.renderer at a different renderer.'
            );
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'invoicing-pdf-').'.pdf';

        try {
            Pdf::html($html)->save($temporaryPath);

            $contents = file_get_contents($temporaryPath);

            if ($contents === false) {
                throw new RuntimeException('The PDF was rendered but could not be read back.');
            }

            return $contents;
        } finally {
            // Whatever happened, do not leave the file behind. These accumulate
            // in the system temp directory on a busy queue worker.
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }
}
