@extends('layouts.admin')
@section('title', $hsp->exists ? 'Edit HSP' : 'Tambah HSP')
@section('page-title', $hsp->exists ? 'Edit Data HSP' : 'Tambah Data HSP')
@section('breadcrumb')
    <a href="{{ route('admin.hsp.index') }}">Data HSP</a>
    <span>/</span>
    <span>{{ $hsp->exists ? 'Edit' : 'Tambah' }}</span>
@endsection
@section('header-actions')
    <a class="btn" href="{{ route('admin.hsp.index') }}">Kembali</a>
    <button class="btn primary" form="hsp-form">Simpan Data</button>
@endsection
@section('content')
<form id="hsp-form" class="card stack" method="POST" action="{{ $hsp->exists ? route('admin.hsp.update',$hsp) : route('admin.hsp.store') }}">
@csrf @if($hsp->exists) @method('PUT') @endif
@if($errors->any())<div class="alert danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="form-grid">
<label>Periode<select name="period_id" required>@foreach($periods as $p)<option value="{{ $p->id }}" @selected(old('period_id',$hsp->period_id)==$p->id)>{{ $p->year }}</option>@endforeach</select></label>
<label>Kategori<select name="category_id"><option value="">-</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id',$hsp->category_id)==$c->id)>{{ $c->code }} - {{ $c->name }}</option>@endforeach</select></label>
<label>Jenis<select name="work_type" id="work-type">
<option value="">Pilih jenis</option>
<option value="PE" @selected(str_starts_with(old('work_code',$hsp->work_code),'PE'))>Peralatan</option>
<option value="BA" @selected(str_starts_with(old('work_code',$hsp->work_code),'BA'))>Bahan</option>
<option value="JA" @selected(str_starts_with(old('work_code',$hsp->work_code),'JA'))>Jasa</option>
</select></label>
<label>Kode Pekerjaan<input name="work_code" id="work-code" value="{{ old('work_code',$hsp->work_code) }}" required placeholder="PE-001"></label>
<label>Satuan<input name="unit" value="{{ old('unit',$hsp->unit) }}" placeholder="m³"></label>
<label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$hsp->exists ? $hsp->is_active : true))> Data aktif</label>
<label class="full">Uraian Pekerjaan<textarea name="description" rows="3" required>{{ old('description',$hsp->description) }}</textarea></label>
</div>
<h3>Harga per Wilayah</h3>
<div class="table-wrap"><table><thead><tr><th>Wilayah</th><th>Kode Regional</th><th>Material</th><th>Peralatan</th><th>Jasa</th><th>Harga</th></tr></thead><tbody>
@foreach($regions as $region)
@php($price=$hsp->prices->firstWhere('region_id',$region->id))
<tr><td>{{ $region->name }}</td>
<td><input name="prices[{{ $region->id }}][regional_code]" value="{{ old('prices.'.$region->id.'.regional_code',$price?->regional_code) }}"></td>
<td><input type="number" step="0.01" min="0" name="prices[{{ $region->id }}][material]" value="{{ old('prices.'.$region->id.'.material',$price?->material ?? 0) }}"></td>
<td><input type="number" step="0.01" min="0" name="prices[{{ $region->id }}][equipment]" value="{{ old('prices.'.$region->id.'.equipment',$price?->equipment ?? 0) }}"></td>
<td><input type="number" step="0.01" min="0" name="prices[{{ $region->id }}][service]" value="{{ old('prices.'.$region->id.'.service',$price?->service ?? 0) }}"></td>
<td><input type="number" step="0.01" min="0" name="prices[{{ $region->id }}][price]" value="{{ old('prices.'.$region->id.'.price',$price?->price ?? 0) }}"></td></tr>
@endforeach
</tbody></table></div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var t = document.getElementById('work-type'), c = document.getElementById('work-code');
    if (!t || !c) return;
    t.addEventListener('change', function() {
        var v = this.value, cur = c.value;
        if (!v) return;
        if (!cur || /^(PE|BA|JA)-?$/.test(cur) || !/^(PE|BA|JA)/.test(cur)) {
            c.value = v + '-'; return;
        }
        c.value = cur.replace(/^(PE|BA|JA)-?/, v + '-');
    });
});
</script>
@endsection
