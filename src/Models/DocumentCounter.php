<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCounter extends Model
{
    public $incrementing = false;

    /**
     * There is no single-column key, and Eloquent cannot express the pair.
     *
     * The table's key is (type, year). This used to be declared as
     * `['type', 'year']`, which Eloquent does not support: `save()` on an
     * existing row reaches `getKeyForSaveQuery()` and throws "Cannot access
     * offset of type array on array". The first document of a year inserted
     * cleanly and the second one died, every year.
     *
     * Writes go through the query builder in
     * GenerateSequentialDocumentNumberAction, which needs no key. Declaring none
     * here is honest about that; declaring a fake one invites somebody to call
     * `save()` and meet the same crash.
     */
    protected $primaryKey = 'type';

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
