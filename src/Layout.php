<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use RuntimeException;

/**
 * Dynamic Layout Resolver for Travel Invoicing & Proposal screens.
 *
 * This package ships no global CSS or layout templates — its screens render directly
 * into your application's layout (inheriting your Tailwind CSS and Flux UI theme).
 *
 * It checks the configured layout in `config/travel-invoicing.php`, attempts standard
 * Laravel starter-kit conventions (`components.layouts.app`, `layouts.app`), and provides
 * an educational error message if no layout view is found.
 */
class Layout
{
    /**
     * Standard layout view paths to probe when no custom layout is configured.
     *
     * @var list<string>
     */
    public const CONVENTIONS = [
        'components.layouts.app',
        'layouts.app',
        'components.layouts.admin',
        'layouts.admin',
    ];

    /**
     * Resolve the layout for administrative invoicing and quote screens.
     */
    public static function admin(): string
    {
        return self::resolve(
            config('travel-invoicing.layouts.admin'),
            'travel-invoicing.layouts.admin',
            self::CONVENTIONS,
        );
    }

    /**
     * Resolve the URL or route for the "Dashboard" breadcrumb.
     */
    public static function home(): string
    {
        $home = (string) config('travel-invoicing.routes.home', 'dashboard');

        if ($home === '') {
            return '/';
        }

        if (Route::has($home)) {
            return route($home);
        }

        return str_starts_with($home, '/') || str_contains($home, '://') || str_starts_with($home, '#')
            ? $home
            : '/';
    }

    /**
     * @param  list<string>  $conventions
     */
    private static function resolve(?string $configured, string $key, array $conventions): string
    {
        if (is_string($configured) && $configured !== '' && View::exists($configured)) {
            return $configured;
        }

        foreach ($conventions as $candidate) {
            if (View::exists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf(
            'No layout to render into. Set `%s` in config/travel-invoicing.php to one of your layout views. '
            .'Tried: %s. This package ships no layout by design — its screens render into yours.',
            $key,
            implode(', ', array_values(array_unique(array_filter(
                array_merge([$configured], $conventions)
            )))),
        ));
    }
}
