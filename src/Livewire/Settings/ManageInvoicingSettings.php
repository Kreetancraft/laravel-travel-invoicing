<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Livewire\Settings;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Layout;
use Kreetancraft\TravelInvoicing\Models\InvoicingSetting;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

class ManageInvoicingSettings extends Component
{
    public string $business_name = '';

    public ?string $tax_id = null;

    public ?string $address = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $website = null;

    public string $currency = 'USD';

    public string $quote_prefix = 'QT';

    public string $invoice_prefix = 'INV';

    public int $pad_length = 4;

    public int $default_deposit_percent = 20;

    public int $quote_validity_days = 14;

    public ?string $bank_account_details = null;

    public ?string $payment_terms_notes = null;

    public ?string $logo_url = null;

    public ?string $stamp_url = null;

    public array $logoMedia = [];

    public array $stampMedia = [];

    public function mount(InvoicingSettingsContract $settings): void
    {
        $this->authorize('update', InvoicingSetting::class);

        $current = $settings->get();

        $this->business_name = $current->business_name ?? '';
        $this->tax_id = $current->tax_id;
        $this->address = $current->address;
        $this->phone = $current->phone;
        $this->email = $current->email;
        $this->website = $current->website;
        $this->currency = $current->currency ?? 'USD';
        $this->quote_prefix = $current->quote_prefix ?? 'QT';
        $this->invoice_prefix = $current->invoice_prefix ?? 'INV';
        $this->pad_length = (int) ($current->pad_length ?? 4);
        $this->default_deposit_percent = (int) ($current->default_deposit_percent ?? 20);
        $this->quote_validity_days = (int) ($current->quote_validity_days ?? 14);
        $this->bank_account_details = $current->bank_account_details;
        $this->payment_terms_notes = $current->payment_terms_notes;
        $this->logo_url = $current->logo_url;
        $this->stamp_url = $current->stamp_url;
        // Hydrate media if branding enabled
        if ($current::brandingEnabled()) {
            $this->logoMedia = array_column($current->brandingImageList('company_logo'), 'id');
            $this->stampMedia = array_column($current->brandingImageList('company_stamp'), 'id');
        }
    }

    #[On('media-picked')]
    public function onMediaPicked(array $payload = []): void
    {
        $ids = $payload['ids'] ?? $payload[0] ?? [];
        $group = $payload['group'] ?? $payload[1] ?? null;
        if (! is_array($ids)) {
            $ids = (array) $ids;
        }
        match ($group) {
            'company_logo', 'invoicing-logo' => $this->logoMedia = $ids,
            'company_stamp', 'invoicing-stamp' => $this->stampMedia = $ids,
            default => null,
        };
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'quote_prefix' => ['required', 'string', 'max:16'],
            'invoice_prefix' => ['required', 'string', 'max:16'],
            'pad_length' => ['required', 'integer', 'min:2', 'max:10'],
            'default_deposit_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'quote_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'bank_account_details' => ['nullable', 'string'],
            'payment_terms_notes' => ['nullable', 'string'],
            'logo_url' => ['nullable', 'string'],
            'stamp_url' => ['nullable', 'string'],
        ];
    }

    public function save(InvoicingSettingsContract $settings): void
    {
        $this->validate();

        $model = $settings->update([
            'business_name' => $this->business_name,
            'tax_id' => $this->tax_id ?: null,
            'address' => $this->address ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'website' => $this->website ?: null,
            'currency' => $this->currency,
            'quote_prefix' => strtoupper(trim($this->quote_prefix)),
            'invoice_prefix' => strtoupper(trim($this->invoice_prefix)),
            'pad_length' => $this->pad_length,
            'default_deposit_percent' => $this->default_deposit_percent,
            'quote_validity_days' => $this->quote_validity_days,
            'bank_account_details' => $this->bank_account_details ?: null,
            'payment_terms_notes' => $this->payment_terms_notes ?: null,
            'logo_url' => $this->logo_url ?: null,
            'stamp_url' => $this->stamp_url ?: null,
        ]);

        if ($model::brandingEnabled()) {
            $model->syncAttachedMedia($this->logoMedia, 'company_logo');
            $model->syncAttachedMedia($this->stampMedia, 'company_stamp');
        }

        Flux::toast(variant: 'success', text: __('Invoicing settings saved successfully.'));
    }

    #[Title('Invoicing Settings & Configuration')]
    public function render(): View
    {
        return view('travel-invoicing::livewire.settings.manage-invoicing-settings', [
            'supportedCurrencies' => config('travel-invoicing.defaults.supported_currencies', ['USD', 'NPR', 'EUR', 'GBP', 'AUD', 'CAD']),
        ])->layout(Layout::admin());
    }
}
