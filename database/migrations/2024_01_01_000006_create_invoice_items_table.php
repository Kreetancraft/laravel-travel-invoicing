<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('travel-invoicing.tables.invoice_items', 'invoice_items');
        $invoicesTable = (string) config('travel-invoicing.tables.invoices', 'invoices');

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($invoicesTable): void {
                $table->id();
                $table->foreignId('invoice_id')->constrained($invoicesTable)->cascadeOnDelete();
                $table->string('description');
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedBigInteger('unit_price_cents')->default(0);
                $table->unsignedBigInteger('total_price_cents')->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }
};
