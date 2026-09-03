<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('travel-invoicing.tables.quotes', 'quotes');

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('quote_reference', 32)->unique();
                $table->string('public_token', 64)->unique();

                // Optional customer reference (loose integer, no hard foreign key constraint)
                $table->unsignedBigInteger('customer_id')->nullable()->index();

                // Buyer / Client Contact Details Snapshot
                $table->string('buyer_name');
                $table->string('buyer_email');
                $table->string('buyer_phone')->nullable();
                $table->string('buyer_country')->nullable();
                $table->text('buyer_address')->nullable();

                // Proposal Subject / Title
                $table->string('title');
                $table->string('currency', 10)->default('USD');

                // Financial values stored in integer cents
                $table->unsignedBigInteger('subtotal_cents')->default(0);
                $table->unsignedBigInteger('tax_cents')->default(0);
                $table->unsignedBigInteger('discount_amount_cents')->default(0);
                $table->string('coupon_code', 50)->nullable();
                $table->string('discount_note')->nullable();
                $table->unsignedBigInteger('grand_total_cents')->default(0);

                // Deposit terms snapshot
                $table->unsignedTinyInteger('deposit_percent')->default(20);
                $table->unsignedBigInteger('deposit_amount_cents')->default(0);

                // Status & lifecycle dates
                $table->string('status', 32)->default('draft');
                $table->date('valid_until');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('responded_at')->nullable();

                // Notes, Rejection feedback, PDF & flexible metadata
                $table->text('client_notes')->nullable();
                $table->text('internal_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->string('pdf_path')->nullable();
                $table->json('metadata')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'valid_until']);
                $table->index(['buyer_email', 'status']);
            });
        }
    }
};
