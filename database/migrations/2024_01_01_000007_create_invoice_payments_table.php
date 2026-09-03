<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('travel-invoicing.tables.invoice_payments', 'invoice_payments');
        $invoicesTable = (string) config('travel-invoicing.tables.invoices', 'invoices');

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($invoicesTable): void {
                $table->id();
                $table->foreignId('invoice_id')->constrained($invoicesTable)->cascadeOnDelete();
                $table->string('transaction_reference', 64)->nullable()->index();
                $table->string('gateway', 32)->nullable(); // 'stripe', 'himalayan', 'manual', 'bank_transfer'
                $table->unsignedBigInteger('amount_cents');
                $table->string('currency', 10)->default('USD');
                $table->string('status', 32)->default('succeeded');
                $table->timestamp('paid_at');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }
};
