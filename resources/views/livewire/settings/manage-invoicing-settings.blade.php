<div class="space-y-6 max-w-4xl">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Invoicing & Billing Settings') }}</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Configure your company profile, numbering prefixes, default deposit schedules, and payment wire instructions.') }}</p>
    </div>

    <form wire:submit.prevent="save" class="space-y-8">
        <!-- Business Profile -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-zinc-800 pb-3">{{ __('Company Profile & Legal Details') }}</h2>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="business_name" label="{{ __('Business / Company Name') }}" placeholder="e.g. Himalayan Trek & Tours Pvt Ltd" required />
                <flux:input wire:model="tax_id" label="{{ __('Tax ID / PAN / VAT Number') }}" placeholder="e.g. PAN: 601234567" />
                <flux:input wire:model="email" label="{{ __('Billing Email') }}" type="email" placeholder="billing@himalayantrek.com" />
                <flux:input wire:model="phone" label="{{ __('Business Phone') }}" placeholder="+977 1 4700000" />
                <flux:input wire:model="website" label="{{ __('Website URL') }}" placeholder="https://himalayantrek.com" />
                <flux:select wire:model="currency" label="{{ __('Default Currency') }}">
                    @foreach($supportedCurrencies as $cur)
                        <option value="{{ $cur }}">{{ $cur }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="address" label="{{ __('Registered Business Address') }}" placeholder="Street, City, Postal Code, Country" rows="2" />
        </div>

        <!-- Document Numbering & Prefixes -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-zinc-800 pb-3">{{ __('Sequential Numbering & Prefixes') }}</h2>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <flux:input wire:model="quote_prefix" label="{{ __('Quote Prefix') }}" placeholder="QT" required />
                <flux:input wire:model="invoice_prefix" label="{{ __('Invoice Prefix') }}" placeholder="INV" required />
                <flux:input wire:model="pad_length" label="{{ __('Zero Padding Length') }}" type="number" min="2" max="10" required />
            </div>
            <p class="text-xs text-zinc-500">{{ __('Example generated numbers: ') }}<strong class="font-mono text-zinc-800 dark:text-zinc-200">{{ $quote_prefix }}-{{ date('Y') }}-0001</strong> {{ __('and') }} <strong class="font-mono text-zinc-800 dark:text-zinc-200">{{ $invoice_prefix }}-{{ date('Y') }}-0001</strong></p>
        </div>

        <!-- Default Deposit Terms & Validity -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-zinc-800 pb-3">{{ __('Deposit Milestones & Proposal Validity') }}</h2>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="default_deposit_percent" label="{{ __('Default Booking Deposit (%)') }}" type="number" min="0" max="100" required />
                <flux:input wire:model="quote_validity_days" label="{{ __('Quote Proposal Validity (Days)') }}" type="number" min="1" max="365" required />
            </div>
        </div>

        <!-- Bank Account & Wire Transfer Instructions -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-zinc-800 pb-3">{{ __('Payment Instructions & Bank Wire Details') }}</h2>
            <p class="text-xs text-zinc-500">{{ __('This information will automatically print on all issued invoices and PDF documents.') }}</p>

            <flux:textarea wire:model="bank_account_details" label="{{ __('Bank Account / SWIFT Transfer Details') }}" rows="4" placeholder="Bank Name, Account Name, Account Number, SWIFT / IBAN code, Branch..." />
            <flux:textarea wire:model="payment_terms_notes" label="{{ __('Invoice Footer & Payment Terms Note') }}" rows="3" placeholder="e.g. A 20% deposit is required upon booking confirmation..." />
        </div>

        <!-- Branding Logo / Stamp -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-zinc-800 pb-3">{{ __('Company Branding Images') }}</h2>
            
            @if(\Kreetancraft\TravelInvoicing\Models\InvoicingSetting::brandingEnabled() && config('travel-invoicing.media_picker_view'))
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:heading size="sm">{{ __('Company Logo') }}</flux:heading>
                        @includeIf(config('travel-invoicing.media_picker_view'), [
                            'items' => [],
                            'group' => 'company_logo',
                            'multiple' => false,
                            'selected' => $logoMedia ?? [],
                        ])
                        <flux:text size="xs" variant="subtle" class="mt-1">{{ __('Logo via media-manager — single image') }}</flux:text>
                    </div>
                    <div>
                        <flux:heading size="sm">{{ __('Official Stamp / Signature') }}</flux:heading>
                        @includeIf(config('travel-invoicing.media_picker_view'), [
                            'items' => [],
                            'group' => 'company_stamp',
                            'multiple' => false,
                            'selected' => $stampMedia ?? [],
                        ])
                        <flux:text size="xs" variant="subtle" class="mt-1">{{ __('Stamp via media-manager — single image') }}</flux:text>
                    </div>
                </div>
                <flux:text size="xs" variant="subtle">{{ __('When media is disabled, fallback to URL below.') }}</flux:text>
            @endif
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="logo_url" label="{{ __('Company Logo URL (fallback)') }}" placeholder="https://example.com/logo.png" />
                <flux:input wire:model="stamp_url" label="{{ __('Official Stamp / Signature URL (fallback)') }}" placeholder="https://example.com/stamp.png" />
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <flux:button type="submit" variant="primary" icon="check">
                {{ __('Save Invoicing Settings') }}
            </flux:button>
        </div>
    </form>
</div>
