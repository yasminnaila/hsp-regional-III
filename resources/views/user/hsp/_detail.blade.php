<div class="detail-header card">
<div><strong>Kode</strong><span class="code-badge">{{ $hsp->work_code }}</span></div><div></div><div><strong>Pekerjaan</strong><span>{{ $hsp->description }}</span></div><div><strong>Satuan</strong><span>{{ $hsp->unit }}</span></div>
<form method="GET"><label>Wilayah<select name="region" onchange="this.form.submit()">@foreach($regions as $region)<option value="{{ $region->id }}" @selected($regionId==$region->id)>{{ $region->name }}</option>@endforeach</select></label></form>
@if(!empty($showExport) && !empty($exportUrl))
<a href="{{ $exportUrl }}" class="btn" style="justify-self:end;align-self:center;background:var(--accent-light);color:#065f46;border-color:#a7f3d0;padding:8px 16px;font-size:14px;min-width:140px;justify-content:center;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#065f46" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export
</a>
@endif
</div>
@php($labels=['labor'=>'A. TENAGA KERJA','material'=>'B. BAHAN','equipment'=>'C. PERALATAN'])
@php($sources=['labor'=>'upah','material'=>'bahan','equipment'=>'alat'])
@foreach($labels as $type=>$label)
<section class="card"><h3>{{ $label }}</h3><div class="table-wrap"><table class="hsp-table"><thead><tr><th>No</th><th>Kode</th><th>Uraian</th><th>Satuan</th><th class="num">Koefisien</th><th class="num">Harga Satuan</th><th class="num">Jumlah</th>@if(!empty($showExport))<th class="num">Aksi</th>@endif</tr></thead><tbody>
@forelse($analysis['groups'][$type] as $row)
@php($component = $componentsByType[$type][$loop->index] ?? null)
<tr><td>{{ $loop->iteration }}</td><td>{{ $row['code'] ?? '-' }}</td><td>{{ $row['description'] }}</td><td>{{ $row['unit'] }}</td><td class="num">{{ number_format($row['coefficient'],4,',','.') }}</td><td class="num">Rp {{ number_format($row['unit_price'],0,',','.') }}</td><td class="num">Rp {{ number_format($row['amount'],0,',','.') }}</td>
@if(!empty($showExport) && $component)
<td class="num"><form method="POST" action="{{ route('admin.hsp.components.destroy',['hsp'=>$hsp,'component'=>$component]) }}" style="display:inline;" onsubmit="return confirm('Hapus komponen ini?');">@csrf @method('DELETE')<input type="hidden" name="region" value="{{ $regionId }}"><button type="submit" class="btn xs" title="Hapus" style="color:#b91c1c;border-color:#fecaca;background:#fef2f2;">✕</button></form></td>
@endif
</tr>
@empty<tr><td colspan="{{ !empty($showExport) ? 8 : 7 }}" class="muted">Belum ada komponen {{ strtolower(substr($label,3)) }}.</td></tr>@endforelse
@if(!empty($showExport))
<tr><td colspan="8" style="border-top:2px solid var(--border);"><form method="POST" action="{{ route('admin.hsp.components.store',$hsp) }}" style="display:flex;gap:6px;align-items:center;">@csrf<input type="hidden" name="region" value="{{ $regionId }}"><select name="basic_item_id" required style="flex:1;min-width:0;"><option value="">Pilih {{ $sources[$type] }}…</option>@foreach($basicItems[$type] ?? [] as $item)<option value="{{ $item->id }}">{{ $item->code }} — {{ $item->description }} ({{ $item->unit ?: '-' }})</option>@endforeach</select><input type="number" name="coefficient" step="0.0001" min="0.0001" required placeholder="Koefisien" style="width:110px;"><button type="submit" class="btn primary small">+ Tambah</button></form></td></tr>
@endif
</tbody><tfoot><tr><th colspan="{{ !empty($showExport) ? 6 : 5 }}"></th><th class="num">Jumlah</th><th class="num">Rp {{ number_format($analysis['subtotals_rounded'][$type],0,',','.') }}</th></tr><tr><th colspan="{{ !empty($showExport) ? 6 : 5 }}"></th><th class="num">Jumlah × {{ rtrim(rtrim(number_format($analysis['overhead_percent'],2,',','.'),'0'),',') }}% overhead &amp; profit</th><th class="num">Rp {{ number_format($analysis['subtotals_with_overhead'][$type],0,',','.') }}</th></tr></tfoot></table></div></section>
@endforeach
<section class="summary card"><div class="direct"><span>D. Jumlah Biaya Langsung (A+B+C)</span><strong>Rp {{ number_format($analysis['direct_cost_rounded'],0,',','.') }}</strong></div><div class="overhead"><span>E. Overhead &amp; Profit ({{ rtrim(rtrim(number_format($analysis['overhead_percent'],2,',','.'),'0'),',') }}%)</span><strong>Rp {{ number_format((float)$analysis['overhead_amount'],0,',','.') }}</strong></div><div class="final"><span>F. Harga Satuan Pekerjaan</span><strong>Rp {{ number_format($analysis['final_price'],0,',','.') }}</strong></div></section>
<div class="toolbar" style="margin-top:8px;"><a class="btn" href="{{ $backUrl }}" style="background:linear-gradient(180deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%); color:#fff; border-color:#991b1b; box-shadow:0 2px 8px rgba(220,38,38,.3);">Kembali</a></div>
