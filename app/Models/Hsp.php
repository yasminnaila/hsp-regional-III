<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Hsp extends Model {
    protected $table='hsp';
    protected $fillable=['period_id','category_id','work_code','binkon_code','description','unit','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function period(): BelongsTo { return $this->belongsTo(Period::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function prices(): HasMany { return $this->hasMany(HspPrice::class); }
    public function components(): HasMany { return $this->hasMany(AhspComponent::class); }
    public function parameters(): HasMany { return $this->hasMany(AhspParameter::class); }
}
