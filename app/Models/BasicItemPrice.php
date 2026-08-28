<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasicItemPrice extends Model
{
    /**
     * Kolom yang boleh diisi melalui create(),
     * update(), dan updateOrCreate().
     */
    protected $fillable = [
        'basic_item_id',
        'period_id',
        'region_id',
        'price',
        'reference_price_1',
        'reference_link_1',
        'reference_url_1',
        'reference_price_2',
        'reference_link_2',
        'reference_url_2',
    ];

    /**
     * Konversi tipe data otomatis dari database.
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:3',
            'reference_price_1' => 'decimal:3',
            'reference_price_2' => 'decimal:3',
        ];
    }

    /**
     * Harga ini dimiliki oleh satu item dasar.
     */
    public function basicItem(): BelongsTo
    {
        return $this->belongsTo(BasicItem::class);
    }

    /**
     * Harga ini berlaku untuk satu periode.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    /**
     * Harga ini berlaku untuk satu wilayah.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
