<?php

declare(strict_types=1);
use Kreetancraft\PaymentGateway\Events\PaymentSucceeded;
use Kreetancraft\TravelInvoicing\Models\DocumentCounter;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\InvoiceItem;
use Kreetancraft\TravelInvoicing\Models\InvoicePayment;
use Kreetancraft\TravelInvoicing\Models\InvoicingSetting;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Kreetancraft\TravelInvoicing\Models\QuoteItem;

return [
    /*
    |--------------------------------------------------------------------------
    | Sidebar & Navigation
    |--------------------------------------------------------------------------
    |
    | The navigation heading under which Quotes, Invoices, and Invoicing Settings sit.
    |
    */
    'navigation' => [
        'group' => 'Billing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Layouts
    |--------------------------------------------------------------------------
    |
    | This package ships no global CSS or layout templates — its screens render
    | directly into your layout. Layout::admin() resolves this value with fallbacks
    | to standard conventions (`components.layouts.app`, `layouts.app`).
    |
    */
    'layouts' => [
        'admin' => 'components.layouts.app',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'register_admin' => true,
        'register_public' => true,
        'prefix' => 'admin',
        'public_prefix' => 'portal',
        'middleware' => ['web', 'auth'],
        'public_middleware' => ['web'],
        'home' => 'dashboard',

        'names' => [
            'quotes' => 'admin.quotes',
            'quotes.show' => 'admin.quotes.show',
            'quotes.create' => 'admin.quotes.create',
            'quotes.edit' => 'admin.quotes.edit',
            'invoices' => 'admin.invoices',
            'invoices.show' => 'admin.invoices.show',
            'invoices.create' => 'admin.invoices.create',
            'invoices.edit' => 'admin.invoices.edit',
            'settings' => 'admin.invoicing.settings',
            'pdf.quote' => 'travel-invoicing.pdf.quote',
            'pdf.invoice' => 'travel-invoicing.pdf.invoice',
            'public.quote' => 'travel-invoicing.public.quote',
            'public.invoice' => 'travel-invoicing.public.invoice',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Profile & Invoicing Defaults
    |--------------------------------------------------------------------------
    |
    | Fallback defaults. These can also be configured and updated at runtime
    | through the Invoicing Settings admin screen.
    |
    */
    'defaults' => [
        'business_name' => env('TRAVEL_BUSINESS_NAME', 'Himalayan Trek & Tours'),
        'tax_id' => env('TRAVEL_BUSINESS_TAX_ID', 'PAN/VAT: 601234567'),
        'address' => env('TRAVEL_BUSINESS_ADDRESS', 'Thamel Marg, Kathmandu 44600, Nepal'),
        'phone' => env('TRAVEL_BUSINESS_PHONE', '+977 1 4700000'),
        'email' => env('TRAVEL_BUSINESS_EMAIL', 'billing@himalayantrek.com'),
        'website' => env('TRAVEL_BUSINESS_WEBSITE', 'https://himalayantrek.com'),
        'currency' => env('DEFAULT_CURRENCY', 'USD'),
        'supported_currencies' => ['USD', 'NPR', 'EUR', 'GBP', 'AUD', 'CAD'],
        'quote_prefix' => env('QUOTE_NUMBER_PREFIX', 'QT'),
        'invoice_prefix' => env('INVOICE_NUMBER_PREFIX', 'INV'),
        'pad_length' => 4,
        'default_deposit_percent' => (int) env('TRAVEL_DEFAULT_DEPOSIT_PERCENT', 20),
        'quote_validity_days' => (int) env('TRAVEL_QUOTE_VALIDITY_DAYS', 14),
        'bank_account_details' => env('TRAVEL_BANK_DETAILS', "Bank: Himalayan Bank Ltd\nAccount Name: Himalayan Trek & Tours Pvt Ltd\nAccount Number: 01900001234567\nSWIFT: HIMANPKA\nBranch: Thamel, Kathmandu"),
        'payment_terms_notes' => env('TRAVEL_PAYMENT_TERMS', 'A 20% deposit is required upon booking confirmation. The remaining balance must be settled 14 days before trek departure.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document & Image Resolution (Seam with Media Manager)
    |--------------------------------------------------------------------------
    |
    | Seamless connection with kreetancraft/laravel-media-manager.
    |
    */
    'image_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Customer Resolution (Seam with Travel Customers)
    |--------------------------------------------------------------------------
    |
    | Invoices and quotes snapshot the buyer's name and email, which is what a
    | document must do. This links them to a customer record as well, so the same
    | buyer's documents hang together and someone can ask what a customer has
    | spent.
    |
    | Point it at anything exposing `findOrCreateByEmail($email, $attributes)` —
    | kreetancraft/laravel-travel-customers' CustomersContract does — or at a
    | closure. Leave it null and documents simply carry no customer id.
    |
    |   'customer_resolver' => \Kreetancraft\TravelCustomers\Contracts\CustomersContract::class,
    |
    */

    'customer_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | PDF generation
    |--------------------------------------------------------------------------
    |
    | Rendering happens on the queue, because driving a headless browser takes
    | seconds and no customer should wait for it. The resulting file's path is
    | stored on the invoice.
    |
    | `renderer` decides the engine, and null means no files are produced — the
    | printable HTML view still serves, which is what this package did before.
    | For spatie/laravel-pdf:
    |
    |   composer require spatie/laravel-pdf
    |   'renderer' => \Kreetancraft\TravelInvoicing\Support\Pdf\SpatiePdfRenderer::class,
    |
    | It drives a real browser, so the PDF matches the page a customer sees — at
    | the cost of needing Node and Puppeteer on the server. Anything exposing
    | `render(string $html): string`, or a closure, works just as well.
    |
    */

    'pdf' => [
        'renderer' => null,
        'disk' => 'local',
        'directory' => 'invoices',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    |
    | Off by default. A package that quietly emails your customers the moment it
    | is installed is worse than one that does not, so this is something you turn
    | on deliberately.
    |
    | When on: the invoice is emailed once, when it is issued, and every payment
    | gets its own receipt. The invoice is never re-sent — two documents both
    | claiming to be the bill is how a customer pays twice.
    |
    */

    'mail' => [
        'enabled' => false,
    ],
    'media_picker_view' => null,

    'collections' => [
        'company_logo' => 'company_logo',
        'company_stamp' => 'company_stamp',
        'quote_attachments' => 'quote_attachments',
    ],

    /*
    |--------------------------------------------------------------------------
    | Eloquent Models
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | The event that means a payment succeeded
    |--------------------------------------------------------------------------
    |
    | When this fires, the invoice it was paid against is credited. Named as a
    | string so this package does not depend on whichever payment package you
    | use, and so a host with none pays nothing for the feature.
    |
    | Set it to null to handle payments yourself.
    |
    */

    'payment_succeeded_event' => PaymentSucceeded::class,

    'models' => [
        'document_counter' => DocumentCounter::class,
        'invoicing_setting' => InvoicingSetting::class,
        'quote' => Quote::class,
        'quote_item' => QuoteItem::class,
        'invoice' => Invoice::class,
        'invoice_item' => InvoiceItem::class,
        'invoice_payment' => InvoicePayment::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'document_counters' => 'document_counters',
        'invoicing_settings' => 'invoicing_settings',
        'quotes' => 'quotes',
        'quote_items' => 'quote_items',
        'invoices' => 'invoices',
        'invoice_items' => 'invoice_items',
        'invoice_payments' => 'invoice_payments',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'view_quotes' => 'view-quotes',
        'create_quotes' => 'create-quotes',
        'edit_quotes' => 'edit-quotes',
        'delete_quotes' => 'delete-quotes',
        'view_invoices' => 'view-invoices',
        'create_invoices' => 'create-invoices',
        'edit_invoices' => 'edit-invoices',
        'delete_invoices' => 'delete-invoices',
        'manage_settings' => 'manage-invoicing-settings',
    ],
];
