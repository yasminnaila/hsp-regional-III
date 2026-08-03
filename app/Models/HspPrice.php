<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class HspPrice extends Model {
    protected $fillable=['hsp_id','region_id','regional_code','material','equipment','service','price'];
    protected function casts(): array { return ['material'=>'decimal:2','equipment'=>'decimal:2','service'=>'decimal:2','price'=>'decimal:2']; }
    public function hsp(): BelongsTo { return $this->belongsTo(Hsp::class); }
    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
}
