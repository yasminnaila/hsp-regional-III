<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Hsp extends Model {
    protected $table='hsp';
    protected $fillable=['period_id','category_id','work_code','binkon_code','description','unit','is_active','sort_key','tkdn_percent'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function period(): BelongsTo { return $this->belongsTo(Period::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function prices(): HasMany { return $this->hasMany(HspPrice::class); }
    public function components(): HasMany { return $this->hasMany(AhspComponent::class); }
    public function parameters(): HasMany { return $this->hasMany(AhspParameter::class); }

    /**
     * Kunci urut yang menghasilkan urutan yang sama dengan spreadsheet master
     * (sheet HSP/AHS): kode ROMAN diurutkan sebagai angka, bagian angka
     * di-nol-padding, bagian huruf dipertahankan. Contoh: "I.10" < "I.2",
     * "XIV.6" → "0014.0006".
     */
    public static function sortKeyFromWorkCode(string $workCode): string
    {
        $roman = [
            'i' => 1, 'ii' => 2, 'iii' => 3, 'iv' => 4, 'v' => 5, 'vi' => 6,
            'vii' => 7, 'viii' => 8, 'ix' => 9, 'x' => 10, 'xi' => 11, 'xii' => 12,
            'xiii' => 13, 'xiv' => 14, 'xv' => 15, 'xvi' => 16,
        ];

        $parts = preg_split('/[.\-]/', strtolower(trim($workCode))) ?: [];
        $key = [];

        foreach ($parts as $i => $part) {
            if ($part === '') {
                $key[] = '';
                continue;
            }

            if (isset($roman[$part])) {
                $key[] = sprintf('%04d', $roman[$part]);
                continue;
            }

            if (ctype_digit($part)) {
                $key[] = sprintf('%04d', (int) $part);
                continue;
            }

            $key[] = $part;
        }

        return implode('.', $key);
    }
}
