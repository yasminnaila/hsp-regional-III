<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Period extends Model {
    protected $fillable=['year','name','is_active'];
    protected function casts(): array { return ['year'=>'integer','is_active'=>'boolean']; }
    public function hsp(): HasMany { return $this->hasMany(Hsp::class); }
}
