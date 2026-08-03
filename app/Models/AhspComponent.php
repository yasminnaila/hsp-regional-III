<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AhspComponent extends Model {
    protected $fillable=['hsp_id','basic_item_id','coefficient','sort_order','notes'];
    protected function casts(): array { return ['coefficient'=>'decimal:8']; }
    public function hsp(): BelongsTo { return $this->belongsTo(Hsp::class); }
    public function basicItem(): BelongsTo { return $this->belongsTo(BasicItem::class); }
}
