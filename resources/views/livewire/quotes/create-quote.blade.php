<div class="space-y-6 max-w-4xl">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Create Commercial Quote / Proposal') }}</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Prepare a detailed proposal with itemized pricing, custom discounts, and deposit schedules.') }}</p>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        <!-- Client Details -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-zinc-800 pb-3">{{ __('Client / Buyer Information') }}</h2>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="buyer_name" label="{{ __('Client / Lead Full Name') }}" placeholder="e.g. John Smith" required />
                <flux:input wire:model="buyer_email" label="{{ __('Client Email Address') }}" type="email" placeholder="john@example.com" required />
                <flux:input wire:model="buyer_phone" label="{{ __('Phone Number') }}" placeholder="+1 555-0199" />
                <flux:input wire:model="buyer_country" label="{{ __('Country / Nationality') }}" placeholder="United States" />
            </div>
            <flux:textarea wire:model="buyer_address" label="{{ __('Billing Address') }}" placeholder="Street address..." rows="2" />
        </div>

        <!-- Proposal Info -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-zinc-800 pb-3">{{ __('Proposal Title & Terms') }}</h2>
            
            <flux:input wire:model="title" label="{{ __('Proposal Subject / Package Title') }}" placeholder="e.g. Everest Base Camp 14-Day Private Trek" required />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <flux:select wire:model="currency" label="{{ __('Currency') }}">
                    <option value="USD">USD ($)</option>
                    <option value="NPR">NPR (NPR)</option>
                    <option value="EUR">EUR (€)</option>
                    <option value="GBP">GBP (£)</option>
                    <option value="AUD">AUD (A$)</option>
                    <option value="CAD">CAD (C$)</option>
                </flux:select>
                <flux:input wire:model="valid_until" label="{{ __('Proposal Valid Until') }}" type="date" required />
                <flux:input wire:model.live="deposit_percent" label="{{ __('Deposit Percentage (%)') }}" type="number" min="0" max="100" required />
            </div>
        </div>

        <!-- Itemized Services & Pricing Table -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Itemized Pricing & Add-ons') }}</h2>
                <flux:button wire:click="addItemRow" type="button" size="sm" variant="ghost" icon="plus">{{ __('Add Line Item') }}</flux:button>
            </div>

            <div class="space-y-3">
                @foreach($items as $index => $item)
                    <div class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-zinc-50/50 p-3 sm:flex-row sm:items-center dark:border-zinc-800 dark:bg-zinc-800/40">
                        <div class="flex-1">
                            <flux:input wire:model="items.{{ $index }}.title" placeholder="{{ __('Service title...') }}" required />
                        </div>
                        <div class="w-full sm:w-24">
                            <flux:input wire:model.live="items.{{ $index }}.quantity" type="number" min="1" placeholder="{{ __('Qty') }}" required />
                        </div>
                        <div class="w-full sm:w-36">
                            <flux:input wire:model.live="items.{{ $index }}.unit_price_cents" type="number" min="0" placeholder="{{ __('Rate (cents)') }}" required />
                        </div>
                        <div class="flex items-center justify-end">
                            <flux:button wire:click="removeItemRow({{ $index }})" type="button" size="sm" variant="ghost" icon="trash" />
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Real-time Totals & Discounts -->
            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-800 space-y-2 text-right">
                <div class="flex justify-end gap-4 text-sm"><span class="text-zinc-500">{{ __('Subtotal:') }}</span> <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($this->subtotalCents, $currency) }}</span></div>
                
                <div class="flex items-center justify-end gap-3">
                    <span class="text-xs text-zinc-500">{{ __('Discount Cents:') }}</span>
                    <div class="w-36">
                        <flux:input wire:model.live="discount_amount_cents" type="number" min="0" placeholder="0" />
                    </div>
                </div>

                <div class="flex justify-end gap-4 text-base font-bold"><span class="text-zinc-700 dark:text-zinc-300">{{ __('Grand Total:') }}</span> <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($this->grandTotalCents, $currency) }}</span></div>
                <div class="flex justify-end gap-4 text-xs font-mono text-zinc-500"><span>{{ __('Deposit Due on Acceptance:') }}</span> <span>{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($this->depositAmountCents, $currency) }} ({{ $deposit_percent }}%)</span></div>
            </div>
        </div>

        <!-- Notes -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-zinc-800 pb-3">{{ __('Notes & Terms') }}</h2>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:textarea wire:model="client_notes" label="{{ __('Client Visible Notes') }}" rows="3" placeholder="Additional itinerary details, inclusions, terms..." />
                <flux:textarea wire:model="internal_notes" label="{{ __('Internal Staff Notes') }}" rows="3" placeholder="Private internal notes..." />
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <flux:button href="{{ route('admin.quotes') }}" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ __('Save Quote Proposal') }}</flux:button>
        </div>
    </form>
</div>
