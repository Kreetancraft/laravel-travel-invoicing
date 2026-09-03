<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Policies\Concerns;

use Spatie\Permission\Models\Permission;
use Throwable;

/**
 * The rule these packages share: usable on a fresh install, closed once you
 * have said who may do what.
 *
 * A package cannot know how the host does authorization. If it denied everything
 * until permissions were seeded, installing it would look broken; if it allowed
 * everything forever, seeding permissions would do nothing and the screens would
 * stay open to anyone who found the URL.
 *
 * So permission is granted while no permissions exist at all, and from the moment
 * the host seeds its first one, the real check applies.
 *
 * The three policies here previously disagreed about this. Invoice and Quote
 * returned true for a guest unconditionally — permanently open, whatever the host
 * configured. InvoicingSetting type-hinted a non-nullable Authenticatable, so
 * Laravel denied guests before the policy ran at all, and the settings screen
 * 403'd on a fresh install.
 */
trait OpenUntilPermissionsExist
{
    /**
     * Is the host actually using permissions yet?
     *
     * False on a fresh install, or where spatie/laravel-permission is absent, or
     * before the table has been migrated — in which case there is nothing to
     * check against and the package stays usable.
     */
    protected function permissionsInUse(): bool
    {
        if (! class_exists(Permission::class)) {
            return false;
        }

        try {
            return Permission::query()->exists();
        } catch (Throwable) {
            // No table yet. Not configured is not the same as forbidden.
            return false;
        }
    }

    /**
     * May this user do this, given the permission names it maps to?
     *
     * @param  list<string>  $permissions  any one of these is enough
     */
    protected function permits(mixed $user, array $permissions): bool
    {
        if ($user === null) {
            return ! $this->permissionsInUse();
        }

        foreach ($permissions as $permission) {
            if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
                return true;
            }

            if (method_exists($user, 'can') && $user->can($permission)) {
                return true;
            }
        }

        // A user object that cannot answer the question at all is not evidence of
        // a denial, so fall back to the same fresh-install rule.
        return ! $this->permissionsInUse();
    }
}
