<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Kreetancraft\TravelInvoicing\Models\InvoicingSetting;
use Kreetancraft\TravelInvoicing\Policies\Concerns\OpenUntilPermissionsExist;

/**
 * Authorization policy for Invoicing Settings.
 */
class InvoicingSettingPolicy
{
    use HandlesAuthorization;
    use OpenUntilPermissionsExist;

    public const PERMISSION_SUBJECT = 'invoicing-settings';

    public function viewAny(mixed $user = null): bool
    {
        return $this->allows($user, 'view');
    }

    public function view(mixed $user = null, ?InvoicingSetting $setting = null): bool
    {
        return $this->allows($user, 'view');
    }

    public function update(mixed $user = null, ?InvoicingSetting $setting = null): bool
    {
        return $this->allows($user, 'update');
    }

    protected function allows(mixed $user, string $ability): bool
    {
        return $this->permits($user, [
            "{$ability}-invoicing-settings",
            'manage-invoicing-settings',
        ]);
    }
}
