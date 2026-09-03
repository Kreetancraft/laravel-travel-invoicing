<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Kreetancraft\TravelInvoicing\Policies\Concerns\OpenUntilPermissionsExist;

/**
 * Authorization policy for Quotes & Proposals.
 *
 * Automatically discovered by kreetancraft/laravel-user-management to generate:
 * - view-quotes
 * - create-quotes
 * - update-quotes
 * - delete-quotes
 */
class QuotePolicy
{
    use HandlesAuthorization;
    use OpenUntilPermissionsExist;

    public const PERMISSION_SUBJECT = 'quotes';

    public function viewAny(mixed $user = null): bool
    {
        return $this->allows($user, 'view');
    }

    public function view(mixed $user = null, ?Quote $quote = null): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(mixed $user = null): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(mixed $user = null, ?Quote $quote = null): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(mixed $user = null, ?Quote $quote = null): bool
    {
        return $this->allows($user, 'delete');
    }

    protected function allows(mixed $user, string $ability): bool
    {
        $map = [
            'view' => 'view-quotes',
            'create' => 'create-quotes',
            'update' => 'edit-quotes',
            'delete' => 'delete-quotes',
        ];

        $permission = (string) config(
            "travel-invoicing.permissions.{$ability}_quotes",
            $map[$ability] ?? "{$ability}-quotes"
        );

        return $this->permits($user, [$permission, 'manage-quotes']);
    }
}
