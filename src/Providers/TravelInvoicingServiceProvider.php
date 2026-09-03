<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Listeners\RecordGatewayPayment;
use Kreetancraft\TravelInvoicing\Livewire\Invoices\CreateInvoice;
use Kreetancraft\TravelInvoicing\Livewire\Invoices\EditInvoice;
use Kreetancraft\TravelInvoicing\Livewire\Invoices\ManageInvoices;
use Kreetancraft\TravelInvoicing\Livewire\Invoices\ShowInvoice;
use Kreetancraft\TravelInvoicing\Livewire\Quotes\CreateQuote;
use Kreetancraft\TravelInvoicing\Livewire\Quotes\EditQuote;
use Kreetancraft\TravelInvoicing\Livewire\Quotes\ManageQuotes;
use Kreetancraft\TravelInvoicing\Livewire\Quotes\ShowQuote;
use Kreetancraft\TravelInvoicing\Livewire\Settings\ManageInvoicingSettings;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\InvoicingSetting;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Kreetancraft\TravelInvoicing\Policies\InvoicePolicy;
use Kreetancraft\TravelInvoicing\Policies\InvoicingSettingPolicy;
use Kreetancraft\TravelInvoicing\Policies\QuotePolicy;
use Kreetancraft\TravelInvoicing\Repositories\InvoicesRepository;
use Kreetancraft\TravelInvoicing\Repositories\InvoicingSettingsRepository;
use Kreetancraft\TravelInvoicing\Repositories\QuotesRepository;
use Livewire\Livewire;

class TravelInvoicingServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private const POLICIES = [
        Quote::class => QuotePolicy::class,
        Invoice::class => InvoicePolicy::class,
        InvoicingSetting::class => InvoicingSettingPolicy::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/travel-invoicing.php',
            'travel-invoicing'
        );

        $this->app->bind(QuotesContract::class, QuotesRepository::class);
        $this->app->bind(InvoicesContract::class, InvoicesRepository::class);
        $this->app->bind(InvoicingSettingsContract::class, InvoicingSettingsRepository::class);

        $this->registerNavigation();
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerLivewire();
        $this->registerPaymentListener();

        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Sidebar navigation links contributed via tagged container binding.
     *
     * Seamlessly picked up by kreetancraft/laravel-user-management or host layouts.
     */
    protected function registerNavigation(): void
    {
        $this->app->bind('travel-invoicing.navigation.items', fn () => [
            [
                'label' => __('Quotes & Proposals'),
                'icon' => 'document-text',
                'route' => config('travel-invoicing.routes.names.quotes', 'admin.quotes'),
                'ability' => 'viewAny',
                'model' => Quote::class,
                'group' => config('travel-invoicing.navigation.group', __('Billing')),
                'sort' => 60,
            ],
            [
                'label' => __('Invoices'),
                'icon' => 'banknotes',
                'route' => config('travel-invoicing.routes.names.invoices', 'admin.invoices'),
                'ability' => 'viewAny',
                'model' => Invoice::class,
                'group' => config('travel-invoicing.navigation.group', __('Billing')),
                'sort' => 61,
            ],
            [
                'label' => __('Invoicing Settings'),
                'icon' => 'cog-6-tooth',
                'route' => config('travel-invoicing.routes.names.settings', 'admin.invoicing.settings'),
                'ability' => 'viewAny',
                'model' => InvoicingSetting::class,
                'group' => config('travel-invoicing.navigation.group', __('Billing')),
                'sort' => 62,
            ],
        ]);

        $this->app->tag('travel-invoicing.navigation.items', 'admin.navigation');
    }

    /**
     * Listen for a payment gateway telling us money arrived.
     *
     * Subscribed by string, so this costs nothing on a host that has no payment
     * package — the event never fires and the listener is never resolved. Set
     * `travel-invoicing.payment_succeeded_event` to null to opt out, or to a
     * different class to listen for something else.
     */
    protected function registerPaymentListener(): void
    {
        $event = config('travel-invoicing.payment_succeeded_event');

        if (blank($event)) {
            return;
        }

        Event::listen($event, RecordGatewayPayment::class);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__.'/../../config/travel-invoicing.php' => config_path('travel-invoicing.php'),
        ], 'travel-invoicing-config');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'travel-invoicing');

        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/travel-invoicing'),
        ], 'travel-invoicing-views');

        Blade::anonymousComponentPath(__DIR__.'/../../resources/views/components', 'travel-invoicing');
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->publishesMigrations([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'travel-invoicing-migrations');
    }

    protected function registerLivewire(): void
    {
        if (class_exists(Livewire::class)) {
            $components = [
                'travel-invoicing.manage-quotes' => ManageQuotes::class,
                'travel-invoicing.show-quote' => ShowQuote::class,
                'travel-invoicing.create-quote' => CreateQuote::class,
                'travel-invoicing.edit-quote' => EditQuote::class,

                'travel-invoicing.manage-invoices' => ManageInvoices::class,
                'travel-invoicing.show-invoice' => ShowInvoice::class,
                'travel-invoicing.create-invoice' => CreateInvoice::class,
                'travel-invoicing.edit-invoice' => EditInvoice::class,

                'travel-invoicing.manage-settings' => ManageInvoicingSettings::class,
            ];

            foreach ($components as $alias => $class) {
                Livewire::component($alias, $class);
            }
        }
    }

    protected function registerRoutes(): void
    {
        if (config('travel-invoicing.routes.register_admin', true)) {
            Route::group([
                'prefix' => config('travel-invoicing.routes.prefix', 'admin'),
                'middleware' => config('travel-invoicing.routes.middleware', ['web', 'auth']),
            ], function (): void {
                $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
            });
        }

        if (config('travel-invoicing.routes.register_public', true)) {
            Route::group([
                'middleware' => config('travel-invoicing.routes.public_middleware', ['web']),
            ], function (): void {
                $this->loadRoutesFrom(__DIR__.'/../../routes/public.php');
            });
        }
    }
}
