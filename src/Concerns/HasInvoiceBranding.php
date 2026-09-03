<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Concerns;

/**
 * Trait for managing invoice & proposal branding (company logo, stamp, PDF attachments).
 *
 * This package delegates media handling to the configured `image_resolver` in
 * `config/travel-invoicing.php`.
 *
 * If `kreetancraft/laravel-media-manager` is installed and configured, branding images
 * resolve smoothly. If absent, methods return null and pickers hide gracefully.
 */
trait HasInvoiceBranding
{
    /**
     * URL of the company branding logo.
     */
    public function logoUrl(): ?string
    {
        $collection = (string) config('travel-invoicing.collections.company_logo', 'company_logo');

        return $this->brandingImageUrl($collection);
    }

    /**
     * URL of the company official stamp / signature.
     */
    public function stampUrl(): ?string
    {
        $collection = (string) config('travel-invoicing.collections.company_stamp', 'company_stamp');

        return $this->brandingImageUrl($collection);
    }

    /**
     * Resolve the first image URL in a specified collection.
     */
    public function brandingImageUrl(string $collection): ?string
    {
        $resolver = config('travel-invoicing.image_resolver');

        if ($resolver === null) {
            return null;
        }

        $resolverInstance = is_string($resolver) ? app($resolver) : $resolver;

        if (is_callable($resolverInstance)) {
            return $resolverInstance($this, $collection);
        }

        if (method_exists($resolverInstance, 'url')) {
            return $resolverInstance->url($this, $collection);
        }

        return null;
    }

    /**
     * Sync attached media item IDs to a collection.
     *
     * @param  list<int|string>  $ids
     */
    public function syncAttachedMedia(array $ids, string $collection = 'company_logo'): void
    {
        $resolver = config('travel-invoicing.image_resolver');

        if ($resolver === null) {
            return;
        }

        $resolverInstance = is_string($resolver) ? app($resolver) : $resolver;

        if (method_exists($resolverInstance, 'sync')) {
            $resolverInstance->sync($this, $collection, $ids);
        }
    }

    /**
     * List attached branding images in a collection.
     *
     * @return list<array{id: int|string, url: ?string, name: ?string}>
     */
    public function brandingImageList(string $collection): array
    {
        $resolver = config('travel-invoicing.image_resolver');

        if ($resolver === null) {
            return [];
        }

        $resolverInstance = is_string($resolver) ? app($resolver) : $resolver;

        if (method_exists($resolverInstance, 'list')) {
            return $resolverInstance->list($this, $collection);
        }

        return [];
    }

    /**
     * Check if media branding is enabled in the host application.
     */
    public static function brandingEnabled(): bool
    {
        return config('travel-invoicing.image_resolver') !== null;
    }
}
