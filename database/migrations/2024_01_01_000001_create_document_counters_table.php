<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('travel-invoicing.tables.document_counters', 'document_counters');

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->string('type', 32); // 'quote', 'invoice'
                $table->unsignedInteger('year');
                $table->unsignedInteger('last_value')->default(0);
                $table->timestamps();

                $table->primary(['type', 'year']);
            });
        }
    }
};
