<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('travel-invoicing.tables.invoicing_settings', 'invoicing_settings');

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('business_name')->nullable();
                $table->string('tax_id')->nullable();
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->string('currency', 10)->default('USD');
                $table->string('quote_prefix', 16)->default('QT');
                $table->string('invoice_prefix', 16)->default('INV');
                $table->unsignedTinyInteger('pad_length')->default(4);
                $table->unsignedTinyInteger('default_deposit_percent')->default(20);
                $table->unsignedSmallInteger('quote_validity_days')->default(14);
                $table->text('bank_account_details')->nullable();
                $table->text('payment_terms_notes')->nullable();
                $table->string('logo_url')->nullable();
                $table->string('stamp_url')->nullable();
                $table->json('extra_settings')->nullable();
                $table->timestamps();
            });
        }
    }
};
