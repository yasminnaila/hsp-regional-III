<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Region extends Model {
    protected $fillable=['code','name','sort_order','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function hspPrices(): HasMany { return $this->hasMany(HspPrice::class); }
}
