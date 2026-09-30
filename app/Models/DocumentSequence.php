<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $key
 * @property string $period
 * @property int $next_number
 */
#[Fillable(['key', 'period', 'next_number'])]
class DocumentSequence extends Model
{
    /**
     * Reserve the next number for the given key and period.
     *
     * The caller is responsible for wrapping this in a database transaction.
     */
    public static function reserve(string $key, string $period = 'all'): int
    {
        DB::table('document_sequences')->insertOrIgnore([
            'key' => $key,
            'period' => $period,
            'next_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = static::query()
            ->where('key', $key)
            ->where('period', $period)
            ->lockForUpdate()
            ->firstOrFail();

        $number = $sequence->next_number;

        $sequence->increment('next_number');

        return $number;
    }
}
