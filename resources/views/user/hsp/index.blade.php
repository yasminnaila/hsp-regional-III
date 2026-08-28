@extends('layouts.user')
@section('title', 'HSP Kontruksi')
@section('content')
<section class="hero hsp-hero">
    <div class="hsp-hero-copy">
        <span class="hero-badge">Tahun {{ optional($periods->firstWhere('id', $periodId))->year }}</span>
        <h1>Harga Satuan Pekerjaan Konstruksi</h1>
        <p>Referensi harga untuk perencanaan dan estimasi pekerjaan konstruksi.</p>
        <div class="hsp-hero-stats">
            <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21v-7h14v7M7 14V8h10v6M9 8V4h6v4"/><path d="M9 17h.01M12 17h.01M15 17h.01"/></svg>Material</span>
            <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 21v-5a5 5 0 0 1 10 0v5M9 9a3 3 0 1 1 6 0"/><path d="M6 9h12l-2-5H8L6 9Z"/></svg>Upah</span>
            <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17h14l3-5h1v5M5 17v2M15 17v2M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/><path d="M3 17V9h7l4 8"/></svg>Alat</span>
        </div>
    </div>
    <div class="hsp-hero-visual hsp-city" aria-hidden="true">
        <svg viewBox="0 0 700 390" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="city-top" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#f97316"/><stop offset="1" stop-color="#fbbf24"/></linearGradient>
                <linearGradient id="city-left" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#475569"/><stop offset="1" stop-color="#172554"/></linearGradient>
                <linearGradient id="city-right" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#64748b"/><stop offset="1" stop-color="#1e293b"/></linearGradient>
                <filter id="city-glow" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="5" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
            </defs>
            <ellipse cx="390" cy="324" rx="250" ry="42" fill="#150812" fill-opacity=".38"/>
            <path d="M145 270 392 166 635 270 388 362Z" fill="#fff" fill-opacity=".07" stroke="#fff" stroke-opacity=".16"/>
            <path d="m145 270 247 104v-12L145 258ZM392 362l243-92v-12L392 362Z" fill="#000" fill-opacity=".18"/>
            <g class="city-grid" fill="none" stroke="#fff" stroke-opacity=".14" stroke-width="1"><path d="m188 272 204 86 204-86M224 256l168 72 168-72M260 240l132 56 132-56M392 166v184"/></g>
            <g class="city-building">
                <path d="m278 205 75-31 75 31-75 32Z" fill="url(#city-top)"/>
                <path d="m278 205 75 32v102l-75-31Z" fill="url(#city-left)"/>
                <path d="m353 237 75-32v103l-75 31Z" fill="url(#city-right)"/>
                <path d="m290 221 52 22v75M316 210l52 22v75M390 221l-25 11v75M414 211l-25 11v75" stroke="#fff" stroke-opacity=".3" stroke-width="2"/>
                <path d="m414 201 18-8 18 8-18 8Z" fill="#fde68a"/><path d="m414 201 18 8v53l-18-8Z" fill="#334155"/><path d="m432 209 18-8v53l-18 8Z" fill="#475569"/>
            </g>
            <g class="city-building">
                <path d="m179 256 47-20 47 20-47 20Z" fill="#fbbf24"/><path d="m179 256 47 20v58l-47-20Z" fill="#334155"/><path d="m226 276 47-20v58l-47 20Z" fill="#475569"/>
                <path d="m188 268 29 12v34M207 260l29 12v34M253 268l-18 8v34" stroke="#fff" stroke-opacity=".28"/>
            </g>
            <g class="city-building">
                <path d="m472 251 52-22 53 22-53 23Z" fill="#fbbf24"/><path d="m472 251 52 23v61l-52-22Z" fill="#334155"/><path d="m524 274 53-23v62l-53 22Z" fill="#475569"/>
                <path d="m482 264 32 14v37M504 255l32 14v37M563 264l-30 13v37" stroke="#fff" stroke-opacity=".28"/>
            </g>
            <g class="city-crane" fill="none" stroke="#ffe070" stroke-linecap="round" stroke-linejoin="round">
                <path d="M225 234V76M225 76h180M225 90l180-14M298 76v44M405 76l28 18M433 94h-43" stroke-width="3"/>
                <path d="m243 76 162 0M243 76l162 18M243 76l162 36M243 76l162 54" stroke-opacity=".48"/>
                <path d="M405 76v59m-9 0h18m-9 0v22" stroke-width="2"/>
            </g>
            <g class="city-excavator" fill="none" stroke="#fbbf24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M453 314h55l13-18h20M465 314v-16h35v16M473 324a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm39 0a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/></g>
            <path d="M170 291C233 282 247 247 296 256s60 31 100 10 57-69 108-55 67 24 111-18" fill="none" stroke="#ffe070" stroke-width="4" stroke-linecap="round" filter="url(#city-glow)"/>
            <g fill="#fff2a8"><circle cx="244" cy="272" r="5"/><circle cx="296" cy="256" r="5"/><circle cx="396" cy="266" r="5"/><circle cx="504" cy="211" r="5"/></g>
        </svg>
    </div>
</section>
<div class="cards">
<div class="card-stat"><div class="stat-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div><span>Total Pekerjaan</span><strong>{{ number_format((float)($statsRow->total ?? 0),0,',','.') }}</strong></div>
<div class="card-stat"><div class="stat-icon violet"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><span>Rata-rata Harga</span><strong>Rp {{ number_format((float)($statsRow->avg_price ?? 0),0,',','.') }}</strong></div>
<div class="card-stat"><div class="stat-icon amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 6l-9.5 9.5-5-5L1 18"/><polyline points="17 6 23 6 23 12"/></svg></div><span>Harga Tertinggi</span><strong>Rp {{ number_format((float)($statsRow->max_price ?? 0),0,',','.') }}</strong></div>
<div class="card-stat"><div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><span>Rata-rata TKDN</span><strong>{{ $statsRow->avg_tkdn !== null ? number_format((float)$statsRow->avg_tkdn,2,',','.').'%' : '—' }}</strong></div>
</div>
<form class="filters card" method="GET">
<label>Tahun<select name="period" onchange="this.form.submit()">@foreach($periods as $p)<option value="{{ $p->id }}" @selected($periodId==$p->id)>{{ $p->year }}</option>@endforeach</select></label>
<label>Wilayah<select name="region" onchange="this.form.submit()">@foreach($regions as $r)<option value="{{ $r->id }}" @selected($regionId==$r->id)>{{ $r->name }}</option>@endforeach</select></label>
<label>Kategori<select name="category" onchange="this.form.submit()"><option value="">Semua Kategori</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected($categoryId==$c->id)>{{ $c->code }} - {{ $c->name }}</option>@endforeach</select></label>
<label class="grow">Cari<input name="q" value="{{ $search }}" placeholder="Kode atau uraian pekerjaan" oninput="filterDebounce(this.form)"></label>
</form>
<script>
let filterDebounceTimer;
function filterDebounce(form) {
    clearTimeout(filterDebounceTimer);
    filterDebounceTimer = setTimeout(() => form.submit(), 500);
}
</script>
<div class="card table-wrap"><table class="public-hsp-table"><thead><tr><th>No</th><th>Kode</th><th>Uraian Pekerjaan</th><th>Satuan</th><th class="num">Material</th><th class="num">Jasa</th><th class="num">Harga Satuan</th><th class="num">TKDN</th><th></th></tr></thead><tbody>
@forelse($hsp as $item) @php($price=$item->prices->first())
<tr><td>{{ $hsp->firstItem()+$loop->index }}</td><td>{{ $price?->regional_code ?? $item->work_code }}</td><td>{{ $item->description }}</td><td>{{ $item->unit }}</td><td class="num">Rp {{ number_format((float)($price?->material ?? 0),0,',','.') }}</td><td class="num">Rp {{ number_format((float)($price?->service ?? 0),0,',','.') }}</td><td class="num">Rp {{ number_format((float)($price?->price ?? 0),0,',','.') }}</td><td class="num">{{ $item->tkdn_percent !== null ? number_format((float)$item->tkdn_percent,2,',','.').'%' : '' }}</td><td><a class="btn small primary" href="{{ route('hsp.show',['hsp'=>$item,'region'=>$regionId]) }}">Detail</a></td></tr>
@empty@include('partials.empty-state', ['colspan' => 9, 'message' => 'Data tidak ditemukan. Coba ubah kata kunci atau filter lainnya.', 'resetUrl' => route('hsp.index')])@endforelse
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
