<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class BasicItem extends Model {
    protected $fillable=['code','source_no','item_type','description','unit','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function prices(): HasMany { return $this->hasMany(BasicItemPrice::class); }
    public function components(): HasMany { return $this->hasMany(AhspComponent::class); }
}
