@extends('layouts.user')
@section('title', 'HSP Kontruksi')
@section('content')
<section class="hero"><h1>Harga Satuan Pekerjaan Kontruksi</h1><p>Regional III</p></section>
<form class="filters card" method="GET">
<label>Tahun<select name="period">@foreach($periods as $p)<option value="{{ $p->id }}" @selected($periodId==$p->id)>{{ $p->year }}</option>@endforeach</select></label>
<label>Wilayah<select name="region">@foreach($regions as $r)<option value="{{ $r->id }}" @selected($regionId==$r->id)>{{ $r->name }}</option>@endforeach</select></label>
<label>Kategori<select name="category"><option value="">Semua Kategori</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected($categoryId==$c->id)>{{ $c->code }} - {{ $c->name }}</option>@endforeach</select></label>
<label class="grow">Cari<input name="q" value="{{ $search }}" placeholder="Kode atau uraian pekerjaan"></label><button class="btn primary">Cari</button>
</form>
<div class="card table-wrap"><table><thead><tr><th>No</th><th>Kode</th><th>Uraian Pekerjaan</th><th>Satuan</th><th>Material</th><th>Jasa</th><th>Harga Satuan</th><th></th></tr></thead><tbody>
@forelse($hsp as $item) @php($price=$item->prices->first())
<tr><td>{{ $hsp->firstItem()+$loop->index }}</td><td>{{ $price?->regional_code ?? $item->work_code }}</td><td>{{ $item->description }}</td><td>{{ $item->unit }}</td><td>Rp {{ number_format((float)($price?->material ?? 0),0,',','.') }}</td><td>Rp {{ number_format((float)($price?->service ?? 0),0,',','.') }}</td><td>Rp {{ number_format((float)($price?->price ?? 0),0,',','.') }}</td><td><a class="btn small primary" href="{{ route('hsp.show',['hsp'=>$item,'region'=>$regionId]) }}">Detail</a></td></tr>
@empty<tr><td colspan="8" class="muted">Data tidak ditemukan.</td></tr>@endforelse
</tbody></table></div>
@if ($hsp->hasPages())
<div class="pagination-wrap">
    <div class="page-nav">
        @if ($hsp->onFirstPage())
            <span class="page-btn disabled">&#8592; Sebelumnya</span>
        @else
            <a class="page-btn primary" href="{{ $hsp->previousPageUrl() }}">&#8592; Sebelumnya</a>
        @endif
        <span class="page-info">
            Halaman {{ $hsp->currentPage() }} dari {{ $hsp->lastPage() }}
            ({{ $hsp->total() }} data)
        </span>
        @if ($hsp->hasMorePages())
            <a class="page-btn primary" href="{{ $hsp->nextPageUrl() }}">Berikutnya &#8594;</a>
        @else
            <span class="page-btn disabled">Berikutnya &#8594;</span>
        @endif
    </div>
</div>
@endif
@endsection
