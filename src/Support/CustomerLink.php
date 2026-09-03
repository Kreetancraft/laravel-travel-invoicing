<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Support;

use Throwable;

/**
 * Attach a document to the customer it belongs to.
 *
 * Invoices and quotes carry the buyer's name and email as a snapshot, which is
 * right — a document has to keep saying what it said when it was issued, even
 * after the customer changes their address. But a snapshot alone means the same
 * buyer's five invoices have no connection to each other, and nothing can answer
 * "what has this customer spent".
 *
 * `customer_id` exists for that, and until now nothing ever filled it in.
 *
 * The customer package is reached the same way the media package is: a class
 * named in config, called by the method it happens to have. No interface is
 * imported across packages, so a host without a customer package sets nothing
 * and every document simply has no customer id — exactly as before.
 */
class CustomerLink
{
    /**
     * The customer id for this buyer, creating the customer if that is what the
     * resolver does, or null when no resolver is configured.
     *
     * @param  array<string, mixed>  $attributes  name, phone and so on, for a customer being created
     */
    public static function idFor(?string $email, array $attributes = []): ?int
    {
        if (blank($email)) {
            return null;
        }

        $resolver = config('travel-invoicing.customer_resolver');

        if ($resolver === null) {
            return null;
        }

        try {
            $instance = is_string($resolver) ? app($resolver) : $resolver;

            if (is_callable($instance)) {
                return static::idOf($instance($email, $attributes));
            }

            // The name laravel-travel-customers uses.
            if (method_exists($instance, 'findOrCreateByEmail')) {
                return static::idOf($instance->findOrCreateByEmail($email, $attributes));
            }

            return null;
        } catch (Throwable) {
            // Linking is a convenience, not a precondition. A customer package
            // that is misconfigured or mid-migration must not stop an invoice
            // being issued — the document's own snapshot of the buyer is what it
            // is actually billed against.
            return null;
        }
    }

    /**
     * The resolver may hand back a model, an id, or nothing.
     */
    protected static function idOf(mixed $customer): ?int
    {
        if (is_int($customer) || is_numeric($customer)) {
            return (int) $customer;
        }

        if (is_object($customer) && method_exists($customer, 'getKey')) {
            return (int) $customer->getKey();
        }

        if (is_object($customer) && isset($customer->id)) {
            return (int) $customer->id;
        }

        return null;
    }
}
