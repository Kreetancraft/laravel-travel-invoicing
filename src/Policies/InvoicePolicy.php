<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Policies\Concerns\OpenUntilPermissionsExist;

/**
 * Authorization policy for Tax Invoices.
 *
 * Automatically discovered by kreetancraft/laravel-user-management to generate:
 * - view-invoices
 * - create-invoices
 * - update-invoices
 * - delete-invoices
 */
class InvoicePolicy
{
    use HandlesAuthorization;
    use OpenUntilPermissionsExist;

    public const PERMISSION_SUBJECT = 'invoices';

    public function viewAny(mixed $user = null): bool
    {
        return $this->allows($user, 'view');
    }

    public function view(mixed $user = null, ?Invoice $invoice = null): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(mixed $user = null): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(mixed $user = null, ?Invoice $invoice = null): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(mixed $user = null, ?Invoice $invoice = null): bool
    {
        return $this->allows($user, 'delete');
    }

    protected function allows(mixed $user, string $ability): bool
    {
        $map = [
            'view' => 'view-invoices',
            'create' => 'create-invoices',
            'update' => 'edit-invoices',
            'delete' => 'delete-invoices',
        ];

        $permission = (string) config(
            "travel-invoicing.permissions.{$ability}_invoices",
            $map[$ability] ?? "{$ability}-invoices"
        );

        return $this->permits($user, [$permission, 'manage-invoices']);
    }
}
