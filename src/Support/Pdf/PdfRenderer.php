<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Support\Pdf;

/**
 * Turns a rendered page into PDF bytes.
 *
 * A one-method seam so the choice of PDF engine belongs to the host. This
 * package renders the Blade view either way; what converts that HTML into a file
 * is somebody else's decision, and hosts differ — a Browsershot renderer needs
 * Node on the server, a DomPDF one does not but understands less CSS.
 *
 * Implementing this interface is optional. `travel-invoicing.pdf.renderer` also
 * accepts any object with a `render()` method, or a closure, so a host can point
 * it at something that never heard of this package.
 */
interface PdfRenderer
{
    /**
     * @param  string  $html  a fully rendered page, styles included
     * @return string the PDF file's bytes
     */
    public function render(string $html): string;
}
