<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AhspParameter extends Model {
    protected $fillable=['hsp_id','region_id','overhead_profit_percent'];
    protected function casts(): array { return ['overhead_profit_percent'=>'decimal:4']; }
    public function hsp(): BelongsTo { return $this->belongsTo(Hsp::class); }
    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
}
