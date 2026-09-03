<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCounter extends Model
{
    public $incrementing = false;

    protected $primaryKey = ['type', 'year'];

    protected $fillable = [
        'type',
        'year',
        'last_value',
    ];

    public function getTable(): string
    {
        return (string) config('travel-invoicing.tables.document_counters', 'document_counters');
    }

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_value' => 'integer',
        ];
    }
}
