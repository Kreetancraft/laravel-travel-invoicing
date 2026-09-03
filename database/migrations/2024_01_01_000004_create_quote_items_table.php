<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('travel-invoicing.tables.quote_items', 'quote_items');
        $quotesTable = (string) config('travel-invoicing.tables.quotes', 'quotes');

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($quotesTable): void {
                $table->id();
                $table->foreignId('quote_id')->constrained($quotesTable)->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedBigInteger('unit_price_cents')->default(0);
                $table->unsignedBigInteger('total_price_cents')->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }
};
