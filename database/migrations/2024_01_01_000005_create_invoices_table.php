<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('travel-invoicing.tables.invoices', 'invoices');
        $quotesTable = (string) config('travel-invoicing.tables.quotes', 'quotes');

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($quotesTable): void {
                $table->id();
                $table->string('invoice_number', 32)->unique();
                $table->string('public_token', 64)->unique();

                // Links to quote & customer (loose integer references, no external hard FK constraints)
                $table->foreignId('quote_id')->nullable()->constrained($quotesTable)->nullOnDelete();
                $table->unsignedBigInteger('customer_id')->nullable()->index();

                // Snapshot of buyer details
                $table->string('buyer_name');
                $table->string('buyer_email');
                $table->string('buyer_phone')->nullable();
                $table->string('buyer_country')->nullable();
                $table->text('buyer_address')->nullable();

                $table->string('title')->nullable();
                $table->string('currency', 10)->default('USD');

                // Financial values stored in integer cents
                $table->unsignedBigInteger('subtotal_cents')->default(0);
                $table->unsignedBigInteger('tax_cents')->default(0);
                $table->unsignedBigInteger('discount_amount_cents')->default(0);
                $table->string('coupon_code', 50)->nullable();
                $table->unsignedBigInteger('grand_total_cents')->default(0);

                // Deposit & payments tracking
                $table->unsignedBigInteger('deposit_amount_cents')->default(0);
                $table->unsignedBigInteger('amount_paid_cents')->default(0);

                // Status & dates
                $table->string('status', 32)->default('draft');
                $table->date('issue_date');
                $table->date('due_date')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('paid_at')->nullable();

                $table->text('notes')->nullable();
                $table->string('pdf_path')->nullable();
                $table->json('metadata')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'due_date']);
                $table->index(['buyer_email', 'status']);
            });
        }
    }
};
